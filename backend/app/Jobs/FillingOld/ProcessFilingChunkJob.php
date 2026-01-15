<?php

namespace App\Jobs\FillingOld;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector; // <--- Namespace correcto
use App\Helpers\FilingOld\ACFileValidator;
use App\Helpers\FilingOld\AFFileValidator;
use App\Helpers\FilingOld\AHFileValidator;
use App\Helpers\FilingOld\AMFileValidator;
use App\Helpers\FilingOld\ANFileValidator;
use App\Helpers\FilingOld\APFileValidator;
use App\Helpers\FilingOld\ATFileValidator;
use App\Helpers\FilingOld\AUFileValidator;
// IMPORTA TUS VALIDADORES AQUÍ
use App\Helpers\FilingOld\CTFileValidator;
use App\Helpers\FilingOld\ErrorCodes;
use App\Helpers\FilingOld\USFileValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessFilingChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;

    public string $fileName;

    public array $chunkData;

    public function __construct(string $batchId, string $fileName, array $chunkData)
    {
        $this->batchId = $batchId;
        $this->fileName = $fileName;
        $this->chunkData = $chunkData;
    }

    public function handle()
    {
        // 🐢 Sleep opcional
        sleep(1);

        $prefix = strtoupper(substr($this->fileName, 0, 2));

        // 1. Procesar validación fila por fila
        foreach ($this->chunkData as $item) {
            $rowNum = $item['row_number'];
            $dataRow = $item['data'];

            try {
                $this->validateRowByType($prefix, $this->fileName, $dataRow, $rowNum);
            } catch (\Throwable $e) {
                // Captura de errores críticos que no sean de validación lógica
                // USO DE ERRORCODES para excepción de fila
                ErrorCollector::addError(
                    $this->batchId,
                    $rowNum,
                    'ROW_EXCEPTION',
                    ErrorCodes::getMessage('ROW_EXCEPTION', $e->getMessage()),
                    'R',
                    ErrorCodes::getCode('ROW_EXCEPTION'),
                    json_encode($dataRow)
                );
            }
        }

        // =================================================================
        // 2. LÓGICA DEL CIERRE ATÓMICO ("ÚLTIMO HOMBRE EN PIE")
        // =================================================================
        $redis = Redis::connection('redis_6380');
        $redisKeyMeta = "batch:{$this->batchId}:metadata";

        $processedInChunk = count($this->chunkData);

        // Incremento ATÓMICO.
        $newGlobalTotal = $redis->hincrby($redisKeyMeta, 'processed_records', $processedInChunk);

        // Obtenemos el Gran Total (Calculado en Fase 2)
        $grandTotal = (int) $redis->hget($redisKeyMeta, 'total_rows');
        $currentErrors = ErrorCollector::countErrors($this->batchId);

        // VALIDACIÓN DE CIERRE
        if ($newGlobalTotal >= $grandTotal) {

            // 🛑 SOY EL ÚLTIMO
            $finalStatus = ($currentErrors > 0) ? 'failed' : 'completed';

            // 1. Guardar en Base de Datos
            ErrorCollector::saveErrorsToDatabase($this->batchId, $finalStatus);

            // 2. Actualizar Metadata final en Redis
            $redis->hmset($redisKeyMeta, [
                'status' => $finalStatus,
                'progress' => 100,
            ]);

            // 3. Evento Final (100%)
            event(new ImportProgressEvent(
                $this->batchId,
                $grandTotal,
                'Proceso Finalizado. Registros validados.',
                $currentErrors,
                $finalStatus,
                'Validación completada.'
            ));

            Log::info("Batch {$this->batchId} finalizado correctamente por Worker.");

        } else {

            // 🏃 NO SOY EL ÚLTIMO
            $msgElement = "Procesando registros ({$newGlobalTotal} / {$grandTotal})";

            event(new ImportProgressEvent(
                $this->batchId,
                $newGlobalTotal,
                'Validando información...',
                $currentErrors,
                'active',
                $msgElement
            ));
        }
    }

    private function validateRowByType(string $prefix, string $fileName, array $dataRow, int $rowNum)
    {
        // Convertir array a CSV string para compatibilidad
        $rawLine = implode(',', $dataRow);

        switch ($prefix) {
            case 'CT': CTFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'US': USFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AF': AFFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AC': ACFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AP': APFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AU': AUFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AH': AHFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AN': ANFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AM': AMFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            case 'AT': ATFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId);
                break;
            default: break;
        }
    }
}
