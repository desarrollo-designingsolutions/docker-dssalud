<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class ANFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'columna 1: Número de la factura',
            1 => 'columna 2: Código del prestador de servicios de salud',
            2 => 'columna 3: Tipo de identificación de la madre',
            3 => 'columna 4: Numero de identificacion de la madre en el Sistema',
            4 => 'columna 5: Fecha de nacimiento del recién nacido',
            5 => 'columna 6: Hora de nacimiento',
            6 => 'columna 7: Edad gestacional',
            7 => 'columna 8: Control prenatal',
            8 => 'columna 9: Sexo',
            9 => 'columna 10: Peso',
            10 => 'columna 11: Diagnóstico del recién nacido',
            11 => 'columna 12: Causa básica de muerte',
            12 => 'columna 13: Fecha de muerte del recién nacido',
            13 => 'columna 14: Hora de muerte del recién nacido',
        ];

        $numFactura = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoIdMadre = $data[2] ?? '';
        $fecNac = $data[4] ?? '';
        $horaNac = $data[5] ?? '';
        $edadGest = $data[6] ?? '';
        $ctrlPrenatal = $data[7] ?? '';
        $sexo = $data[8] ?? '';
        $peso = $data[9] ?? '';

        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_001', $cols[0], '');
        }
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_002', $cols[1], '');
        }

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (! in_array($tipoIdMadre, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_003', $cols[2], $tipoIdMadre);
        }

        if ($fecNac === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_004', $cols[4], '');
        }
        if ($horaNac === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_005', $cols[5], '');
        }
        if ($edadGest === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_006', $cols[6], '');
        }

        if ($ctrlPrenatal === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_007', $cols[7], '');
        } elseif (! in_array($ctrlPrenatal, ['1', '2'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_008', $cols[7], $ctrlPrenatal);
        }

        if ($sexo === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_009', $cols[8], '');
        } elseif (! in_array($sexo, ['1', '2'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_010', $cols[8], $sexo);
        }

        if ($peso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_011', $cols[9], '');
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
