<?php

namespace App\Jobs\FillingOld;

use App\Helpers\Common\ErrorCollector;
use App\Events\ImportProgressEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DistributeFilingFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    protected string $selectedQueue;

    const CHUNK_SIZE = 50;

    private static $expectedColumns = [
        'AC' => 17, 'AF' => 17, 'AH' => 19, 'AM' => 14,
        'AN' => 14, 'AP' => 15, 'AT' => 11, 'AU' => 17,
        'US' => 14, 'CT' => 4,
    ];

    public function __construct(string $batchId, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $redisKey = "batch:{$this->batchId}:metadata";
        $metadata = $redis->hgetall($redisKey);

        // --- 1. EXTRACCIÓN Y VALIDACIÓN DE ESTRUCTURA ---
        $totalFilesInZip = (int) ($metadata['total_files_in_zip'] ?? 0);

        event(new ImportProgressEvent(
            $this->batchId, 0, "Fase 2: Revisando estructura...", 0, 'active', 'Structure Check'
        ));

        if (empty($metadata['path_zip'])) {
            $this->failJob("No se encontró ruta del ZIP."); return;
        }

        $zipPath = storage_path('app/public/' . $metadata['path_zip']);
        $extractPath = storage_path('app/public/temp/filings/extracted/' . $this->batchId);

        // Lógica de re-extracción si no existe (por si acaso)
        if (!is_dir($extractPath)) {
             if (!file_exists($zipPath)) {
                 $this->failJob("El archivo ZIP ya no existe."); return;
             }
             if (!mkdir($extractPath, 0755, true)) {
                 $this->failJob("No se pudo crear directorio temporal."); return;
             }
             $zip = new ZipArchive;
             if ($zip->open($zipPath) === true) {
                 $zip->extractTo($extractPath);
                 $zip->close();
             } else {
                 $this->failJob("Error crítico al extraer el ZIP."); return;
             }
        }

        $files = scandir($extractPath);
        $filesToProcess = [];
        $validatedCount = 0;

        // Filtramos y Validamos Columnas
        foreach ($files as $fileName) {
            if ($fileName === '.' || $fileName === '..' || str_starts_with($fileName, '__')) continue;
            if (str_starts_with(basename($fileName), '.')) continue;

            $fullFilePath = $extractPath . '/' . $fileName;

            if (!$this->validateInternalFile($fileName, $fullFilePath)) {
                // Si falla columnas, paramos todo el proceso
                $this->failJob("Fase 2 Fallida: Archivos con columnas incorrectas.");
                Storage::deleteDirectory('public/temp/filings/extracted/' . $this->batchId);
                return;
            }

            $filesToProcess[] = $fullFilePath;

            $validatedCount++;
            event(new ImportProgressEvent(
                $this->batchId, $validatedCount, "Estructura OK: {$fileName}",
                ErrorCollector::countErrors($this->batchId), 'active', 'Structure Check'
            ));
        }

        // ============================================================
        // 🚀 FASE 3: DISTRIBUCIÓN (Con Fix UTF-8 + Captura CT)
        // ============================================================

        $redis->del("batch:{$this->batchId}:file_progress"); // Limpieza previa

        // 1. CÁLCULO DE TOTALES GLOBALES
        event(new ImportProgressEvent(
            $this->batchId, 0, "Calculando totales...", 0, 'active', 'Calculating'
        ));

        $grandTotalRows = 0;
        $filesMap = [];

        foreach ($filesToProcess as $filePath) {
            $fName = basename($filePath);
            $lines = 0;
            $h = fopen($filePath, 'r');
            while(!feof($h)){
                $l = fgets($h);
                if($l !== false && trim($l) !== '') $lines++;
            }
            fclose($h);

            $filesMap[$fName] = $lines;
            $grandTotalRows += $lines;
        }

        if (!empty($filesMap)) {
            $redis->hmset("batch:{$this->batchId}:file_counts", $filesMap);
        }

        $redis->hmset($redisKey, [
            'total_rows' => $grandTotalRows,
            'processed_records' => 0,
            'status' => 'processing_rows'
        ]);

        // 2. LECTURA, CAPTURA DE CABECERAS Y ENVÍO DE CHUNKS
        event(new ImportProgressEvent(
            $this->batchId, 0, "Procesando {$grandTotalRows} registros...", 0, 'active', 'Distributing'
        ));

        foreach ($filesToProcess as $filePath) {
            $fileName = basename($filePath);
            $prefix = strtoupper(substr($fileName, 0, 2)); // US, AF, CT...

            $handle = fopen($filePath, 'r');
            $chunkData = [];
            $lineNum = 0;

            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $lineNum++;
                if (count($data) === 1 && is_null($data[0])) continue;

                // A. FIX UTF-8
                $data = array_map(function($value) {
                    return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                }, $data);

                // B. 🔥 CAPTURA DE DATOS DEL CT (Aquí se llena la variable)
                // Si es el archivo de Control (CT) y es la primera línea...
                if ($prefix === 'CT' && $lineNum === 1) {
                    $providerCode = trim($data[0] ?? ''); // Columna 1 del CT es el Código Prestador
                    // Guardamos en Redis para que AFFileValidator lo pueda consultar
                    $redis->hset("batch:{$this->batchId}:header_info", 'provider_code', $providerCode);
                }

                $chunkData[] = [
                    'row_number' => $lineNum,
                    'data' => $data
                ];

                if (count($chunkData) >= self::CHUNK_SIZE) {
                    \App\Jobs\FillingOld\ProcessFilingChunkJob::dispatch(
                        $this->batchId, $fileName, $chunkData
                    )->onQueue($this->selectedQueue);
                    $chunkData = [];
                }
            }

            if (!empty($chunkData)) {
                \App\Jobs\FillingOld\ProcessFilingChunkJob::dispatch(
                    $this->batchId, $fileName, $chunkData
                )->onQueue($this->selectedQueue);
            }

            fclose($handle);
        }

        Log::info("Batch {$this->batchId}: Distribuido. CT Provider Code capturado.");
    }

    private function validateInternalFile(string $fileName, string $filePath): bool
    {
        $prefix = strtoupper(substr($fileName, 0, 2));
        $expected = self::$expectedColumns[$prefix] ?? null;
        if ($expected === null) return true;

        $handle = fopen($filePath, 'r');
        if (!$handle) return false;

        $row = 1;
        $isValid = true;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $actual = count($data);
            if ($actual === 1 && $data[0] === null) continue;

            if ($actual !== $expected) {
                ErrorCollector::addError($this->batchId, $row, 'COLUMN_MISMATCH',
                    "Archivo {$fileName}: Esperaba {$expected} col, tiene {$actual}", 'R', $data[0]??'', json_encode($data));
                $isValid = false;
                break;
            }
            $row++;
        }
        fclose($handle);
        return $isValid;
    }

    private function failJob(string $msg)
    {
        $count = ErrorCollector::countErrors($this->batchId);
        ErrorCollector::saveErrorsToDatabase($this->batchId, 'failed');
        Redis::connection('redis_6380')->hset("batch:{$this->batchId}:metadata", 'status', 'failed');
        event(new ImportProgressEvent($this->batchId, 100, $msg, $count, 'failed', 'Error'));
    }
}
