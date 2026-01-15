<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class AMFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'columna 1: Número de la factura',
            1 => 'columna 2: Código del prestador de servicios de salud',
            2 => 'columna 3: Tipo de identificación del usuario',
            3 => 'columna 4: Número de identificación del usuario en el sistema',
            4 => 'columna 5: Numero de autorizacion',
            5 => 'columna 6: Código del medicamento',
            6 => 'columna 7: Tipo de medicamento',
            7 => 'columna 8: Nombre genérico del medicamento',
            8 => 'columna 9: Forma farmacéutica',
            9 => 'columna 10: Concentración del medicamento',
            10 => 'columna 11: Unidad de medida del medicamento',
            11 => 'columna 12: Número de unidades',
            12 => 'columna 13: Valor unitario de medicamento',
            13 => 'columna 14: Valor total de medicamento',
        ];

        $numFactura = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId = $data[2] ?? '';
        $tipoMed = $data[6] ?? '';
        $nomGenerico = $data[7] ?? '';
        $formaFarm = $data[8] ?? '';
        $concentracion = $data[9] ?? '';
        $unidadMed = $data[10] ?? '';
        $numUnidades = $data[11] ?? '';
        $valTotal = $data[12] ?? '';

        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_001', $cols[0], '');
        }
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_002', $cols[1], '');
        }

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (! in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_003', $cols[2], $tipoId);
        }

        if ($tipoMed === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_004', $cols[6], '');
        } elseif (! in_array($tipoMed, ['1', '2'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_005', $cols[6], $tipoMed);
        }

        if ($nomGenerico === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_006', $cols[7], '');
        }
        if ($formaFarm === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_007', $cols[8], '');
        }
        if ($concentracion === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_008', $cols[9], '');
        }
        if ($unidadMed === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_009', $cols[10], '');
        }
        if ($numUnidades === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_010', $cols[12], '');
        }
        if ($valTotal === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_011', $cols[13], '');
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
}
