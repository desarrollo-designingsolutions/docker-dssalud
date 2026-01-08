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

// IMPORTA TUS VALIDADORES AQUÍ
use App\Helpers\FilingOld\ACFileValidator;
use App\Helpers\FilingOld\AFFileValidator;
use App\Helpers\FilingOld\AHFileValidator;
use App\Helpers\FilingOld\AMFileValidator;
use App\Helpers\FilingOld\ANFileValidator;
use App\Helpers\FilingOld\APFileValidator;
use App\Helpers\FilingOld\ATFileValidator;
use App\Helpers\FilingOld\AUFileValidator;
use App\Helpers\FilingOld\CTFileValidator;
use App\Helpers\FilingOld\USFileValidator;

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

        $prefix = strtoupper(substr($this->fileName, 0, 2));

        // 1. Procesar validación fila por fila
        foreach ($this->chunkData as $item) {
            $rowNum = $item['row_number'];
            $dataRow = $item['data'];

            try {
                $this->validateRowByType($prefix, $this->fileName, $dataRow, $rowNum);
            } catch (\Throwable $e) {
                ErrorCollector::addError(
                    $this->batchId, $rowNum, 'ROW_EXCEPTION',
                    $e->getMessage(), 'R', basename($this->fileName), json_encode($dataRow)
                );
            }
        }

        // 2. Actualizar Progreso GLOBAL en Redis
        $redis = Redis::connection('redis_6380');
        $redisKeyMeta = "batch:{$this->batchId}:metadata";

        $processedInChunk = count($this->chunkData);

        // Sumar al acumulado
        $newGlobalTotal = $redis->hincrby($redisKeyMeta, 'processed_records', $processedInChunk);

        // Obtener el Gran Total (Calculado en Fase 2)
        $grandTotal = $redis->hget($redisKeyMeta, 'total_rows');

        // 3. Emitir Evento con Mensaje Global
        // Mensaje: "Procesando registros (150 / 5000)"
        $msgElement = "Procesando registros ({$newGlobalTotal} / {$grandTotal})";

        event(new ImportProgressEvent(
            $this->batchId,
            $newGlobalTotal,
            "Validando información...", // Acción General
            ErrorCollector::countErrors($this->batchId),
            'active',
            $msgElement // Detalle numérico
        ));
    }

    private function validateRowByType(string $prefix, string $fileName, array $dataRow, int $rowNum)
    {
        // Convertir array a CSV string para compatibilidad con tus validadores actuales
        $rawLine = implode(',', $dataRow);

        switch ($prefix) {
            case 'CT': CTFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'US': USFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AF': AFFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AC': ACFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AP': APFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AU': AUFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AH': AHFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AN': ANFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AM': AMFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            case 'AT': ATFileValidator::validate($fileName, $rawLine, $rowNum, $this->batchId); break;
            default: break;
        }
    }
}
