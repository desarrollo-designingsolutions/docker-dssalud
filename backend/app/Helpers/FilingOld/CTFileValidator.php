<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
// Importamos la clase central
use Illuminate\Support\Facades\Redis;

class CTFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'columna 1: Código del prestador de servicios de salud',
            1 => 'columna 2: Fecha de remisión',
            2 => 'columna 3: Código del archivo',
            3 => 'columna 4: Total de registros',
        ];

        $codPrestador = $data[0] ?? '';
        $fechaRemision = $data[1] ?? '';
        $codArchivo = $data[2] ?? '';
        $totalReg = $data[3] ?? '';

        // 1. Código Prestador
        if (! ctype_digit($codPrestador)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_001', $cols[0], $codPrestador);
        } elseif (strlen($codPrestador) !== 12) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_002', $cols[0], $codPrestador);
        }

        // 2. Fecha
        if (! preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaRemision) || ! self::isValidDate($fechaRemision)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_003', $cols[1], $fechaRemision);
        } elseif (self::isDateAfterToday($fechaRemision)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_004', $cols[1], $fechaRemision);
        }

        // 3. Código Archivo
        $prefix = strtoupper(substr($codArchivo, 0, 2));
        $allowedPrefixes = ['AC', 'AF', 'AH', 'AM', 'AN', 'AP', 'AT', 'AU', 'US', 'CT'];

        if (! in_array($prefix, $allowedPrefixes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_005', $cols[2], $codArchivo, $prefix);
        } else {
            $isNew = Redis::connection('redis_6380')->sadd("batch:{$batchId}:ct_reported_files", $codArchivo);
            if (! $isNew) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_006', $cols[2], $codArchivo, $codArchivo);
            }
        }

        // 4. Total Registros
        if (! ctype_digit($totalReg)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_007', $cols[3], $totalReg);
        } else {
            $expectedCount = (int) $totalReg;
            $fileMapKey = "batch:{$batchId}:file_counts";
            $allCounts = Redis::connection('redis_6380')->hgetall($fileMapKey);
            $actualCount = null;

            foreach ($allCounts as $nameOnDisk => $count) {
                if (pathinfo($nameOnDisk, PATHINFO_FILENAME) === $codArchivo) {
                    $actualCount = (int) $count;
                    break;
                }
            }

            if ($actualCount === null) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_008', $cols[2], $codArchivo);
            } elseif ($actualCount !== $expectedCount) {
                // Pasamos argumentos extra para el mensaje dinámico (%s, %s)
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_009', $cols[3], $totalReg, $expectedCount, $actualCount);
            }
        }
    }

    private static function logError($batchId, $row, $fileName, $data, $constName, $colTitle, $val, ...$msgArgs)
    {
        $debugData = ['file' => $fileName, 'code' => ErrorCodes::getCode($constName), 'row_data' => $data];
        ErrorCollector::addError(
            $batchId,
            $row,
            $colTitle,
            '['.ErrorCodes::getCode($constName).'] '.ErrorCodes::getMessage($constName, ...$msgArgs),
            'R',
            $val,
            json_encode($debugData)
        );
    }

    private static function isValidDate(string $date): bool
    {
        $parts = explode('/', $date);

        return count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]);
    }

    private static function isDateAfterToday(string $date): bool
    {
        $d = \DateTime::createFromFormat('d/m/Y', $date);

        return $d && $d > new \DateTime('today');
    }
}
