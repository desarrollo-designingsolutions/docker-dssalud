<?php

namespace App\Jobs\FillingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes; // <--- Namespace correcto
use App\Events\ImportProgressEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ValidateFilingZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    protected string $selectedQueue;

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
        $fullPath = $metadata['full_path'] ?? null;
        if (!$fullPath && isset($metadata['path_zip'])) {
            $fullPath = storage_path('app/public/' . $metadata['path_zip']);
        }

        event(new ImportProgressEvent(
            $this->batchId, 0, 'Validando estructura ZIP...', 0, 'active', 'ZIP Validation'
        ));

        // 1. VALIDACIONES FÍSICAS
        if (!$fullPath || !file_exists($fullPath)) {
            $this->failJobAndStop('ZIP_CRITICAL_001'); // Archivo no existe
            return;
        }

        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'zip') {
            $this->failJobAndStop('ZIP_CRITICAL_002'); // No es .zip
            return;
        }

        $zip = new ZipArchive;
        $res = $zip->open($fullPath);

        if ($res !== true) {
            $this->failJobAndStop('ZIP_CRITICAL_003'); // Corrupto o error de lectura
            return;
        }

        // 2. ANÁLISIS DE CONTENIDO
        $rawFileCount = $zip->numFiles;
        $validFileNames = [];
        $hasFolders = false;

        for ($i = 0; $i < $rawFileCount; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr($name, -1) === '/') {
                $hasFolders = true;
                break;
            }
            $basename = basename($name);
            if (str_starts_with($name, '__MACOSX') || str_starts_with($basename, '.')) {
                continue;
            }
            $validFileNames[] = $name;
        }

        // Validación: Carpetas
        if ($hasFolders) {
            $zip->close();
            $this->failJobAndStop('ZIP_CONTENT_001');
            return;
        }

        $cleanCount = count($validFileNames);

        // Validación: Cantidad Máxima
        if ($cleanCount > 10) {
            $zip->close();
            $this->failJobAndStop('ZIP_CONTENT_002', $cleanCount); // Pasa argumento %d
            return;
        }
        // Validación: Cantidad Mínima
        if ($cleanCount < 4) {
            $zip->close();
            $this->failJobAndStop('ZIP_CONTENT_003', $cleanCount); // Pasa argumento %d
            return;
        }

        // 3. REGLAS DE NEGOCIO (PREFIJOS)
        $hasAF = false;
        $hasUS = false;
        $hasDetail = false;
        $missingErrors = []; // Guardaremos las constantes de error aquí

        foreach ($validFileNames as $name) {
            $prefix = strtoupper(substr(basename($name), 0, 2));
            if ($prefix === 'AF') $hasAF = true;
            if ($prefix === 'US') $hasUS = true;
            if (in_array($prefix, ['AC', 'AP', 'AM', 'AT'])) $hasDetail = true;
        }

        if (!$hasAF) $missingErrors[] = 'ZIP_MISSING_AF';
        if (!$hasUS) $missingErrors[] = 'ZIP_MISSING_US';
        if (!$hasDetail) $missingErrors[] = 'ZIP_MISSING_DETAIL';

        if (count($missingErrors) > 0) {
            $zip->close();
            foreach ($missingErrors as $codeConst) {
                // Usamos ErrorCodes para obtener mensaje y código
                ErrorCollector::addError(
                    $this->batchId,
                    0,
                    'ZIP_CONTENT',
                    ErrorCodes::getMessage($codeConst),
                    'R',
                    ErrorCodes::getCode($codeConst),
                    null
                );
            }
            $this->finalizeAsFailed(count($missingErrors) . " archivos requeridos faltantes.");
            return;
        }

        $zip->close();

        // 4. ÉXITO
        event(new ImportProgressEvent($this->batchId, 1, 'ZIP OK', 0, 'active', 'ZIP OK'));

        $redis->hmset($redisKey, [
            'status' => 'zip_validated',
            'total_rows' => $cleanCount,
            'processed_records' => 0,
            'total_files_in_zip' => $cleanCount,
            'file_list' => json_encode($validFileNames)
        ]);

        Log::info("Batch {$this->batchId}: ZIP validado. {$cleanCount} archivos.");

        event(new ImportProgressEvent($this->batchId, 0, "Extrayendo...", 0, 'active', 'Phase 2 Start'));

        DistributeFilingFilesJob::dispatch($this->batchId, $this->selectedQueue)
             ->onQueue($this->selectedQueue);
    }

    /**
     * Helper para fallos críticos usando ErrorCodes
     */
    private function failJobAndStop(string $errorCodeConstant, ...$args)
    {
        $msg = ErrorCodes::getMessage($errorCodeConstant, ...$args);
        $code = ErrorCodes::getCode($errorCodeConstant);

        ErrorCollector::addError(
            $this->batchId,
            0,
            'ZIP_CRITICAL',
            $msg,
            'R',
            $code,
            null
        );
        $this->finalizeAsFailed($msg);
    }

    private function finalizeAsFailed(string $logMessage)
    {
        $errorCount = ErrorCollector::countErrors($this->batchId);
        ErrorCollector::saveErrorsToDatabase($this->batchId, 'failed');
        Redis::connection('redis_6380')->hset("batch:{$this->batchId}:metadata", 'status', 'failed');

        event(new ImportProgressEvent(
            $this->batchId,
            1,
            $logMessage,
            $errorCount,
            'failed',
            'Validation Failed'
        ));

        Log::error("Batch {$this->batchId} Failed: $logMessage");
    }
}
