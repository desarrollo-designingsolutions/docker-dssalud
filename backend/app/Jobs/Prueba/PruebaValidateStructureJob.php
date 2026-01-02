<?php

namespace App\Jobs\Prueba;

use App\Events\ImportProgressEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class PruebaValidateStructureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    protected int $lockTtl = 30;
    protected string $selectedQueue;

    public function __construct(string $batchId, string $selectedQueue)
    {
        $this->batchId = $batchId;
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue); // ✅ Asignar la cola
    }

    public function handle()
    {

        event(new ImportProgressEvent($this->batchId, 0, 'Iniciando importacion', 0, 'active', 'CSV'));


        sleep(20); // Simula tiempo de procesamiento



         event(new ImportProgressEvent($this->batchId, 0, 'finalizacndo importacion', 0, 'completed', 'CSV'));



        try {
        } catch (\Throwable $e) {
        } finally {
        }
    }
}
