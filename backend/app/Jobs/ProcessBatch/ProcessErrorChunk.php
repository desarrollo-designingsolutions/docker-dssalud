<?php

namespace App\Jobs\ProcessBatch;

use App\Models\ProcessBatchesError;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessErrorChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $customBatchId;

    protected string $processKey;

    protected int $offset;

    protected int $limit;

    protected string $userId;

    public function __construct(string $customBatchId, string $processKey, int $offset, int $limit, string $userId)
    {
        $this->customBatchId = $customBatchId;
        $this->processKey = $processKey;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->userId = $userId;
        $this->onQueue('download_files');
    }

    public function handle(): void
    {
        try {
            $errors = ProcessBatchesError::where('batch_id', $this->customBatchId)
                ->skip($this->offset)
                ->take($this->limit)
                ->get();

            $rowsKey = $this->processKey.':rows';

            foreach ($errors as $error) {
                // ✅ CAMBIO: Guardamos un ARRAY estructurado convertido a JSON
                // Esto permite que GenerateErrorCsv lo lea correctamente.
                $data = [
                    'row_number' => $error->row_number,
                    'column_name' => $error->column_name,
                    'error_message' => $error->error_message,
                    'error_type' => $error->error_type,
                    'error_value' => $error->error_value,
                    'original_data' => $error->original_data, // Necesario para sacar el nombre del archivo
                ];

                // Guardar JSON en Redis
                Redis::rpush($rowsKey, json_encode($data));
            }

            // Actualizar progreso
            Redis::hincrby($this->processKey, 'processed', $errors->count());

        } catch (\Exception $e) {
            Log::error("Error en ProcessErrorChunk: {$e->getMessage()}");
            // Notificar error...
            throw $e;
        }
    }
}
