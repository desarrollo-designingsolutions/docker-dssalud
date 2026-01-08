<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;

class ATFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Número de la factura',
            1 => 'Columna 2: Código del prestador',
            2 => 'Columna 3: Tipo de identificación del usuario',
            5 => 'Columna 6: Tipo de servicio',
            7 => 'Columna 8: Nombre del servicio',
        ];

        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $tipoServicio = $data[5] ?? '';
        $nomServicio  = $data[7] ?? '';

        if ($numFactura === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_001', $cols[0], '');
        if ($codPrestador === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_002', $cols[1], '');

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_003', $cols[2], $tipoId);
        }

        if ($tipoServicio === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_004', $cols[5], '');
        if ($nomServicio === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_005', $cols[7], '');
    }

    private static function logError($batchId, $row, $fileName, $data, $constName, $colTitle, $val, ...$msgArgs) {
        $debugData = ['file' => $fileName, 'code' => ErrorCodes::getCode($constName), 'row_data' => $data];
        ErrorCollector::addError(
            $batchId, $row, $colTitle,
            "[" . ErrorCodes::getCode($constName) . "] " . ErrorCodes::getMessage($constName, ...$msgArgs),
            'R', $val, json_encode($debugData)
        );
    }
}
