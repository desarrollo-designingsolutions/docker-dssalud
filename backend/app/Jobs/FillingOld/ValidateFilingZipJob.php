<?php

namespace App\Jobs\FillingOld;

use App\Helpers\Common\ErrorCollector;
use App\Events\ImportProgressEvent;
use App\Jobs\FillingOld\DistributeFilingFilesJob;
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

        // 1. Recuperar información
        $metadata = $redis->hgetall($redisKey);

        // Aseguramos ruta absoluta
        $fullPath = $metadata['full_path'] ?? null;
        if (!$fullPath && isset($metadata['path_zip'])) {
            $fullPath = storage_path('app/public/' . $metadata['path_zip']);
        }

        // --- INICIO FASE 1: Notificar al Front (0 de 1 ZIP procesados) ---
        event(new ImportProgressEvent(
            $this->batchId,
            0, // processedRecords = 0
            'Validando estructura e integridad del archivo ZIP...',
            0,
            'active',
            'ZIP Validation'
        ));

        // ========================================================================
        // 🛑 PASO 1: VALIDACIONES FÍSICAS (BLOQUEANTES)
        // ========================================================================

        if (!$fullPath || !file_exists($fullPath)) {
            $this->failJobAndStop("El archivo ZIP no existe en el servidor.");
            return;
        }

        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'zip') {
            $this->failJobAndStop("El archivo subido no tiene extensión .zip.");
            return;
        }

        $zip = new ZipArchive;
        $res = $zip->open($fullPath);

        if ($res !== true) {
            $msg = match ($res) {
                ZipArchive::ER_NOZIP => 'El archivo no es un ZIP válido.',
                ZipArchive::ER_INCONS => 'El archivo ZIP está corrupto.',
                ZipArchive::ER_CRC => 'Error de integridad (CRC).',
                default => "Error crítico al abrir ZIP. Código: $res",
            };
            $this->failJobAndStop($msg);
            return;
        }

        // ========================================================================
        // 🛑 PASO 2: ANÁLISIS DE CONTENIDO (ESTRUCTURA)
        // ========================================================================

        $rawFileCount = $zip->numFiles;
        $validFileNames = [];
        $hasFolders = false;

        for ($i = 0; $i < $rawFileCount; $i++) {
            $name = $zip->getNameIndex($i);

            // 1. Detectar carpetas (terminan en /)
            if (substr($name, -1) === '/') {
                $hasFolders = true;
                break;
            }

            // 2. Ignorar basura de sistema (__MACOSX, .DS_Store)
            $basename = basename($name);
            if (str_starts_with($name, '__MACOSX') || str_starts_with($basename, '.')) {
                continue;
            }

            $validFileNames[] = $name;
        }

        // Validación: No carpetas
        if ($hasFolders) {
            $zip->close();
            $this->failJobAndStop("El ZIP contiene carpetas. Solo se permiten archivos en la raíz.");
            return;
        }

        $cleanCount = count($validFileNames);

        // Validación: Cantidad (Min 4 - Max 10)
        if ($cleanCount > 10) {
            $zip->close();
            $this->failJobAndStop("El ZIP contiene {$cleanCount} archivos. El máximo permitido es 10.");
            return;
        }
        if ($cleanCount < 4) {
            $zip->close();
            $this->failJobAndStop("El ZIP contiene solo {$cleanCount} archivos. El mínimo requerido es 4.");
            return;
        }

        // ========================================================================
        // 🛑 PASO 3: REGLAS DE NEGOCIO (PREFIJOS REQUERIDOS)
        // ========================================================================

        $hasAF = false;
        $hasUS = false;
        $hasAC_AP_AM_AT = false;
        $missingErrors = [];

        foreach ($validFileNames as $name) {
            $prefix = strtoupper(substr(basename($name), 0, 2));

            if ($prefix === 'AF') $hasAF = true;
            if ($prefix === 'US') $hasUS = true;
            if ($prefix === 'CT') { /* Opcional según lógica */
            }
            if (in_array($prefix, ['AC', 'AP', 'AM', 'AT'])) $hasAC_AP_AM_AT = true;
        }

        if (!$hasAF) $missingErrors[] = "Falta el archivo de transacciones (AF).";
        if (!$hasUS) $missingErrors[] = "Falta el archivo de usuarios (US).";
        if (!$hasAC_AP_AM_AT) $missingErrors[] = "Falta al menos un archivo de detalle (AC, AP, AM o AT).";

        if (count($missingErrors) > 0) {
            $zip->close();
            // Registramos todos los errores acumulados
            foreach ($missingErrors as $errorMsg) {
                ErrorCollector::addError($this->batchId, 0, 'ZIP_CONTENT', $errorMsg, 'R', 'FILE_MISSING', null);
            }
            // Cerramos como fallido
            $this->finalizeAsFailed(count($missingErrors) . " archivos requeridos faltantes.");
            return;
        }

        $zip->close();

        // ========================================================================
        // ✅ ÉXITO FASE 1 -> TRANSICIÓN A FASE 2
        // ========================================================================

        // 1. Notificar Éxito de Fase 1 (1 de 1 completado)
        event(new ImportProgressEvent(
            $this->batchId,
            1, // processedRecords = 1 (100% de la fase actual)
            'Validación ZIP completada exitosamente.',
            0,
            'active',
            'ZIP OK'
        ));

        // 2. PREPARAR REDIS PARA FASE 2
        // Cambiamos el "Universo" de trabajo: Ahora son N archivos internos
        $redis->hmset($redisKey, [
            'status' => 'zip_validated',
            'total_rows' => $cleanCount,    // <--- NUEVO TOTAL PARA FASE 2
            'processed_records' => 0,       // <--- REINICIAMOS CONTADOR
            'total_files_in_zip' => $cleanCount,
            'file_list' => json_encode($validFileNames)
        ]);

        Log::info("Batch {$this->batchId}: ZIP validado. Contiene {$cleanCount} archivos. Iniciando Fase 2.");

        // 3. Notificar inicio visual de Fase 2 (0 de N)
        event(new ImportProgressEvent(
            $this->batchId,
            0, // 0 de 8 (ejemplo)
            "Preparando extracción de {$cleanCount} archivos...",
            0,
            'active',
            'Phase 2 Start'
        ));

        // ------------------------------------------------------------------------
        // 🚀 DESPACHAR SIGUIENTE JOB (Fase 2)
        // ------------------------------------------------------------------------
        DistributeFilingFilesJob::dispatch($this->batchId, $this->selectedQueue)
             ->onQueue($this->selectedQueue);
    }

    /**
     * Helper para fallos críticos (Error único bloqueante)
     */
    private function failJobAndStop(string $message)
    {
        ErrorCollector::addError(
            $this->batchId,
            0,
            'ZIP_CRITICAL',
            $message,
            'R',
            'BLOCKING_ERROR',
            null
        );
        $this->finalizeAsFailed($message);
    }

    /**
     * Finaliza el proceso en BD y avisa al front (100% Failed)
     */
    private function finalizeAsFailed(string $logMessage)
    {
        // 1. CAPTURAR: Contamos los errores ANTES de que se borren de Redis
        $errorCount = ErrorCollector::countErrors($this->batchId);

        // 2. GUARDAR: Esto persiste en MySQL y luego LIMPIA Redis
        ErrorCollector::saveErrorsToDatabase($this->batchId, 'failed');

        // 3. ACTUALIZAR: Marcamos el status final en la metadata
        Redis::connection('redis_6380')->hset("batch:{$this->batchId}:metadata", 'status', 'failed');

        // 4. ENVIAR: Usamos la variable $errorCount que capturamos al inicio
        event(new ImportProgressEvent(
            $this->batchId,
            1, // 1 de 1 completado (Fail)
            $logMessage,
            $errorCount, // <--- AQUÍ USAMOS LA VARIABLE CAPTURADA
            'failed',
            'Validation Failed'
        ));

        Log::error("Batch {$this->batchId} Failed: $logMessage");
    }
}
