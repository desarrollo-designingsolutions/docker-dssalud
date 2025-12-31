<?php

namespace App\Jobs\Filing;
use App\Helpers\FilingOld\InternalFileValidator;
use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ZipValidator;
use App\Models\ProcessBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Helpers\FilingOld\FormatDataTxt;
use App\Helpers\Constants;
use App\Helpers\FilingOld\ACFileValidator;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ValidateZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;
    public $batchId;
    public $userId;
    public $companyId;

    public function __construct(string $filePath, string $batchId, string $userId, string $companyId, string $selectedQueue)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->userId = $userId;
        $this->companyId = $companyId;
        $this->onQueue($selectedQueue);
    }

    public function handle(): void
    {
        $redis = Redis::connection('redis_6380');

        ErrorCollector::clear($this->batchId);
        event(new ImportProgressEvent($this->batchId, 0, 'Iniciando validación ZIP', 0, 'active', 'ZIP'));

        try {
            $zip = new ZipArchive();
            $numFiles = 0;
            if ($zip->open($this->filePath) === true) {
                $numFiles = $zip->numFiles;

                $tempDir = 'temp/filings/zip/' . $this->batchId;
                Storage::disk('public')->makeDirectory($tempDir);
                $basePath = storage_path('app/public/' . $tempDir);
            }

            // Pruebas borrar
            // $archivos = [];
            // $totalRows = 0;
            // for ($i = 0; $i < $zip->numFiles; $i++) {
            //     $name = $zip->getNameIndex($i);
            //     if (substr($name, -1) === '/') {
            //         continue; // Saltar carpetas
            //     }
            //     $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            //     if ($ext !== 'txt') {
            //         continue; // Saltar no-TXT
            //     }

            //     $zip->extractTo($basePath, $name);

            //     // Usar el nombre completo con subdirectorios si existen
            //     $filename = basename($name);
            //     $rutaTemporal = $tempDir . '/' . $name; // Mantener estructura de subdirectorios
            //     $fullTempPath = storage_path('app/public/' . $rutaTemporal);

            //     // Debug: Log para verificar rutas
            //     Log::info("Extrayendo archivo ZIP", [
            //         'name' => $name,
            //         'filename' => $filename,
            //         'rutaTemporal' => $rutaTemporal,
            //         'fullTempPath' => $fullTempPath,
            //         'exists' => file_exists($fullTempPath)
            //     ]);

            //     // Contar filas del archivo extraído
            //     $contenido = file_get_contents($fullTempPath);
            //     $encoding = mb_detect_encoding($contenido, 'UTF-8, ISO-8859-1', true);
            //     if ($encoding !== 'UTF-8') {
            //         $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
            //     }

            //     $contentDataArray = FormatDataTxt::execute($contenido);
            //     $countRows = count($contentDataArray);
            //     $totalRows += $countRows;

            //     $archivos[] = [
            //         'name' => $filename,
            //         'extension' => $ext,
            //         'rutaTemporal' => $rutaTemporal,
            //         'fullTempPath' => $fullTempPath, // Ruta absoluta para operaciones de archivo
            //         'contentData' => $contenido,
            //         'contentDataArray' => $contentDataArray,
            //         'count_rows' => $countRows,
            //         'type' => substr($filename, 0, 2),
            //     ];
            // }

            // foreach ($archivos as $file) {
            //     $prefix = strtoupper(substr($file['name'], 0, 2));
            //     $redis->set("rip_batch:{$this->batchId}:{$prefix}", json_encode($file));
            //     $redis->expire("rip_batch:{$this->batchId}:{$prefix}", 86400);

            //     $chunks = array_chunk($file['contentDataArray'], Constants::CHUNKSIZE);

            //     foreach ($chunks as $index => $chunk) {
            //         $startRow = ($index * Constants::CHUNKSIZE) + 1;

            //         if ($prefix === 'AC') {
            //             ACFileValidator::validate($file['name'], $chunk, $startRow, $this->batchId);
            //         }
            //     }
            // }

            $metadata = $redis->hgetall("batch:{$this->batchId}:metadata");
            $metadata['total_rows'] = $numFiles;
            $redis->hmset("batch:{$this->batchId}:metadata", $metadata);
            ProcessBatch::where('batch_id', $this->batchId)->update([
                'total_records' => $numFiles,
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);

            //Validamos el archivo ZIP y los archivos TXT dentro de él (ESTRUCTURA)
            $validationResult = ZipValidator::validate($this->filePath, $this->batchId);
            event(new ImportProgressEvent($this->batchId, 0, 'Terminando validación ZIP', 0, 'active', 'ZIP'));


            if ($validationResult) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $fileName = $zip->getNameIndex($i);
                    if (substr($fileName, -1) !== '/') {
                        $filePath = $tempDir . '/' . $fileName;
                        $fullFilePath = storage_path('app/public/' . $filePath); // Ruta absoluta
                        InternalFileValidator::validate($this->batchId, $fullFilePath);
                    }
                }
            }

            $zip->close();



            // Obtener errores recolectados
            $errors = ErrorCollector::getErrors($this->batchId);
            $errorCount = count($errors);

            // Log::info("Validación ZIP completada para batch {$this->batchId}", ['error_count' => $errorCount]);
            event(new ImportProgressEvent($this->batchId, $numFiles, 'Validación ZIP completada', $errorCount, "active", 'ZIP'));

            $redis->hmset("rip_batch:{$this->batchId}", [
                'status' => 'zip_validated',
            ]);
            $redis->expire("rip_batch:{$this->batchId}", 86400);
        } catch (\Exception $e) {
            Log::error("Excepción en validación ZIP para batch {$this->batchId}: {$e->getMessage()}");

            $errors = ErrorCollector::getErrors($this->batchId);
            $errorCount = count($errors);

            event(new ImportProgressEvent($this->batchId, 0, 'Error inesperado en ZIP', $errorCount, 'failed', 'ZIP'));

            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }

            $this->fail($e);
        }
    }
}
