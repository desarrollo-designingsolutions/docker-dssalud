<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class AHFileValidator
{
    /**
     * Valida el archivo AH (Hospitalización).
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
            4 => 'Columna 5: Via de ingreso',
            5 => 'Columna 6: Fecha ingreso',
            6 => 'Columna 7: Hora ingreso',
            7 => 'Columna 8: Numero de autorizacion',
            8 => 'Columna 9: Causa externa',
            9 => 'Columna 10: Diagnostico ingreso',
            10 => 'Columna 11: Diagnóstico egreso',
            11 => 'Columna 12: Diagnóstico rel. 1',
            12 => 'Columna 13: Diagnóstico rel. 2',
            13 => 'Columna 14: Diagnóstico rel. 3',
            14 => 'Columna 15: Complicación',
            15 => 'Columna 16: Estado a la salida',
            16 => 'Columna 17: Causa básica de muerte',
            17 => 'Columna 18: Fecha egreso',
            18 => 'Columna 19: Hora egreso',
        ];

        // Extracción de variables
        $numFactura  = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId      = $data[2] ?? '';
        $viaIngreso  = $data[4] ?? '';
        $fecIngreso  = $data[5] ?? '';
        $horaIngreso = $data[6] ?? '';
        $causaExt    = $data[8] ?? ''; // Ojo: En tu array original validabas rowData[7], pero según tus títulos es la col 9 (índice 8). Ajustado al índice real.
        $diagIngreso = $data[9] ?? '';
        $estado      = $data[15] ?? '';
        $fecEgreso   = $data[17] ?? '';
        $horaEgreso  = $data[18] ?? '';

        // -----------------------------------------------------------
        // 1. NÚMERO DE FACTURA (Col 1)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_001',
                "El dato registrado es obligatorio.", $cols[0], '');
        }

        // -----------------------------------------------------------
        // 2. CÓDIGO PRESTADOR (Col 2)
        // -----------------------------------------------------------
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_002',
                "El dato registrado es obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO IDENTIFICACIÓN (Col 3)
        // -----------------------------------------------------------
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if ($tipoId === '') {
             // Validamos obligatorio si aplica, tu código original no tenía el empty explícito aquí, pero tenía el in_array
        }
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_004',
                "El dato ingresado no es permitido", $cols[2], $tipoId);
        }

        // -----------------------------------------------------------
        // 5. VÍA DE INGRESO (Col 5)
        // -----------------------------------------------------------
        if ($viaIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_003',
                "El dato registrado es obligatorio.", $cols[4], '');
        }

        // -----------------------------------------------------------
        // 6. FECHA INGRESO (Col 6)
        // -----------------------------------------------------------
        if ($fecIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_005',
                "La Fecha de ingreso del usuario a la institución es un dato obligatorio.", $cols[5], '');
        } elseif (!self::isValidDate($fecIngreso)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_005_F',
                "Formato fecha inválido.", $cols[5], $fecIngreso);
        }

        // -----------------------------------------------------------
        // 7. HORA INGRESO (Col 7)
        // -----------------------------------------------------------
        if ($horaIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_006',
                "La Hora de ingreso del usuario a la Institución es un dato obligatorio.", $cols[6], '');
        }

        // -----------------------------------------------------------
        // 9. CAUSA EXTERNA (Col 9)
        // -----------------------------------------------------------
        if ($causaExt === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_007',
                "El dato registrado es obligatorio.", $cols[8], '');
        }


        // -----------------------------------------------------------
        // 18. FECHA EGRESO (Col 18)
        // -----------------------------------------------------------
        if ($fecEgreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_009',
                "El dato registrado es obligatorio.", $cols[17], '');
        } elseif (!self::isValidDate($fecEgreso)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_009_F',
                "Formato fecha inválido.", $cols[17], $fecEgreso);
        }

        // -----------------------------------------------------------
        // 19. HORA EGRESO (Col 19)
        // -----------------------------------------------------------
        if ($horaEgreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AH_ERROR_010',
                "El dato registrado es obligatorio.", $cols[18], '');
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
