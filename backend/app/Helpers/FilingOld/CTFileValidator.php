<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use Illuminate\Support\Facades\Redis;

class CTFileValidator
{
    /**
     * Valida el archivo CT (Control).
     */
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        // 1. Ajuste de Llaves
        // $keyErrorRedis ya no se usa directo aquí, se usa dentro de ErrorCollector

        // Convertimos el string CSV a Array
        $data = array_map('trim', explode(',', $rowData));

        // 2. Mapeo de Columnas (UX)
        $cols = [
            0 => 'Columna 1: Código del prestador',
            1 => 'Columna 2: Fecha de remisión',
            2 => 'Columna 3: Código del archivo',
            3 => 'Columna 4: Total de registros',
        ];

        // Variables para legibilidad
        $codPrestador = $data[0] ?? '';
        $fechaRemision = $data[1] ?? '';
        $codArchivo = $data[2] ?? '';
        $totalReg = $data[3] ?? '';

        // --------------------------------------------------------
        // VALIDACIÓN 1: Código Prestador (Col 1)
        // --------------------------------------------------------
        if (!ctype_digit($codPrestador)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_001',
                "El valor no es numérico.", $cols[0], $codPrestador);
        }
        elseif (strlen($codPrestador) !== 12) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_002',
                "Debe tener 12 caracteres.", $cols[0], $codPrestador);
        }

        // --------------------------------------------------------
        // VALIDACIÓN 2: Fecha (Col 2)
        // --------------------------------------------------------
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaRemision) || !self::isValidDate($fechaRemision)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_003',
                "Fecha inválida (formato dd/mm/aaaa).", $cols[1], $fechaRemision);
        }
        elseif (self::isDateAfterToday($fechaRemision)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_004',
                "La fecha es futura.", $cols[1], $fechaRemision);
        }

        // --------------------------------------------------------
        // VALIDACIÓN 3: Código Archivo (Col 3) y Duplicados
        // --------------------------------------------------------
        $prefix = strtoupper(substr($codArchivo, 0, 2));
        $allowedPrefixes = ['AC', 'AF', 'AH', 'AM', 'AN', 'AP', 'AT', 'AU', 'US', 'CT'];

        if (!in_array($prefix, $allowedPrefixes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_005',
                "El prefijo '{$prefix}' no es válido.", $cols[2], $codArchivo);
        } else {
            // Verificación de duplicados en el mismo CT usando Redis Set
            $isNew = Redis::connection('redis_6380')->sadd("batch:{$batchId}:ct_reported_files", $codArchivo);

            if (!$isNew) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_006',
                    "El archivo '{$codArchivo}' está duplicado en este CT.", $cols[2], $codArchivo);
            }
        }

        // --------------------------------------------------------
        // VALIDACIÓN 4: Total Registros (Col 4) vs REALIDAD
        // --------------------------------------------------------
        if (!ctype_digit($totalReg)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_007',
                "El total no es numérico.", $cols[3], $totalReg);
        } else {
            $expectedCount = (int) $totalReg;

            // Consultamos el mapa de conteos generado en la Fase 2
            $fileMapKey = "batch:{$batchId}:file_counts";
            $allCounts = Redis::connection('redis_6380')->hgetall($fileMapKey);
            $actualCount = null;

            // Buscamos coincidencia (US001 vs US001.txt)
            foreach ($allCounts as $nameOnDisk => $count) {
                if (pathinfo($nameOnDisk, PATHINFO_FILENAME) === $codArchivo) {
                    $actualCount = (int) $count;
                    break;
                }
            }

            if ($actualCount === null) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_008',
                    "El archivo referenciado no existe en el ZIP.", $cols[2], $codArchivo);
            }
            elseif ($actualCount !== $expectedCount) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_CT_ERROR_009',
                    "Inconsistencia: CT dice {$expectedCount}, archivo real tiene {$actualCount}.", $cols[3], $totalReg);
            }
        }
    }

    /**
     * Helper privado ajustado a ErrorCollector real.
     */
    private static function logError($batchId, $row, $fileName, $data, $code, $msg, $colTitle, $val) {

        $debugData = [
            'file' => $fileName,
            'code' => $code,
            'row_data' => $data
        ];

        ErrorCollector::addError(
            $batchId,
            $row,
            $colTitle,          // "Columna 1..."
            "[$code] $msg",     // Mensaje con código
            'R',
            $val,               // Valor errado
            json_encode($debugData)
        );
    }

    private static function isValidDate(string $date): bool
    {
        $parts = explode('/', $date);
        if (count($parts) !== 3) return false;
        return checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]);
    }

    private static function isDateAfterToday(string $date): bool
    {
        $d = \DateTime::createFromFormat('d/m/Y', $date);
        return $d && $d > new \DateTime('today');
    }
}
