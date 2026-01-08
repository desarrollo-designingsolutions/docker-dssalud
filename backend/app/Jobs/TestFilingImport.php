<?php

namespace App\Jobs;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Jobs\ProcessFilingRecord; // <--- Importamos el nuevo Job hijo

class TestFilingImport implements ShouldQueue
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
        // 1. Configuración Inicial
        $redis = Redis::connection('redis_6380');
        $totalRows = 100; // Aquí simulas la cantidad de registros del ZIP

        // Inicializamos los contadores en Redis (ATÓMICO y SEGURO)
        $metadataKey = "batch:{$this->batchId}:metadata";
        $progressKey = "batch:{$this->batchId}:progress";

        // Aseguramos que el progreso arranque en 0
        $redis->set($progressKey, 0);

        // Actualizamos el total de filas en la metadata para que el Front sepa el 100%
        $redis->hset($metadataKey, 'total_rows', $totalRows);

        // 2. EL FAN-OUT (Repartir trabajo)
        // En lugar de hacer un bucle sleep aquí, despachamos 100 jobs pequeños.
        // Esto toma menos de 1 segundo en ejecutarse.
        for ($i = 1; $i <= $totalRows; $i++) {

            // Despachamos el job hijo a la MISMA COLA que seleccionaste
            ProcessFilingRecord::dispatch($this->batchId, $i, $this->selectedQueue)
                ->onQueue($this->selectedQueue);
        }

        // El Job Maestro termina aquí inmediatamente.
        // Ahora Supervisor verá 100 jobs en la cola y despertará a los 4 workers.
    }
}
