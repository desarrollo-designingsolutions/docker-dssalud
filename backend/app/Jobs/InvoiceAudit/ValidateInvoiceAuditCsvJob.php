<?php

namespace App\Jobs\InvoiceAudit;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\InvoiceAudit\ErrorCodes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use App\Models\ProcessBatch;
use App\Models\InvoiceAudit;
use App\Models\Third;
use Illuminate\Support\Str;

class ValidateInvoiceAuditCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    protected string $selectedQueue;

    public function __construct(string $batchId, string $selectedQueue = 'default')
    {
        $this->batchId = $batchId;
        $this->selectedQueue = $selectedQueue;
        $this->onQueue($selectedQueue);
    }

    public function handle()
    {
        $redis = Redis::connection('redis_6380');
        $redisKey = "batch:{$this->batchId}:metadata";
        $metadata = $redis->hgetall($redisKey);

        $fullPath = $metadata['full_path'] ?? null;
        $companyId = $metadata['company_id'] ?? null;

        // 0. Update status to processing
        ProcessBatch::where('batch_id', $this->batchId)->update(['status' => 'processing']);
        $redis->hset($redisKey, 'status', 'processing');

        event(new ImportProgressEvent($this->batchId, 0, 'Iniciando validación de CSV...', 0, 'active', 'CSV Validation'));

        if (!$fullPath || !file_exists($fullPath)) {
            $this->failJobAndStop('ZIP_CRITICAL_001');
            return;
        }

        // 1. ABRIR ARCHIVO Y DETECTAR SEPARADOR
        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            $this->failJobAndStop('ZIP_CRITICAL_001');
            return;
        }
        $firstLine = fgets($handle);
        $separator = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        // 2. LEER ENCABEZADO
        $header = fgetcsv($handle, 1000, $separator);

        if (!$header) {
            $this->failJobAndStop('CSV_EMPTY_FILE');
            fclose($handle);
            return;
        }

        $header = array_map(function ($item) {
            return trim(strtolower($item));
        }, $header);

        $requiredColumns = [
            'nit',
            'numero_factura',
            'valor_factura',
            'fecha_factura',
            'fecha_inicio',
            'fecha_fin',
            'modalidad',
            'regimen',
            'cobertura',
            'contrato',
            'estado'
        ];

        $missing = array_diff($requiredColumns, $header);

        if (!empty($missing)) {
            $this->failJobAndStop('CSV_MISSING_COLUMNS', implode(', ', $missing));
            fclose($handle);
            return;
        }

        $colMap = array_flip($header);
        $rowCount = 0;
        $errorsFound = 0;

        // Cachear Thirds para validación rápida
        $thirds = Third::where('company_id', $companyId)->pluck('id', 'nit')->toArray();

        // 3. VALIDACIÓN FILA POR FILA
        while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
            $rowCount++;

            // Normalizar codificación UTF-8
            $row = $this->normalizeEncoding($row);

            if (count($row) < count($requiredColumns)) {
                $this->addContentError('FILA_INCOMPLETA', $rowCount);
                $errorsFound++;
                continue;
            }

            $rowErrors = $this->validateRow($row, $colMap, $rowCount, $thirds);

            if (!empty($rowErrors)) {
                $errorsFound += count($rowErrors);
                if ($errorsFound > 50)
                    break;
            }
        }
        fclose($handle);


        // Actualizar total_rows en metadata para que el progreso se calcule bien
        $redis->hset($redisKey, 'total_rows', $rowCount);

        if ($errorsFound > 0) {
            $redis->hmset($redisKey, [
                'error_count' => $errorsFound,
                'status' => 'failed'
            ]);

            $this->finalizeAsFailed("Se encontraron {$errorsFound} errores en el contenido del CSV.");
            return;
        }

        // 4. ÉXITO - GUARDAR DATOS
        try {
            $this->saveData($fullPath, $colMap, $separator, $companyId, $thirds);

            // 5. Finalizar exitosamente
            $metadata = $redis->hgetall($redisKey);
            $metadata['status'] = 'completed';
            $metadata['total_rows'] = $rowCount;
            $metadata['processed_records'] = $rowCount;
            $metadata['finished_at'] = now()->toDateTimeString();
            $redis->hmset($redisKey, $metadata);

            ProcessBatch::where('batch_id', $this->batchId)->update([
                'status' => 'completed',
                'total_records' => $rowCount,
                'processed_records' => $rowCount,
                'metadata' => $metadata,
                'updated_at' => now(),
            ]);

            event(new ImportProgressEvent($this->batchId, $rowCount, 'CSV Procesado y guardado correctamente', 0, 'completed', 'Success'));

        } catch (\Exception $e) {
            Log::error("Error guardando datos de InvoiceAudit: " . $e->getMessage());
            $this->finalizeAsFailed("Error al guardar los datos en la base de datos: " . $e->getMessage());
        }
    }

    private function saveData($fullPath, $colMap, $separator, $companyId, $thirds)
    {
        $handle = fopen($fullPath, 'r');
        fgetcsv($handle, 1000, $separator); // skip header

        $dataToInsert = [];
        $batchSize = 200; // Un poco más pequeño para mayor seguridad

        while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
            // Normalizar codificación UTF-8
            $row = $this->normalizeEncoding($row);

            $nit = trim($row[$colMap['nit']]);
            $thirdId = $thirds[$nit] ?? null;

            if (!$thirdId)
                continue;

            $dataToInsert[] = [
                'id' => Str::uuid()->toString(),
                'company_id' => $companyId,
                'third_id' => $thirdId,
                'invoice_number' => $row[$colMap['numero_factura']],
                'total_value' => floatval(str_replace(',', '.', $row[$colMap['valor_factura']])),
                'origin' => 'Radicacion',
                'expedition_date' => $this->parseDate($row[$colMap['fecha_factura']]),
                'date_entry' => $this->parseDate($row[$colMap['fecha_inicio']]),
                'date_departure' => $this->parseDate($row[$colMap['fecha_fin']]),
                'modality' => $row[$colMap['modalidad']],
                'regimen' => $row[$colMap['regimen']],
                'coverage' => $row[$colMap['cobertura']],
                'contract_number' => $row[$colMap['contrato']],
                'status' => $row[$colMap['estado']],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($dataToInsert) >= $batchSize) {
                InvoiceAudit::insert($dataToInsert);
                $dataToInsert = [];
            }
        }

        if (!empty($dataToInsert)) {
            InvoiceAudit::insert($dataToInsert);
        }

        fclose($handle);
    }

    private function normalizeEncoding($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'normalizeEncoding'], $data);
        }
        if (is_string($data)) {
            return mb_check_encoding($data, 'UTF-8') ? $data : mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
        }
        return $data;
    }

    private function parseDate($date)
    {
        if (empty($date))
            return null;
        try {
            return Carbon::parse($date)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function validateRow($row, $colMap, $line, $thirds)
    {
        $errors = [];

        // Regla 1: Nit (Existe en la empresa)
        $nit = trim($row[$colMap['nit']]);
        if (empty($nit)) {
            $errors[] = $this->addContentError('nit', $line);
        } elseif (!isset($thirds[$nit])) {
            $errors[] = $this->addThirdNotFoundError($nit, $line);
        }

        // Regla 2: numero_factura (String obligatorio)
        if (empty($row[$colMap['numero_factura']])) {
            $errors[] = $this->addContentError('numero_factura', $line);
        }

        // Regla 3: valor_factura (Numérico obligatorio)
        $valor = $row[$colMap['valor_factura']];
        if (empty($valor) || !is_numeric(str_replace(',', '.', $valor))) {
            $errors[] = $this->addContentError('valor_factura', $line);
        }

        // Regla 5: fecha_factura (yyyy-mm-dd)
        if (!$this->isValidDate($row[$colMap['fecha_factura']])) {
            $errors[] = $this->addContentError('fecha_factura', $line);
        }

        // Regla 6: fecha_inicio (yyyy-mm-dd) solo si existe numero_factura
        if (!empty($row[$colMap['numero_factura']]) && !$this->isValidDate($row[$colMap['fecha_inicio']])) {
            $errors[] = $this->addContentError('fecha_inicio', $line);
        }

        // Regla 7: fecha_fin (yyyy-mm-dd) solo si existe numero_factura
        if (!empty($row[$colMap['numero_factura']]) && !$this->isValidDate($row[$colMap['fecha_fin']])) {
            $errors[] = $this->addContentError('fecha_fin', $line);
        }

        // Reglas 8, 9, 10, 11: Strings obligatorios
        foreach (['modalidad', 'regimen', 'cobertura', 'estado'] as $field) {
            if (empty($row[$colMap[$field]])) {
                $errors[] = $this->addContentError($field, $line);
            }
        }

        return $errors;
    }

    private function isValidDate($date, $format = 'Y-m-d')
    {
        if (empty($date))
            return false;
        try {
            Carbon::createFromFormat($format, $date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function addContentError($field, $line)
    {
        $msg = ErrorCodes::getMessage('CSV_INVALID_FORMAT', $line, $field);
        $code = ErrorCodes::getCode('CSV_INVALID_FORMAT');

        ErrorCollector::addError($this->batchId, $line, $field, $msg, 'R', $code, null);
        return $msg;
    }

    private function addThirdNotFoundError($nit, $line)
    {
        $msg = ErrorCodes::getMessage('THIRD_NOT_FOUND', $line, $nit);
        $code = ErrorCodes::getCode('THIRD_NOT_FOUND');

        ErrorCollector::addError($this->batchId, $line, 'nit', $msg, 'R', $code, null);
        return $msg;
    }

    private function failJobAndStop(string $errorCodeConstant, ...$args)
    {
        $msg = ErrorCodes::getMessage($errorCodeConstant, ...$args);
        $code = ErrorCodes::getCode($errorCodeConstant);
        ErrorCollector::addError($this->batchId, 0, 'CSV_CRITICAL', $msg, 'R', $code, null);
        $this->finalizeAsFailed($msg);
    }

    private function finalizeAsFailed(string $logMessage)
    {
        $redis = Redis::connection('redis_6380');
        $redisKey = "batch:{$this->batchId}:metadata";
        $metadata = $redis->hgetall($redisKey);

        $metadata['status'] = 'failed';
        $metadata['error_message'] = $logMessage;
        $metadata['finished_at'] = now()->toDateTimeString();
        $redis->hmset($redisKey, $metadata);

        ErrorCollector::saveErrorsToDatabase($this->batchId, 'failed');

        // Aseguramos que ProcessBatch quede en failed con la metadata actualizada
        ProcessBatch::where('batch_id', $this->batchId)->update([
            'status' => 'failed',
            'metadata' => $metadata,
            'updated_at' => now(),
        ]);

        event(new ImportProgressEvent($this->batchId, 1, $logMessage, ErrorCollector::countErrors($this->batchId), 'failed', 'Validation Failed'));
    }
}