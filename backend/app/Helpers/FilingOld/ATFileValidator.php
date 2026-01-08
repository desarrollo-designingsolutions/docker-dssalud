<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class ATFileValidator
{
    /**
     * Valida el archivo AT (Otros Servicios).
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
            5 => 'Columna 6: Tipo de servicio',
            6 => 'Columna 7: Codigo del servicio',
            7 => 'Columna 8: Nombre del servicio',
            8 => 'Columna 9: Cantidad',
            9 => 'Columna 10: Valor unitario',
            10 => 'Columna 11: Valor total',
        ];

        // Extracción de variables
        $numFactura   = $data[0] ?? '';
        $codPrestador = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $tipoServicio = $data[5] ?? '';
        $nomServicio  = $data[7] ?? '';

        // -----------------------------------------------------------
        // 1. NÚMERO DE FACTURA (Col 1)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_001',
                "El dato registrado es obligatorio.", $cols[0], '');
        }

        // -----------------------------------------------------------
        // 2. CÓDIGO PRESTADOR (Col 2)
        // -----------------------------------------------------------
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_002',
                "El dato registrado es obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO IDENTIFICACIÓN (Col 3)
        // -----------------------------------------------------------
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_003',
                "El dato ingresado no es permitido", $cols[2], $tipoId);
        }

        // -----------------------------------------------------------
        // 6. TIPO DE SERVICIO (Col 6)
        // -----------------------------------------------------------
        if ($tipoServicio === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_004',
                "El dato registrado es obligatorio.", $cols[5], '');
        }

        // -----------------------------------------------------------
        // 8. NOMBRE DEL SERVICIO (Col 8)
        // -----------------------------------------------------------
        if ($nomServicio === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AT_ERROR_005',
                "El dato registrado es obligatorio.", $cols[7], ''); // Corregido mensaje original
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
