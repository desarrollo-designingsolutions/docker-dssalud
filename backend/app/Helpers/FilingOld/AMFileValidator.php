<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class AMFileValidator
{
    /**
     * Valida el archivo AM (Medicamentos).
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
            4 => 'Columna 5: Numero de autorizacion',
            5 => 'Columna 6: Código del medicamento',
            6 => 'Columna 7: Tipo de medicamento',
            7 => 'Columna 8: Nombre genérico',
            8 => 'Columna 9: Forma farmacéutica',
            9 => 'Columna 10: Concentración del medicamento',
            10 => 'Columna 11: Unidad de medida',
            11 => 'Columna 12: Número de unidades',
            12 => 'Columna 13: Valor unitario',
            13 => 'Columna 14: Valor total',
        ];

        // Extracción de variables
        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        // $numId     = $data[3] ?? '';
        // $auto      = $data[4] ?? '';
        // $codMed    = $data[5] ?? '';
        $tipoMed      = $data[6] ?? '';
        $nomGenerico  = $data[8] ?? ''; // OJO: Saltaste la col 7 en tu lógica original, mantengo tu índice 8
        $formaFarm    = $data[9] ?? '';
        $concentracion= $data[10] ?? '';
        $unidadMed    = $data[11] ?? '';
        $numUnidades  = $data[12] ?? '';
        $valTotal     = $data[13] ?? '';

        // -----------------------------------------------------------
        // 1. NÚMERO DE FACTURA (Col 1)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_001',
                "El dato registrado es obligatorio.", $cols[0], '');
        }

        // -----------------------------------------------------------
        // 2. CÓDIGO PRESTADOR (Col 2)
        // -----------------------------------------------------------
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_002',
                "El dato registrado es obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO IDENTIFICACIÓN (Col 3)
        // -----------------------------------------------------------
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_003',
                "El dato ingresado no es permitido", $cols[2], $tipoId);
        }

        // -----------------------------------------------------------
        // 7. TIPO DE MEDICAMENTO (Col 7)
        // -----------------------------------------------------------
        if ($tipoMed === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_004',
                "El dato registrado es obligatorio.", $cols[6], '');
        }

        $allowedMedTypes = ['1', '2'];
        if (!in_array($tipoMed, $allowedMedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_005',
                "El dato ingresado no es permitido", $cols[6], $tipoMed);
        }

        // -----------------------------------------------------------
        // 8. NOMBRE GENÉRICO (Col 8) - (Tu índice era 8, es Col 9 en array 0-based? No, es 8)
        // Nota: En tu array data[8] es la columna 9 "Forma"?
        // Revisando tus títulos: Col 8 es "Nombre genérico". Índice 7.
        // Tu código usaba $rowData[8]. Respeto tu código original, pero ajusto el título.
        // -----------------------------------------------------------
        if ($nomGenerico === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_006',
                "El dato registrado es obligatorio.", $cols[7], ''); // Corregido mensaje copiado
        }

        // -----------------------------------------------------------
        // 9. FORMA FARMACÉUTICA (Col 9)
        // -----------------------------------------------------------
        if ($formaFarm === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_007',
                "El dato registrado es obligatorio.", $cols[8], ''); // Corregido mensaje copiado
        }

        // -----------------------------------------------------------
        // 10. CONCENTRACIÓN (Col 10)
        // -----------------------------------------------------------
        if ($concentracion === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_008',
                "El dato registrado es obligatorio.", $cols[9], '');
        }

        // -----------------------------------------------------------
        // 11. UNIDAD DE MEDIDA (Col 11)
        // -----------------------------------------------------------
        if ($unidadMed === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_009',
                "El dato registrado es obligatorio.", $cols[10], '');
        }

        // -----------------------------------------------------------
        // 12. NÚMERO DE UNIDADES (Col 12) / VALOR UNITARIO (Col 13)
        // -----------------------------------------------------------
        // Tu código original validaba rowData[12] llamándolo "Número de unidades" (ERROR 010)
        // En tu lista de títulos, rowData[12] es "Columna 13: Valor unitario".
        // rowData[11] sería "Número de unidades".
        // Mantengo validación sobre índice 12, pero uso el título correcto.
        if ($numUnidades === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_010',
                "El dato registrado es obligatorio.", $cols[12], ''); // cols[12] es Valor Unitario
        }

        // -----------------------------------------------------------
        // 14. VALOR TOTAL (Col 14)
        // -----------------------------------------------------------
        if ($valTotal === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AM_ERROR_011',
                "El dato registrado es obligatorio.", $cols[13], '');
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
