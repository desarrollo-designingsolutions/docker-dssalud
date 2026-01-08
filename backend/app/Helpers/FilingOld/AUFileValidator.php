<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class AUFileValidator
{
    /**
     * Valida el archivo AU (Urgencias con Observación).
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
            4 => 'Columna 5: Fecha ingreso observación',
            5 => 'Columna 6: Hora ingreso observación',
            6 => 'Columna 7: Numero de autorizacion',
            7 => 'Columna 8: Causa externa',
            8 => 'Columna 9: Diagnostico a la salida',
            9 => 'Columna 10: Diagnóstico rel. 1',
            10 => 'Columna 11: Diagnóstico rel. 2',
            11 => 'Columna 12: Diagnóstico rel. 3',
            12 => 'Columna 13: Destino a la salida',
            13 => 'Columna 14: Estado a la salida',
            14 => 'Columna 15: Causa muerte',
            15 => 'Columna 16: Fecha salida observación',
            16 => 'Columna 17: Hora salida observación',
        ];

        // Extracción de variables
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

        // -----------------------------------------------------------
        // 1. NÚMERO DE FACTURA (Col 1)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_001',
                "Dato obligatorio.", $cols[0], '');
        }

        // -----------------------------------------------------------
        // 2. CÓDIGO PRESTADOR (Col 2)
        // -----------------------------------------------------------
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_002',
                "Dato obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO IDENTIFICACIÓN (Col 3)
        // -----------------------------------------------------------
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_003',
                "Valor no permitido.", $cols[2], $tipoId);
        }

        // -----------------------------------------------------------
        // 5. FECHA INGRESO (Col 5)
        // -----------------------------------------------------------
        if ($fecIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_004',
                "Dato obligatorio.", $cols[4], '');
        } elseif (!self::isValidDate($fecIngreso)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_004_F',
                "Formato fecha inválido.", $cols[4], $fecIngreso);
        }

        // -----------------------------------------------------------
        // 6. HORA INGRESO (Col 6)
        // -----------------------------------------------------------
        if ($horaIngreso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_005',
                "Dato obligatorio.", $cols[5], '');
        } elseif (!self::isValidTime($horaIngreso)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_005_F',
                "Formato hora inválido (HH:MM).", $cols[5], $horaIngreso);
        }

        // -----------------------------------------------------------
        // 8. CAUSA EXTERNA (Col 8)
        // -----------------------------------------------------------
        $allowedCausa = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'];
        if ($causaExt === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_007',
                "Dato obligatorio.", $cols[7], '');
        } elseif (!in_array($causaExt, $allowedCausa)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_006',
                "Valor no permitido.", $cols[7], $causaExt);
        }

        // -----------------------------------------------------------
        // 9. DIAGNÓSTICO SALIDA (Col 9)
        // -----------------------------------------------------------
        if ($diagSalida === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_008',
                "Dato obligatorio.", $cols[8], '');
        }

        // -----------------------------------------------------------
        // 13. DESTINO SALIDA (Col 13)
        // -----------------------------------------------------------
        // 1=Alta, 2=Remisión, 3=Casa, etc. (Validar según norma vigente si tienes la lista, aquí solo empty)
        if ($destino === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_009',
                "Dato obligatorio.", $cols[12], '');
        } elseif (!in_array($destino, ['1','2','3','4','5'])) { // Asumiendo valores estándar
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_009_V',
                "Valor no permitido.", $cols[12], $destino);
        }

        // -----------------------------------------------------------
        // 14. ESTADO SALIDA (Col 14)
        // -----------------------------------------------------------
        // 1=Vivo, 2=Muerto
        if ($estado === '') {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_EST',
                "Dato obligatorio.", $cols[13], '');
        } elseif (!in_array($estado, ['1', '2'])) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_EST_V',
                "Valor no permitido (1=Vivo, 2=Muerto).", $cols[13], $estado);
        }

        // Validación cruzada Estado vs Causa Muerte
        if ($estado === '2' && $causaMuerte === '') {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_MUE',
                "Si estado es Muerto, Causa Muerte es obligatoria.", $cols[14], '');
        }

        // -----------------------------------------------------------
        // 16. FECHA SALIDA (Col 16)
        // -----------------------------------------------------------
        if ($fecSalida === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_010',
                "Dato obligatorio.", $cols[15], '');
        } elseif (!self::isValidDate($fecSalida)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_010_F',
                "Formato fecha inválido.", $cols[15], $fecSalida);
        }

        // Lógica Fechas (Ingreso <= Salida)
        if (self::isValidDate($fecIngreso) && self::isValidDate($fecSalida)) {
            $dIn = \DateTime::createFromFormat('d/m/Y', $fecIngreso);
            $dOut = \DateTime::createFromFormat('d/m/Y', $fecSalida);
            if ($dIn > $dOut) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_DATE_SEQ',
                    "Fecha Ingreso mayor a Salida.", $cols[15], "$fecIngreso > $fecSalida");
            }
        }

        // -----------------------------------------------------------
        // 17. HORA SALIDA (Col 17)
        // -----------------------------------------------------------
        if ($horaSalida === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_011',
                "Dato obligatorio.", $cols[16], '');
        } elseif (!self::isValidTime($horaSalida)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AU_ERROR_011_F',
                "Formato hora inválido.", $cols[16], $horaSalida);
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

    private static function isValidTime(string $time): bool {
        // Formato HH:MM
        return preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $time);
    }
}
