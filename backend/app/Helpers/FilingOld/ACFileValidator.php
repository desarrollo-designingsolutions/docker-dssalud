<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class ACFileValidator
{
    /**
     * Valida el archivo AC (Consultas).
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
            4 => 'Columna 5: Fecha de la consulta',
            5 => 'Columna 6: Número de autorización',
            6 => 'Columna 7: Código de la consulta',
            7 => 'Columna 8: Finalidad de la consulta',
            8 => 'Columna 9: Causa externa',
            9 => 'Columna 10: Código de diagnóstico principal',
            10 => 'Columna 11: Diagnóstico relacionado 1',
            11 => 'Columna 12: Diagnóstico relacionado 2',
            12 => 'Columna 13: Diagnóstico relacionado 3',
            13 => 'Columna 14: Tipo de diagnóstico principal',
            14 => 'Columna 15: Valor de la consulta',
            15 => 'Columna 16: Valor de la cuota moderadora',
            16 => 'Columna 17: Valor neto a pagar',
        ];

        // Extracción de variables
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
        $valCuota     = $data[15] ?? '';
        $valNeto      = $data[16] ?? '';

        // -----------------------------------------------------------
        // 1. NÚMERO DE FACTURA (Col 1)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_001',
                "Dato obligatorio.", $cols[0], '');
        }

        // -----------------------------------------------------------
        // 2. CÓDIGO PRESTADOR (Col 2)
        // -----------------------------------------------------------
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_002',
                "Dato obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO IDENTIFICACIÓN (Col 3)
        // -----------------------------------------------------------
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_003',
                "Valor no permitido.", $cols[2], $tipoId);
        }

        // -----------------------------------------------------------
        // 4. NUMERO IDENTIFICACIÓN (Col 4)
        // -----------------------------------------------------------
        if ($numIdUsuario === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_003_ID',
                "Dato obligatorio.", $cols[3], '');
        }

        // -----------------------------------------------------------
        // 5. FECHA CONSULTA (Col 5)
        // -----------------------------------------------------------
        if ($fecConsulta === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_004',
                "Dato obligatorio.", $cols[4], '');
        } elseif (!self::isValidDate($fecConsulta)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_004_F',
                "Formato fecha inválido.", $cols[4], $fecConsulta);
        }

        // -----------------------------------------------------------
        // 7. CÓDIGO CONSULTA (Col 7)
        // -----------------------------------------------------------
        // Lista resumida de códigos CUPS (Asegúrate de tener la lista completa actualizada)
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
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_005',
                "Código CUPS no permitido o no existe en la lista.", $cols[6], $codConsulta);
        }

        // -----------------------------------------------------------
        // 8. FINALIDAD (Col 8)
        // -----------------------------------------------------------
        $allowedFinalidad = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10'];
        if (!in_array($finalidad, $allowedFinalidad)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_006',
                "Valor no permitido.", $cols[7], $finalidad);
        }

        // -----------------------------------------------------------
        // 9. CAUSA EXTERNA (Col 9)
        // -----------------------------------------------------------
        $allowedCausa = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15'];

        if ($causa === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_008',
                "Dato obligatorio.", $cols[8], '');
        } elseif (!in_array($causa, $allowedCausa)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_007',
                "Valor no permitido.", $cols[8], $causa);
        }

        // -----------------------------------------------------------
        // 10. DIAGNÓSTICO PRINCIPAL (Col 10)
        // -----------------------------------------------------------
        if ($diagPpal === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_009',
                "Dato obligatorio.", $cols[9], '');
        }

        // -----------------------------------------------------------
        // 14. TIPO DIAGNÓSTICO (Col 14)
        // -----------------------------------------------------------
        if ($tipoDiag === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_011',
                "Dato obligatorio.", $cols[13], '');
        } elseif (!in_array($tipoDiag, ['1', '2', '3'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_012',
                "Valor inválido (1=Impresión, 2=Confirmado Nuevo, 3=Confirmado Repetido).", $cols[13], $tipoDiag);
        }

        // -----------------------------------------------------------
        // VALORES (Col 15, 16, 17)
        // -----------------------------------------------------------
        // Valor Consulta
        if ($valConsulta === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_013',
                "Dato obligatorio.", $cols[14], '');
        } elseif (!is_numeric($valConsulta)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_013_N',
                "Debe ser numérico.", $cols[14], $valConsulta);
        }

        // Valor Neto
        if ($valNeto === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_014',
                "Dato obligatorio.", $cols[16], '');
        } elseif (!is_numeric($valNeto)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AC_ERROR_014_N',
                "Debe ser numérico.", $cols[16], $valNeto);
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
