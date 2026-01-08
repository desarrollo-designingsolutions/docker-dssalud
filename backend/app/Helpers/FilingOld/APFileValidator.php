<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;

class APFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Número de la factura',
            1 => 'Columna 2: Código del prestador',
            2 => 'Columna 3: Tipo de identificación del usuario',
            3 => 'Columna 4: Número de identificación del usuario',
            4 => 'Columna 5: Fecha del procedimiento',
            6 => 'Columna 7: Código del procedimiento',
            7 => 'Columna 8: Ambito de realización',
            14 => 'Columna 15: Valor del procedimiento',
        ];

        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $numIdUsuario = $data[3] ?? '';
        $fecProc      = $data[4] ?? '';
        $codProc      = $data[6] ?? '';
        $ambito       = $data[7] ?? '';
        $valorProc    = $data[14] ?? '';

        if ($numFactura === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_001', $cols[0], '');
        if ($codPrestador === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_002', $cols[1], '');

        if ($tipoId === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_003', $cols[2], '');
        } else {
            $allowedPrefixes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
            if (!in_array($tipoId, $allowedPrefixes)) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_004', $cols[2], $tipoId);
            }
        }

        if ($numIdUsuario === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_005', $cols[3], '');

        if ($fecProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_006', $cols[4], '');
        } elseif (!self::isValidDate($fecProc)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_006_F', $cols[4], $fecProc);
        }

        if ($codProc === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_007', $cols[6], '');
        if ($ambito === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_008', $cols[7], '');

        if ($valorProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_009', $cols[14], '');
        } elseif (!is_numeric($valorProc)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_009_N', $cols[14], $valorProc);
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
