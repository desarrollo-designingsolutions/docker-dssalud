<?php

namespace App\Helpers\Common;

use App\Models\ProcessBatch;
use App\Models\ProcessBatchesError;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ErrorCollector
{
    /**
     * Agrega un error a la lista en Redis.
     */
    public static function addError(
        string $batchId,
        int $rowNumber,
        ?string $columnName,
        string $errorMessage,
        string $errorType,
        $errorValue,
        ?string $originalData
    ): void {
        $error = [
            'id' => Str::uuid(),
            'batch_id' => $batchId,
            'row_number' => $rowNumber,
            'column_name' => $columnName,
            'error_message' => $errorMessage,
            'error_type' => $errorType,
            'error_value' => is_null($errorValue) ? null : strval($errorValue),
            'original_data' => $originalData ?: null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        Redis::connection('redis_6380')->rpush("import_errors:{$batchId}", json_encode($error));
        Redis::connection('redis_6380')->expire("import_errors:{$batchId}", 3600);
    }

    /**
     * Devuelve todos los errores recolectados.
     */
    public static function getErrors(string $batchId): array
    {
        $rawErrors = Redis::connection('redis_6380')->lrange("import_errors:{$batchId}", 0, -1);
        $errors = [];
        foreach ($rawErrors as $errorJson) {
            $errors[] = json_decode($errorJson, true);
        }

        return $errors;
    }

    /**
     * Devuelve la cantidad de errores recolectados.
     */
    public static function countErrors(string $batchId): int
    {
        return (int) Redis::connection('redis_6380')->llen("import_errors:{$batchId}");
    }

    /**
     * Limpia la lista de errores en Redis.
     */
    public static function clear(string $batchId): void
    {
        Redis::connection('redis_6380')->del("import_errors:{$batchId}");
    }

   /**
     * Guarda los errores en la base de datos de manera eficiente con la memoria.
     */
    public static function saveErrorsToDatabase(string $batchId, string $status = 'failed'): void
    {
        $redis = Redis::connection('redis_6380');
        $errorKey = "import_errors:{$batchId}";

        // 1. Obtenemos el total de errores en Redis
        $totalErrors = (int) $redis->llen($errorKey);

        // Metadatos
        $metadata = $redis->hgetall("batch:{$batchId}:metadata");
        $metadata['completed_at'] = now()->toDateTimeString();

        // Si no hay errores, cerramos limpio
        if ($totalErrors === 0) {
            ProcessBatch::where('batch_id', $batchId)->update([
                'error_count' => 0,
                'status' => 'completed',
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);
            $redis->hmset("batch:{$batchId}:metadata", $metadata);
            self::clear($batchId);
            return;
        }

        // 2. PROCESAMIENTO POR LOTES (Sin saturar RAM)
        // En lugar de lrange(0, -1), sacamos bloques de Redis
        $batchSize = 500;

        // Iteramos sacando de a 500 errores desde el inicio de la lista
        // lrange no borra, así que usamos un puntero o simplemente lpop si quisiéramos borrar al vuelo.
        // Para seguridad (por si falla la DB), usamos lrange por paginación.

        for ($i = 0; $i < $totalErrors; $i += $batchSize) {
            // Traemos solo 500 elementos
            $rawErrors = $redis->lrange($errorKey, $i, $i + $batchSize - 1);

            if (empty($rawErrors)) break;

            $chunkData = [];
            foreach ($rawErrors as $errorJson) {
                $error = json_decode($errorJson, true);
                $chunkData[] = [
                    'id' => Str::uuid(),
                    'batch_id' => $batchId,
                    'row_number' => $error['row_number'] ?? null,
                    'column_name' => $error['column_name'] ?? null,
                    'error_message' => $error['error_message'],
                    'error_type' => $error['error_type'] ?? 'R',
                    'error_value' => $error['error_value'] ?? null,
                    'original_data' => isset($error['original_data']) ? json_encode(['data' => $error['original_data']]) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insertamos este lote en BD
            try {
                ProcessBatchesError::insert($chunkData);
            } catch (\Exception $e) {
                Log::error("Error insertando chunk errores batch {$batchId}: " . $e->getMessage());
            }

            // Liberamos memoria de este ciclo
            unset($rawErrors, $chunkData);
        }

        // 3. Actualizar estado final
        ProcessBatch::where('batch_id', $batchId)->update([
            'error_count' => $totalErrors,
            'status' => $status, // 'failed' o 'completed_with_errors'
            'metadata' => json_encode($metadata),
            'updated_at' => now(),
        ]);

        $redis->hmset("batch:{$batchId}:metadata", $metadata);

        // Limpiamos Redis al final
        self::clear($batchId);
    }
}
