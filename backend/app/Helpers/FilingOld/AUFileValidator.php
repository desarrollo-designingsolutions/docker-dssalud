<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;

class AUFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Número de la factura',
            // ...
            4 => 'Columna 5: Fecha ingreso',
            5 => 'Columna 6: Hora ingreso',
            7 => 'Columna 8: Causa externa',
            8 => 'Columna 9: Diagnostico salida',
            12 => 'Columna 13: Destino',
            13 => 'Columna 14: Estado',
            14 => 'Columna 15: Causa muerte',
            15 => 'Columna 16: Fecha salida',
            16 => 'Columna 17: Hora salida',
        ];

        $numFactura  = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId      = $data[2] ?? '';
        $fecIngreso  = $data[4] ?? '';
        $horaIngreso = $data[5] ?? '';
        $causaExt    = $data[7] ?? '';
        $diagSalida  = $data[8] ?? '';
        $destino     = $data[12] ?? '';
        $estado      = $data[13] ?? '';
        $causaMuerte = $data[14] ?? '';
        $fecSalida   = $data[15] ?? '';
        $horaSalida  = $data[16] ?? '';

        if ($numFactura === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_001', $cols[0], '');
        if ($codPrestador === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_002', $cols[1], '');

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_003', $cols[2], $tipoId);

        if ($fecIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_004', $cols[4], '');
        } elseif (!self::isValidDate($fecIngreso)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_004_F', $cols[4], $fecIngreso);
        }

        if ($horaIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_005', $cols[5], '');
        } elseif (!self::isValidTime($horaIngreso)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_005_F', $cols[5], $horaIngreso);
        }

        $allowedCausa = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'];
        if ($causaExt === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_007', $cols[7], '');
        } elseif (!in_array($causaExt, $allowedCausa)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_006', $cols[7], $causaExt);
        }

        if ($diagSalida === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_008', $cols[8], '');

        if ($destino === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_009', $cols[12], '');
        } elseif (!in_array($destino, ['1','2','3','4','5'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_009_V', $cols[12], $destino);
        }

        if ($estado === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_EST', $cols[13], '');
        } elseif (!in_array($estado, ['1', '2'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_EST_V', $cols[13], $estado);
        }

        if ($estado === '2' && $causaMuerte === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_MUE', $cols[14], '');
        }

        if ($fecSalida === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_010', $cols[15], '');
        } elseif (!self::isValidDate($fecSalida)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_010_F', $cols[15], $fecSalida);
        }

        if (self::isValidDate($fecIngreso) && self::isValidDate($fecSalida)) {
            $dIn = \DateTime::createFromFormat('d/m/Y', $fecIngreso);
            $dOut = \DateTime::createFromFormat('d/m/Y', $fecSalida);
            if ($dIn > $dOut) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_DATE_SEQ', $cols[15], "$fecIngreso > $fecSalida");
            }
        }

        if ($horaSalida === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_011', $cols[16], '');
        } elseif (!self::isValidTime($horaSalida)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_011_F', $cols[16], $horaSalida);
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

    private static function isValidTime(string $time): bool {
        return preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $time);
    }
}
