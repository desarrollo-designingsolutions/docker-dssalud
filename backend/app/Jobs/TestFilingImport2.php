<?php

namespace App\Jobs;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TestFilingImport2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;

    protected int $lockTtl = 30;

    protected string $selectedQueue;

    public function __construct(string $batchId, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        $lockKey = "validate_structure_lock:{$this->batchId}";
        $lock = Cache::lock($lockKey, $this->lockTtl);

        if (! $lock->get()) {
            return;
        }

        try {
            // 🔥 USAR REDIS PARA CONTROL CENTRALIZADO DEL PROGRESO
            $redis = Redis::connection('redis_6380');
            $progressKey = "batch:{$this->batchId}:progress";

            // Inicializar progreso solo si no existe
            if (! $redis->exists($progressKey)) {
                $redis->set($progressKey, 0);
            }

            // Emitir evento inicial
            event(new ImportProgressEvent(
                $this->batchId,
                0,
                'Iniciando validación CSV',
                ErrorCollector::countErrors($this->batchId),
                'active',
                'CSV 1'
            ));

            // Guardar total_rows en Redis para el progreso
            $metadataKey = "batch:{$this->batchId}:metadata";
            $metadata = $redis->hgetall($metadataKey);
            if (empty($metadata['total_rows'])) {
                $metadata['total_rows'] = 100;
                $redis->hmset($metadataKey, $metadata);
            }

            // 🔥 PROCESAR CON PROGRESO CENTRALIZADO
            for ($i = 1; $i <= 100; $i++) {
                // Solo un worker puede avanzar el progreso a la vez
                $progressLock = Cache::lock("progress_lock:{$this->batchId}", 5);

                if ($progressLock->get()) {
                    try {
                        // Incrementar progreso en Redis
                        $currentProgress = $redis->incr($progressKey);

                        // Emitir evento con progreso actualizado
                        event(new ImportProgressEvent(
                            $this->batchId,
                            $currentProgress,
                            'Validando CSV - Progreso '.$currentProgress.'/'.$metadata['total_rows'],
                            ErrorCollector::countErrors($this->batchId),
                            'active',
                            'CSV '.$currentProgress
                        ));
                    } finally {
                        $progressLock->release();
                    }
                }

                // Simular trabajo
                sleep(1);
            }

            // 🔥 VERIFICAR SI TODOS LOS TRABAJOS HAN TERMINADO
            $completedKey = "batch:{$this->batchId}:completed_jobs";
            $completed = $redis->incr($completedKey);
            $totalJobs = 1; // Ajustar según cuántos jobs esperas que se completen

            if ($completed >= $totalJobs) {
                // Solo emitir evento final cuando TODOS los jobs hayan terminado
                event(new ImportProgressEvent(
                    $this->batchId,
                    100,
                    'Validación CSV completada',
                    ErrorCollector::countErrors($this->batchId),
                    'completed',
                    'Finalizado'
                ));

                // Limpiar claves de Redis
                $redis->del([$progressKey, $completedKey]);
            }

        } catch (\Throwable $e) {
            Log::error('TestFilingImport error: '.$e->getMessage(), [
                'batchId' => $this->batchId,
                'exception' => $e,
            ]);

            // Marcar error en Redis
            $redis->hset("batch:{$this->batchId}:status", 'error', $e->getMessage());

        } finally {
            if (isset($lock) && method_exists($lock, 'release')) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    Log::debug('Error liberando lock: '.$e->getMessage());
                }
            }
        }
    }
}
