<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class AHFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'columna 1: Número de la factura',
            1 => 'columna 2: Código del prestador de servicios de salud',
            2 => 'columna 3: Tipo de identificación del usuario',
            3 => 'columna 4: Número de identificación del usuario en el sistema',
            4 => 'columna 5: Via de ingreso a la institucion',
            5 => 'columna 6: Fecha de ingreso del usuario a la institución',
            6 => 'columna 7: Hora de ingreso del usuario a la Institución',
            7 => 'columna 8: Numero de autorizacion',
            8 => 'columna 9: Causa externa',
            9 => 'columna 10: Diagnostico principal de ingreso',
            10 => 'columna 11: Diagnóstico principal de egreso',
            11 => 'columna 12: Diagnóstico relacionado Nro. 1 de egreso',
            12 => 'columna 13: Diagnóstico relacionado Nro. 2 de egreso',
            13 => 'columna 14: Diagnóstico relacionado Nro. 3 de egreso',
            14 => 'columna 15: Diagnóstico de la complicacion',
            15 => 'columna 16: Estado a la salida',
            16 => 'columna 17: Diagnóstico de la causa básica de muerte',
            17 => 'columna 18: Fecha de egreso del usuario a la institución',
            18 => 'columna 19: Hora de egreso del usuario de la institución',
        ];

        $numFactura = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId = $data[2] ?? '';
        $viaIngreso = $data[4] ?? '';
        $fecIngreso = $data[5] ?? '';
        $horaIngreso = $data[6] ?? '';
        $causaExt = $data[8] ?? '';
        $fecEgreso = $data[17] ?? '';
        $horaEgreso = $data[18] ?? '';

        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_001', $cols[0], '');
        }
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_002', $cols[1], '');
        }

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (! in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_004', $cols[2], $tipoId);
        }

        if ($viaIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_003', $cols[4], '');
        }

        if ($fecIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_005', $cols[5], '');
        } elseif (! self::isValidDate($fecIngreso)) {
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
        } elseif (! self::isValidDate($fecEgreso)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_009_F', $cols[17], $fecEgreso);
        }

        if ($horaEgreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_010', $cols[18], '');
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
}
