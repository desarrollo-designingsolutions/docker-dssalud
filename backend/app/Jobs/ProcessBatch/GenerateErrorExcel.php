<?php

namespace App\Jobs\ProcessBatch;

use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class GenerateErrorExcel implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $processKey;

    protected string $userId;

    public function __construct(string $processKey, string $userId)
    {
        $this->processKey = $processKey;
        $this->userId = $userId;
        $this->onQueue('download_files');
    }

    public function handle(): void
    {
        $rowsKey = $this->processKey.':rows';

        // Recover metadata
        $metadata = Redis::hgetall($this->processKey);
        $fileName = $metadata['file_name'] ?? 'reporte_datos.xlsx';

        // Create temp dir
        $tempPath = storage_path('app/temp/'.uniqid().'.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        try {
            $writer = new Writer;
            $writer->openToFile($tempPath);

            $batchSize = 1000;
            $totalItems = Redis::llen($rowsKey);
            $processed = 0;
            $headersWritten = false;
            $headers = [];

            while ($processed < $totalItems) {
                $chunk = Redis::lrange($rowsKey, $processed, $processed + $batchSize - 1);

                if (empty($chunk)) {
                    break;
                }

                foreach ($chunk as $jsonRow) {
                    $row = json_decode($jsonRow, true);

                    if (! $row || ! is_array($row)) {
                        continue;
                    }

                    // 1. DYNAMIC HEADERS: Detect headers from the first valid row found
                    if (! $headersWritten) {
                        $headers = array_keys($row);
                        // Write Headers
                        $headerRow = Row::fromValues($headers);
                        $writer->addRow($headerRow);
                        $headersWritten = true;
                    }

                    // 2. Map row data to headers order
                    $rowData = [];
                    foreach ($headers as $header) {
                        // Ensure value is string/scalar
                        $val = $row[$header] ?? '';
                        $rowData[] = is_array($val) ? json_encode($val) : (string) $val;
                    }

                    $excelRow = Row::fromValues($rowData);
                    $writer->addRow($excelRow);
                }

                $processed += count($chunk);
                unset($chunk);
            }

            $writer->close();

            // Move to public
            $publicPath = 'reports/'.$fileName;
            Storage::disk('public')->put($publicPath, file_get_contents($tempPath));

            // Cleanup
            unlink($tempPath);
            Redis::del($this->processKey, $rowsKey, $this->processKey.':seen_rows');

            // Notify
            $url = Storage::disk('public')->url($publicPath);
            $user = User::find($this->userId);

            if ($user) {
                $user->notify(new BellNotification([
                    'title' => 'Reporte Generado',
                    'subtitle' => 'Su archivo de datos está listo.',
                    'type' => 'success',
                    'openInNewTab' => true,
                    'action_url' => $url,
                ]));
            }

            Log::info("Excel Data generated: $fileName");
        } catch (\Throwable $e) {
            Log::error('Error generating Data Excel: '.$e->getMessage());
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            throw $e;
        }
    }
}
