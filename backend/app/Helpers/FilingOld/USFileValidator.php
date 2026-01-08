<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;

class USFileValidator
{
    /**
     * Valida el archivo US (Usuarios).
     */
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        // Convertimos CSV string a Array
        $data = array_map('trim', explode(',', $rowData));

        // 1. Títulos Originales (Mejor UX)
        $cols = [
            0 => 'Columna 1: Tipo de identificación del usuario.',
            1 => 'Columna 2: Número de identificación del usuario del sistema.',
            2 => 'Columna 3: Código entidad administradora.',
            3 => 'Columna 4: Tipo de usuario.',
            4 => 'Columna 5: Primer apellido del usuario.',
            5 => 'Columna 6: Segundo apellido del usuario.',
            6 => 'Columna 7: Primer nombre del usuario.',
            7 => 'Columna 8: Segundo nombre del usuario.',
            8 => 'Columna 9: Edad.',
            9 => 'Columna 10: Unidad de medida de la edad.',
            10 => 'Columna 11: Sexo.',
            11 => 'Columna 12: Código del departamento de residencia habitual.',
            12 => 'Columna 13: Código del municipio de residencia habitual.',
            13 => 'Columna 14: Zona de residencia habitual.',
        ];

        // Variables para legibilidad interna
        $tipoId   = $data[0] ?? '';
        $numId    = $data[1] ?? '';
        // $codAdmin = $data[2] ?? '';
        $tipoUsu  = $data[3] ?? '';
        $ape1     = $data[4] ?? '';
        $nom1     = $data[6] ?? '';
        $edad     = $data[8] ?? '';
        $unidad   = $data[9] ?? '';
        $sexo     = $data[10] ?? '';
        $dep      = $data[11] ?? '';
        $mun      = $data[12] ?? '';
        $zona     = $data[13] ?? '';

