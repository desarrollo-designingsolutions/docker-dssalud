<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;

class AMFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Número de la factura',
            // ...
            6 => 'Columna 7: Tipo de medicamento',
            7 => 'Columna 8: Nombre genérico',
            // ...
            12 => 'Columna 13: Valor unitario',
            13 => 'Columna 14: Valor total',
        ];

        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $tipoMed      = $data[6] ?? '';
        $nomGenerico  = $data[8] ?? ''; // Indice 8 segun tu logica original
        $formaFarm    = $data[9] ?? '';
        $concentracion= $data[10] ?? '';
        $unidadMed    = $data[11] ?? '';
        $numUnidades  = $data[12] ?? '';
        $valTotal     = $data[13] ?? '';

        if ($numFactura === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_001', $cols[0], '');
        if ($codPrestador === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_002', $cols[1], '');

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_003', $cols[2], $tipoId);
        }

        if ($tipoMed === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_004', $cols[6], '');
        } elseif (!in_array($tipoMed, ['1', '2'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_005', $cols[6], $tipoMed);
        }

        if ($nomGenerico === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_006', $cols[7], '');
        if ($formaFarm === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_007', $cols[8], '');
        if ($concentracion === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_008', $cols[9], '');
        if ($unidadMed === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_009', $cols[10], '');
        if ($numUnidades === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_010', $cols[12], '');
        if ($valTotal === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_011', $cols[13], '');
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
