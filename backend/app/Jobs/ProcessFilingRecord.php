<?php

namespace App\Jobs;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessFilingRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;

    public int $rowNumber;

    public string $selectedQueue;

    public function __construct(string $batchId, int $rowNumber, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->rowNumber = $rowNumber;
        $this->selectedQueue = $selectedQueue;
    }

    public function handle()
    {
        try {
            // 1. Simular trabajo pesado (Validación, DB, etc.)
            // Aquí es donde realmente "gastas" tiempo.
            // Al tener 4 workers, se harán 4 sleeps simultáneos.
            sleep(1);

            // 2. Actualizar Progreso (LA CLAVE DEL ÉXITO)
            $redis = Redis::connection('redis_6380');
            $progressKey = "batch:{$this->batchId}:progress";

            // INCR es una operación atómica de Redis.
            // No necesitas Cache::lock. Redis se encarga de que si 4 workers
            // llegan a la vez, sume 1, 2, 3, 4 correctamente.
            $currentProgress = $redis->incr($progressKey);

            // Obtenemos el total para saber si acabamos
            $metadataKey = "batch:{$this->batchId}:metadata";
            $totalRows = (int) $redis->hget($metadataKey, 'total_rows');

            // 3. Emitir evento al Front
            // Enviamos el progreso real calculado por Redis
            event(new ImportProgressEvent(
                $this->batchId,
                $currentProgress, // Progreso actual (ej: 45)
                "Procesando registro {$this->rowNumber}",
                ErrorCollector::countErrors($this->batchId), // Tus errores actuales
                'active',
                "Fila {$this->rowNumber}"
            ));

            // 4. Verificar si soy el ÚLTIMO job en terminar
            if ($currentProgress >= $totalRows) {
                $this->finishBatch($redis);
            }

        } catch (\Throwable $e) {
            Log::error("Error en fila {$this->rowNumber}: ".$e->getMessage());
            // Opcional: Registrar error en tu ErrorCollector
        }
    }

    protected function finishBatch($redis)
    {
        // Lógica de finalización
        // Emitimos el evento final 100%
        event(new ImportProgressEvent(
            $this->batchId,
            100, // Forzamos 100%
            'Validación Completada Exitosamente',
            ErrorCollector::countErrors($this->batchId),
            'completed',
            'Finalizado'
        ));

        // Limpiamos Redis o actualizamos estado general
        $redis->hmset("rip_batch:{$this->batchId}", ['status' => 'completed']);

        // Limpiamos keys temporales si quieres
        $redis->del("batch:{$this->batchId}:progress");
    }
}
