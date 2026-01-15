<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProcessBatchesError\ProcessBatchesErrorPaginateResource;
use App\Jobs\ProcessBatch\CsvReportErrors;
use App\Jobs\ProcessBatch\ExcelReportData;
use App\Models\ProcessBatch;
use App\Repositories\ProcessBatchesErrorRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ProcessBatchController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected ProcessBatchesErrorRepository $processBatchesErrorRepository,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->processBatchesErrorRepository->paginate($request->all());
            $tableData = ProcessBatchesErrorPaginateResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function getUserProcesses(Request $request, $id)
    {
        $processes = ProcessBatch::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($batch) {
                $metadata = json_decode($batch->metadata, true);

                $progress = $batch->total_records > 0 ? ($batch->total_records / $batch->total_records) * 100 : 0;
                if ($batch->status == 'completed' || $batch->status == 'failed') { // Asegurar 100% para completados/fallidos
                    $progress = 100;
                }

                // Determinar current_action basado en el estado
                $currentAction = 'Carga inicial';
                if ($batch->status === 'active') {
                    $currentAction = 'Procesando datos';
                } elseif ($batch->status === 'queued') {
                    $currentAction = 'En cola de espera';
                } elseif ($batch->status === 'completed') {
                    $currentAction = 'Importación finalizada';
                } elseif ($batch->status === 'failed') {
                    $currentAction = 'Importación fallida';
                }

                // Mapear el estado del backend al estado esperado por el frontend
                $frontendStatus = $this->mapBackendStatusToFrontend($batch->status);

                return [
                    'batch_id' => $batch->batch_id,
                    'progress' => round($progress, 2),
                    'current_element' => $metadata['processed_records'] ?? 0, // Mapear processed_records a current_element
                    'current_action' => $currentAction, // Establecer acción apropiada
                    'status' => $frontendStatus, // Usar el estado mapeado
                    'started_at' => $batch->created_at?->toIso8601String(),
                    // completed_at debe establecerse para los estados 'completed' y 'failed'
                    'completed_at' => in_array($batch->status, ['completed', 'failed']) ? $batch->updated_at?->toIso8601String() : null,
                    'metadata' => [
                        'total_records' => $batch->total_records,
                        'processed_records' => $metadata['processed_records'] ?? 0,
                        'errors_count' => $batch->error_count,
                        'processing_start_time' => $batch->created_at?->toIso8601String(),
                        'connection_status' => 'disconnected', // Siempre desconectado para carga histórica
                        // Añadir otros campos de metadata si son necesarios por el frontend
                        'file_size' => $metadata['file_size'] ?? 0, // Asumiendo que file_size está en metadata
                        'file_name' => $metadata ? $metadata['file_name'] : 'Archivo desconocido',
                        'current_sheet' => 1, // Valor por defecto para histórico
                        'total_sheets' => 1, // Valor por defecto para histórico
                        'warnings_count' => 0, // Valor por defecto para histórico
                        'processing_speed' => 0, // Valor por defecto para histórico
                        'estimated_time_remaining' => 0, // Valor por defecto para histórico
                    ],
                ];
            });

        return response()->json(['processes' => $processes], 200);
    }

    // Helper function to map backend status to frontend status
    private function mapBackendStatusToFrontend(string $backendStatus): string
    {
        return match ($backendStatus) {
            'active', 'finalizing' => 'active',
            'queued' => 'queued',
            'completed' => 'completed',
            'completed_with_errors' => 'completed_with_errors',
            'failed' => 'failed',
            default => 'active', // Default to active if unknown
        };
    }

    public function generateCsvReportErrors(Request $request)
    {
        return $this->execute(function () use ($request) {
            // Generar nombre único para el archivo
            $batchId = $request->input('batch_id');
            $userId = $request->input('user_id');

            // Disparamos el job principal
            CsvReportErrors::dispatch(
                $batchId,
                $userId,
            );

            return [
                'code' => 200,
                'message' => 'El reporte se está generando en segundo plano. Se le notificará cuando esté listo.',
            ];
        });
    }

    public function generateExcelReportData(Request $request)
    {
        return $this->execute(function () use ($request) {
            // Generar nombre único para el archivo
            $batchId = $request->input('batch_id');
            $userId = $request->input('user_id');

            // Disparamos el job principal
            ExcelReportData::dispatch(
                $batchId,
                $userId,
            );

            return [
                'code' => 200,
                'message' => 'El reporte se está generando en segundo plano. Se le notificará cuando esté listo.',
            ];
        });
    }

    public function getBatchStatus(string $batchId)
    {
        // Leemos directo de la memoria (Redis) para máxima velocidad
        $redis = Redis::connection('redis_6380');
        $metadata = $redis->hgetall("batch:{$batchId}:metadata");

        if (empty($metadata)) {
            // Si no está en Redis, buscamos en BD como respaldo final
            // (Asumiendo que tienes un modelo ProcessBatch)
            $batch = ProcessBatch::where('batch_id', $batchId)->first();

            if (! $batch) {
                return response()->json(['error' => 'Proceso no encontrado'], 404);
            }

            // Retornamos estructura similar a la de Redis
            return response()->json([
                'status' => $batch->status,
                'progress' => ($batch->status === 'completed' || $batch->status === 'completed_with_errors') ? 100 : 0,
                'metadata' => $batch->metadata ?? [], // Asumiendo que es JSON en BD
            ]);
        }

        // Calculamos el porcentaje real basado en Redis
        $total = (int) ($metadata['total_rows'] ?? 0);
        $processed = (int) ($metadata['processed_records'] ?? 0);
        $progress = ($total > 0) ? round(($processed / $total) * 100, 2) : 0;

        return response()->json([
            'status' => $metadata['status'] ?? 'unknown',
            'progress' => $progress,
            'metadata' => $metadata, // Enviamos toda la data para actualizar UI
            // Flags para el front
            'current_action' => 'Sincronizando vía Polling...',
            'current_element' => "Procesados: $processed / $total",
        ]);
    }
}
