<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class APFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'columna 1: Número de la factura',
            1 => 'columna 2: Código del prestador de servicios de salud',
            2 => 'columna 3: Tipo de identificación del usuario',
            3 => 'columna 4: Número de identificación del usuario en el sistema',
            4 => 'columna 5: Fecha del procedimiento',
            5 => 'columna 6: Numero de autorizacion',
            6 => 'columna 7: Código del procedimiento',
            7 => 'columna 8: Ambito de realización del procedimiento',
            8 => 'columna 9: Finalidad del procedimiento',
            9 => 'columna 10: Personal que atiende',
            10 => 'columna 11: Diagnóstico principal',
            11 => 'columna 12: Diagnostico relacionado',
            12 => 'columna 13: Complicación',
            13 => 'columna 14: Forma de realización del acto quirúrgico',
            14 => 'columna 15: Valor del procedimiento',
        ];

        $numFactura = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId = $data[2] ?? '';
        $numIdUsuario = $data[3] ?? '';
        $fecProc = $data[4] ?? '';
        $codProc = $data[6] ?? '';
        $ambito = $data[7] ?? '';
        $valorProc = $data[14] ?? '';

        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_001', $cols[0], '');
        }
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_002', $cols[1], '');
        }

        if ($tipoId === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_003', $cols[2], '');
        } else {
            $allowedPrefixes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
            if (! in_array($tipoId, $allowedPrefixes)) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_004', $cols[2], $tipoId);
            }
        }

        if ($numIdUsuario === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_005', $cols[3], '');
        }

        if ($fecProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_006', $cols[4], '');
        } elseif (! self::isValidDate($fecProc)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_006_F', $cols[4], $fecProc);
        }

        if ($codProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_007', $cols[6], '');
        }
        if ($ambito === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_008', $cols[7], '');
        }

        if ($valorProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_009', $cols[14], '');
        } elseif (! is_numeric($valorProc)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_009_N', $cols[14], $valorProc);
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
