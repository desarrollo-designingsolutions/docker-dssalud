<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;

class ACFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Número de la factura',
            1 => 'Columna 2: Código del prestador',
            2 => 'Columna 3: Tipo de identificación del usuario',
            // ...
            4 => 'Columna 5: Fecha de la consulta',
            6 => 'Columna 7: Código de la consulta',
            7 => 'Columna 8: Finalidad',
            8 => 'Columna 9: Causa externa',
            9 => 'Columna 10: Código diagnóstico ppal',
            13 => 'Columna 14: Tipo diagnóstico',
            14 => 'Columna 15: Valor consulta',
            16 => 'Columna 17: Valor neto',
        ];

        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $numIdUsuario = $data[3] ?? '';
        $fecConsulta  = $data[4] ?? '';
        $codConsulta  = $data[6] ?? '';
        $finalidad    = $data[7] ?? '';
        $causa        = $data[8] ?? '';
        $diagPpal     = $data[9] ?? '';
        $tipoDiag     = $data[13] ?? '';
        $valConsulta  = $data[14] ?? '';
        $valNeto      = $data[16] ?? '';

        if ($numFactura === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_001', $cols[0], '');
        if ($codPrestador === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_002', $cols[1], '');

        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_003', $cols[2], $tipoId);

        if ($numIdUsuario === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_003_ID', $cols[3], ''); // New ID needed in ErrorCodes

        if ($fecConsulta === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_004', $cols[4], '');
        } elseif (!self::isValidDate($fecConsulta)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_004_F', $cols[4], $fecConsulta);
        }

        $allowedCups = [
            '890201','890202','890301','890302','890701','890702','890101','890102',
            '890203','890204','890304','890303','890703','890704','890205','890305',
            '890105','890206','890208','890207','890209','890210','890211','890212',
            '890213','890306','890307','890308','890309','890310','890311','890312',
            '890313','890402','890403','890404','890405','890406','890408','890409',
            '890410','890411','890412','890413','890501','890502','890503','890214',
            '890314','890284','890285','890308','890309','890384','890385','890302',
            '940100','940200','940301','940700','940900','941100','941301','941400',
            '942600','943101','943102','943500','944001','944002','944101','944102',
            '944201','944202','944901','944902','944903','944904','944905','944906',
            '944910','944915'
        ];
        if (!in_array($codConsulta, $allowedCups)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_005', $cols[6], $codConsulta);
        }

        $allowedFinalidad = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10'];
        if (!in_array($finalidad, $allowedFinalidad)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_006', $cols[7], $finalidad);

        $allowedCausa = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'];
        if ($causa === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_008', $cols[8], '');
        } elseif (!in_array($causa, $allowedCausa)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_007', $cols[8], $causa);
        }

        if ($diagPpal === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_009', $cols[9], '');

        if ($tipoDiag === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_011', $cols[13], '');
        } elseif (!in_array($tipoDiag, ['1', '2', '3'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_012', $cols[13], $tipoDiag);
        }

        if ($valConsulta === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_013', $cols[14], '');
        } elseif (!is_numeric($valConsulta)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_013_N', $cols[14], $valConsulta);
        }

        if ($valNeto === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_014', $cols[16], '');
        } elseif (!is_numeric($valNeto)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_014_N', $cols[16], $valNeto);
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
