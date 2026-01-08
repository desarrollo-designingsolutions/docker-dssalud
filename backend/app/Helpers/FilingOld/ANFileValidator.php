<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class ANFileValidator
{
    /**
     * Valida el archivo AN (Recién Nacidos).
     */
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        // 1. Preparar datos
        $data = array_map('trim', explode(',', $rowData));

        // 2. Mapeo de columnas (UX)
        $cols = [
            0 => 'Columna 1: Número de la factura',
            1 => 'Columna 2: Código del prestador de servicios de salud',
            2 => 'Columna 3: Tipo de identificación de la madre',
            3 => 'Columna 4: Numero de identificacion de la madre en el Sistema',
            4 => 'Columna 5: Fecha de nacimiento del recién nacido',
            5 => 'Columna 6: Hora de nacimiento',
            6 => 'Columna 7: Edad gestacional',
            7 => 'Columna 8: Control prenatal',
            8 => 'Columna 9: Sexo',
            9 => 'Columna 10: Peso',
            10 => 'Columna 11: Diagnóstico del recién nacido',
            11 => 'Columna 12: Causa básica de muerte',
            12 => 'Columna 13: Fecha de muerte del recién nacido',
            13 => 'Columna 14: Hora de muerte del recién nacido',
        ];

        // Extracción de variables para legibilidad
        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoIdMadre  = $data[2] ?? '';
        // $numIdMadre = $data[3] ?? ''; // No tenía validación en tu código original
        $fecNac       = $data[4] ?? '';
        $horaNac      = $data[5] ?? '';
        $edadGest     = $data[6] ?? '';
        $ctrlPrenatal = $data[7] ?? '';
        $sexo         = $data[8] ?? '';
        $peso         = $data[9] ?? '';

        // -----------------------------------------------------------
        // 1. NÚMERO DE FACTURA (Col 1)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_001',
                "El dato registrado es obligatorio.", $cols[0], '');
        }

        // -----------------------------------------------------------
        // 2. CÓDIGO PRESTADOR (Col 2)
        // -----------------------------------------------------------
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_002',
                "El dato registrado es obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO IDENTIFICACIÓN MADRE (Col 3)
        // -----------------------------------------------------------
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoIdMadre, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_003',
                "El dato registrado no es un valor permitido.", $cols[2], $tipoIdMadre);
        }

        // -----------------------------------------------------------
        // 5. FECHA NACIMIENTO (Col 5)
        // -----------------------------------------------------------
        if ($fecNac === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_004',
                "El dato registrado es obligatorio.", $cols[4], '');
        }

        // -----------------------------------------------------------
        // 6. HORA NACIMIENTO (Col 6)
        // -----------------------------------------------------------
        if ($horaNac === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_005',
                "El dato registrado es obligatorio.", $cols[5], '');
        }

        // -----------------------------------------------------------
        // 7. EDAD GESTACIONAL (Col 7)
        // -----------------------------------------------------------
        if ($edadGest === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_006',
                "El dato registrado es obligatorio.", $cols[6], '');
        }

        // -----------------------------------------------------------
        // 8. CONTROL PRENATAL (Col 8)
        // -----------------------------------------------------------
        if ($ctrlPrenatal === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_007',
                "El dato registrado es obligatorio.", $cols[7], '');
        }

        $allowedCtrl = ['1', '2'];
        if (!in_array($ctrlPrenatal, $allowedCtrl)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_008',
                "El dato registrado no es un valor permitido.", $cols[7], $ctrlPrenatal);
        }

        // -----------------------------------------------------------
        // 9. SEXO (Col 9)
        // -----------------------------------------------------------
        if ($sexo === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_009',
                "El dato registrado es obligatorio.", $cols[8], '');
        }

        $allowedSexo = ['1', '2']; // Mantenido según tu código original (numérico para AN?)
        if (!in_array($sexo, $allowedSexo)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_010',
                "El dato registrado no es un valor permitido.", $cols[8], $sexo);
        }

        // -----------------------------------------------------------
        // 10. PESO (Col 10)
        // -----------------------------------------------------------
        if ($peso === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AN_ERROR_011',
                "El dato registrado es obligatorio.", $cols[9], '');
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
}
