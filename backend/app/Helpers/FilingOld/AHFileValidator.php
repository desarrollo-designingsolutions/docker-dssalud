<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;

class AHFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Número de la factura',
            // ...
            4 => 'Columna 5: Via de ingreso',
            5 => 'Columna 6: Fecha ingreso',
            6 => 'Columna 7: Hora ingreso',
            8 => 'Columna 9: Causa externa',
            17 => 'Columna 18: Fecha egreso',
            18 => 'Columna 19: Hora egreso',
        ];

        $numFactura  = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId      = $data[2] ?? '';
        $viaIngreso  = $data[4] ?? '';
        $fecIngreso  = $data[5] ?? '';
        $horaIngreso = $data[6] ?? '';
        $causaExt    = $data[8] ?? '';
        $fecEgreso   = $data[17] ?? '';
        $horaEgreso  = $data[18] ?? '';

        if ($numFactura === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_001', $cols[0], '');
        if ($codPrestador === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_002', $cols[1], '');

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_004', $cols[2], $tipoId);
        }

        if ($viaIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_003', $cols[4], '');
        }

        if ($fecIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_005', $cols[5], '');
        } elseif (!self::isValidDate($fecIngreso)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_005_F', $cols[5], $fecIngreso);
        }

        if ($horaIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_006', $cols[6], '');
        }

        if ($causaExt === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_007', $cols[8], '');
        }

        if ($fecEgreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_009', $cols[17], '');
        } elseif (!self::isValidDate($fecEgreso)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_009_F', $cols[17], $fecEgreso);
        }

        if ($horaEgreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_010', $cols[18], '');
        }
    }

    private static function logError($batchId, $row, $fileName, $data, $constName, $colTitle, $val, ...$msgArgs) {
        $debugData = ['file' => $fileName, 'code' => ErrorCodes::getCode($constName), 'row_data' => $data];
        ErrorCollector::addError(
            $batchId, $row, $colTitle,
            "[" . ErrorCodes::getCode($constName) . "] " . ErrorCodes::getMessage($constName, ...$msgArgs),
            'R', $val, json_encode($debugData)
        );
    }

    private static function isValidDate(string $date): bool {
        $parts = explode('/', $date);
        return count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]);
    }
}