        // -----------------------------------------------------------
        // VALIDACIÓN TIPO ID (Columna 1)
        // -----------------------------------------------------------
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];

        if (!in_array($tipoId, $allowedTypes)) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_001',
                "El valor '{$tipoId}' no es válido.",
                $cols[0],
                $tipoId
            );
        }

        // -----------------------------------------------------------
        // VALIDACIÓN NÚMERO ID (Columna 2)
        // -----------------------------------------------------------
        $rules = [
            'CC' => ['numeric' => true,  'max' => 10, 'errNum' => 'FILE_US_ERROR_002', 'errLen' => 'FILE_US_ERROR_009'],
            'TI' => ['numeric' => true,  'max' => 11, 'errNum' => 'FILE_US_ERROR_005', 'errLen' => 'FILE_US_ERROR_018'],
            'CE' => ['numeric' => false, 'max' => 6,  'errNum' => null,                'errLen' => 'FILE_US_ERROR_010'],
            'CD' => ['numeric' => false, 'max' => 16, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_011'],
            'PA' => ['numeric' => false, 'max' => 16, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_012'],
            'SC' => ['numeric' => false, 'max' => 16, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_013'],
            'PE' => ['numeric' => false, 'max' => 15, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_014'],
            'RE' => ['numeric' => false, 'max' => 15, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_015'],
            'RC' => ['numeric' => false, 'max' => 11, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_016'],
            'CN' => ['numeric' => false, 'max' => 9,  'errNum' => null,                'errLen' => 'FILE_US_ERROR_019'],
            'AS' => ['numeric' => false, 'max' => 10, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_020'],
            'MS' => ['numeric' => false, 'max' => 12, 'errNum' => null,                'errLen' => 'FILE_US_ERROR_021'],
        ];

        if (isset($rules[$tipoId])) {
            $rule = $rules[$tipoId];

            // Validar numérico
            if ($rule['numeric'] && !empty($numId) && !ctype_digit($numId)) {
                self::logError(
                    $batchId,
                    $rowNumber,
                    $fileName,
                    $data,
                    $rule['errNum'],
                    "El valor debe ser numérico.",
                    $cols[1],
                    $numId
                );
            }
            // Validar longitud
            if (strlen($numId) > $rule['max']) {
                self::logError(
                    $batchId,
                    $rowNumber,
                    $fileName,
                    $data,
                    $rule['errLen'],
                    "Longitud excede el máximo permitido ({$rule['max']}).",
                    $cols[1],
                    $numId
                );
            }
        }

        // Validación TI (extra)
        if ($tipoId == 'TI' && !ctype_digit($numId) && !empty($numId)) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_017',
                "El valor debe ser numérico.",
                $cols[1],
                $numId
            );
        }

        // -----------------------------------------------------------
        // VALIDACIÓN EDAD (Columna 9)
        // -----------------------------------------------------------
        if ($edad === '') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_026',
                "El dato edad es obligatorio.",
                $cols[8],
                ''
            );
        } elseif (!ctype_digit($edad)) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_AGE',
                "La edad debe ser un número.",
                $cols[8],
                $edad
            );
        }

        // -----------------------------------------------------------
        // VALIDACIÓN UNIDAD MEDIDA (Columna 10)
        // -----------------------------------------------------------
        if ($unidad === '') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_029',
                "El registro del dato es obligatorio.",
                $cols[9],
                ''
            );
        } elseif (!in_array($unidad, ['1', '2', '3'])) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_028',
                "Dato inválido (Permitido: 1, 2, 3).",
                $cols[9],
                $unidad
            );
        }

        // Reglas cruzadas (Unidad vs Tipo ID)
        if (in_array($tipoId, ['CC', 'CE', 'AS']) && $unidad !== '1') {
            $code = ($tipoId == 'CC') ? 'FILE_US_ERROR_003' : (($tipoId == 'CE') ? 'FILE_US_ERROR_004' : 'FILE_US_ERROR_007');

            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                $code,
                "El campo unidad de medida es diferente a 1.",
                $cols[9],
                $unidad
            );
        }

        if ($tipoId == 'TI' && $unidad !== '1') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_006',
                "El campo unidad de medida es diferente a 1.",
                $cols[9],
                $unidad
            );
        }

        if ($tipoId === 'CN' && $unidad !== '3') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_022',
                "El campo unidad de medida es diferente a 3.",
                $cols[9],
                $unidad
            );
        }

        // -----------------------------------------------------------
        // VALIDACIÓN TIPO USUARIO (Columna 4)
        // -----------------------------------------------------------
        if (!in_array($tipoUsu, ['1', '2', '3', '4', '5', '6', '7', '8'])) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_023',
                "El dato registrado no es un valor permitido.",
                $cols[3],
                $tipoUsu
            );
        }

        // -----------------------------------------------------------
        // VALIDACIÓN APELLIDOS Y NOMBRES (Columnas 5 y 7)
        // -----------------------------------------------------------
        if ($ape1 === '') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_024',
                "El primer apellido es un dato obligatorio.",
                $cols[4],
                ''
            );
        }
        if ($nom1 === '') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_025',
                "El primer nombre es un dato obligatorio.",
                $cols[6],
                ''
            );
        }

        // -----------------------------------------------------------
        // VALIDACIÓN SEXO (Columna 11)
        // -----------------------------------------------------------
        if ($sexo === '') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_030',
                "El registro del dato es obligatorio.",
                $cols[10],
                ''
            );
        } elseif (!in_array($sexo, ['M', 'F'])) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_034',
                "Dato inválido.",
                $cols[10],
                $sexo
            );
        }

        // -----------------------------------------------------------
        // VALIDACIÓN UBICACIÓN (Columnas 12, 13, 14)
        // -----------------------------------------------------------
        if (empty($dep)) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_032',
                "El registro del dato es obligatorio.",
                $cols[11],
                ''
            );
        }
        if (empty($mun)) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_033',
                "El registro del dato es obligatorio.",
                $cols[12],
                ''
            );
        }

        if ($zona === '') {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_033',
                "El registro del dato es obligatorio.",
                $cols[13],
                ''
            );
        } elseif (!in_array($zona, ['U', 'R'])) {
            self::logError(
                $batchId,
                $rowNumber,
                $fileName,
                $data,
                'FILE_US_ERROR_033',
                "El dato registrado no es un valor permitido.",
                $cols[13],
                $zona
            );
        }
    }

    /**
     * Helper privado ajustado a tu clase ErrorCollector real.
     */
    private static function logError($batchId, $row, $fileName, $data, $code, $msg, $colTitle, $val)
    {

        // Preparamos la data original incluyendo el nombre del archivo para no perder ese dato
        $debugData = [
            'file' => $fileName,
            'code' => $code, // Guardamos el código aquí si no hay columna en DB para él
            'row_data' => $data
        ];

        // Llamada exacta a tu firma:
        // addError($batchId, $row, $colName, $msg, $type, $val, $originalData)

        ErrorCollector::addError(
            $batchId,           // 1. batchId
            $row,               // 2. rowNumber
            $colTitle,          // 3. columnName (ej: "Columna 1: Tipo...")
            "[$code] $msg",     // 4. errorMessage (Concatenamos el código: "[FILE_001] Error...")
            'R',                // 5. errorType
            $val,               // 6. errorValue
            json_encode($debugData) // 7. originalData (JSON)
        );
    }
}
