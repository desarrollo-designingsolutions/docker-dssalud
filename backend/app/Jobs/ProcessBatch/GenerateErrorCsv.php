<?php

namespace App\Jobs\ProcessBatch;

use App\Models\User;
use App\Notifications\BellNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class GenerateErrorCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $metadata = Redis::hgetall($this->processKey);
        $fileName = $metadata['file_name'] ?? 'reporte_errores.csv';

        // Ruta temporal
        $tempPath = 'temp/'.uniqid().'.csv';
        Storage::disk('local')->put($tempPath, '');
        $handle = fopen(Storage::disk('local')->path($tempPath), 'w');

        // BOM para Excel (para que abra bien las tildes)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeceras
        fputcsv($handle, ['Tipo', 'Columna', 'Fila', 'Valor Errado', 'Mensaje', 'Archivo Original']);

        // LEER DE REDIS POR LOTES
        $batchSize = 1000;
        $totalItems = Redis::llen($rowsKey);
        $processed = 0;

        while ($processed < $totalItems) {
            $chunk = Redis::lrange($rowsKey, $processed, $processed + $batchSize - 1);

            if (empty($chunk)) {
                break;
            }

            foreach ($chunk as $jsonRow) {
                // ✅ Ahora sí funcionará porque el Worker guardó JSON
                $row = json_decode($jsonRow, true);

                if ($row) {
                    // Extraer nombre de archivo del JSON original si existe
                    $fileOrigin = '';
                    if (isset($row['original_data'])) {
                        $originData = json_decode($row['original_data'], true); // Decodificar el string JSON de BD
                        $fileOrigin = $originData['file'] ?? '';
                    }

                    fputcsv($handle, [
                        $row['error_type'] ?? '',
                        $row['column_name'] ?? '',
                        $row['row_number'] ?? '',
                        $row['error_value'] ?? '',
                        $row['error_message'] ?? '',
                        $fileOrigin,
                    ]);
                }
            }

            $processed += count($chunk);
            unset($chunk);
        }

        fclose($handle);

        // Mover a public y limpiar
        $publicPath = 'reports/'.$fileName;
        Storage::disk('public')->put($publicPath, file_get_contents(Storage::disk('local')->path($tempPath)));
        Storage::disk('local')->delete($tempPath);
        Redis::del($this->processKey, $rowsKey);

        // Notificar
        $url = Storage::disk('public')->url($publicPath);
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new BellNotification([
                'title' => 'Reporte CSV Listo',
                'subtitle' => 'Descargue su archivo de errores aquí.',
                'type' => 'success',
                'openInNewTab' => true,
                'action_url' => $url,
            ]));
        }
    }
}
