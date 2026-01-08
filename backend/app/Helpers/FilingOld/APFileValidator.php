<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class APFileValidator
{
    /**
     * Valida el archivo AP (Procedimientos).
     */
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        // 1. Preparar datos
        $data = array_map('trim', explode(',', $rowData));

        // 2. Mapeo de columnas (UX)
        $cols = [
            0 => 'Columna 1: Número de la factura',
            1 => 'Columna 2: Código del prestador',
            2 => 'Columna 3: Tipo de identificación del usuario',
            3 => 'Columna 4: Número de identificación del usuario',
            4 => 'Columna 5: Fecha del procedimiento',
            5 => 'Columna 6: Numero de autorizacion',
            6 => 'Columna 7: Código del procedimiento',
            7 => 'Columna 8: Ambito de realización',
            8 => 'Columna 9: Finalidad del procedimiento',
            9 => 'Columna 10: Personal que atiende',
            10 => 'Columna 11: Diagnóstico principal',
            11 => 'Columna 12: Diagnostico relacionado',
            12 => 'Columna 13: Complicación',
            13 => 'Columna 14: Forma de realización',
            14 => 'Columna 15: Valor del procedimiento',
        ];

        // Extracción de variables
        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $numIdUsuario = $data[3] ?? '';
        $fecProc      = $data[4] ?? '';
        $codProc      = $data[6] ?? '';
        $ambito       = $data[7] ?? '';
        $valorProc    = $data[14] ?? '';

        // -----------------------------------------------------------
        // 1. NÚMERO DE FACTURA (Col 1)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_001',
                "El numero de factura es un dato obligatorio", $cols[0], '');
        }

        // -----------------------------------------------------------
        // 2. CÓDIGO PRESTADOR (Col 2)
        // -----------------------------------------------------------
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_002',
                "El dato registrado es obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO IDENTIFICACIÓN (Col 3)
        // -----------------------------------------------------------
        if ($tipoId === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_003',
                "El dato registrado es obligatorio.", $cols[2], '');
        } else {
            $allowedPrefixes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
            if (!in_array($tipoId, $allowedPrefixes)) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_004',
                    "El dato ingresado no es permitido", $cols[2], $tipoId);
            }
        }

        // -----------------------------------------------------------
        // 4. NUMERO IDENTIFICACIÓN (Col 4)
        // -----------------------------------------------------------
        if ($numIdUsuario === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_005',
                "El dato registrado es obligatorio.", $cols[3], '');
        }

        // -----------------------------------------------------------
        // 5. FECHA PROCEDIMIENTO (Col 5)
        // -----------------------------------------------------------
        if ($fecProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_006',
                "El dato registrado es obligatorio.", $cols[4], '');
        } elseif (!self::isValidDate($fecProc)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_006_F',
                "Formato fecha inválido (dd/mm/aaaa).", $cols[4], $fecProc);
        }

        // -----------------------------------------------------------
        // 6. CÓDIGO PROCEDIMIENTO (Col 7)
        // -----------------------------------------------------------
        if ($codProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_007',
                "El dato registrado es obligatorio.", $cols[6], '');
        }

        // -----------------------------------------------------------
        // 7. ÁMBITO REALIZACIÓN (Col 8)
        // -----------------------------------------------------------
        if ($ambito === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_008',
                "El dato registrado es obligatorio.", $cols[7], '');
        }
        // Eliminada la validación de valores específicos (1,2,3) para respetar tu lógica original.

        // -----------------------------------------------------------
        // 14. VALOR PROCEDIMIENTO (Col 15)
        // -----------------------------------------------------------
        if ($valorProc === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_009',
                "El dato registrado es obligatorio.", $cols[14], '');
        } elseif (!is_numeric($valorProc)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AP_ERROR_009_N',
                "Debe ser un valor numérico.", $cols[14], $valorProc);
        }
    }

    /**
     * Helper privado
     */
    private static function logError($batchId, $row, $fileName, $data, $code, $msg, $colTitle, $val) {
        $debugData = ['file' => $fileName, 'code' => $code, 'row_data' => $data];
        ErrorCollector::addError(
            $batchId, $row, $colTitle, "[$code] $msg", 'R', $val, json_encode($debugData)
        );
    }

    private static function isValidDate(string $date): bool {
        $parts = explode('/', $date);
        if (count($parts) !== 3) return false;
        return checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]);
    }
}
