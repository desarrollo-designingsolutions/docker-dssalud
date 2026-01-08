<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;

class USFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Tipo de identificación',
            1 => 'Columna 2: Número de identificación',
            // ... resto de mapeo igual ...
            8 => 'Columna 9: Edad',
            9 => 'Columna 10: Unidad de medida',
            10 => 'Columna 11: Sexo',
            11 => 'Columna 12: Depto',
            12 => 'Columna 13: Municipio',
            13 => 'Columna 14: Zona',
        ];

        $tipoId = $data[0] ?? '';
        $numId  = $data[1] ?? '';
        $tipoUsu = $data[3] ?? '';
        $ape1   = $data[4] ?? '';
        $nom1   = $data[6] ?? '';
        $edad   = $data[8] ?? '';
        $unidad = $data[9] ?? '';
        $sexo   = $data[10] ?? '';
        $dep    = $data[11] ?? '';
        $mun    = $data[12] ?? '';
        $zona   = $data[13] ?? '';

        // TIPO ID
        $allowedTypes = ['CC', 'CE', 'CD', 'PA', 'SC', 'PE', 'RE', 'RC', 'TI', 'CN', 'AS', 'MS', 'DE', 'PT', 'SI'];
        if (!in_array($tipoId, $allowedTypes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_001', $cols[0], $tipoId, $tipoId);
        }

        // NUMERO ID
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
            if ($rule['numeric'] && !empty($numId) && !ctype_digit($numId)) {
                self::logError($batchId, $rowNumber, $fileName, $data, $rule['errNum'], $cols[1], $numId);
            }
            if (strlen($numId) > $rule['max']) {
                self::logError($batchId, $rowNumber, $fileName, $data, $rule['errLen'], $cols[1], $numId, $rule['max']);
            }
        }

        if ($tipoId == 'TI' && !ctype_digit($numId) && !empty($numId)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_017', $cols[1], $numId);
        }

        // EDAD
        if ($edad === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_026', $cols[8], '');
        } elseif (!ctype_digit($edad)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_AGE', $cols[8], $edad);
        }

        // UNIDAD MEDIDA
        if ($unidad === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_029', $cols[9], '');
        } elseif (!in_array($unidad, ['1', '2', '3'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_028', $cols[9], $unidad);
        }

        // REGLAS CRUZADAS UNIDAD
        if (in_array($tipoId, ['CC', 'CE', 'AS']) && $unidad !== '1') {
            $code = ($tipoId == 'CC') ? 'FILE_US_ERROR_003' : (($tipoId == 'CE') ? 'FILE_US_ERROR_004' : 'FILE_US_ERROR_007');
            self::logError($batchId, $rowNumber, $fileName, $data, $code, $cols[9], $unidad);
        }
        if ($tipoId == 'TI' && $unidad !== '1') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_006', $cols[9], $unidad);
        }
        if ($tipoId === 'CN' && $unidad !== '3') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_022', $cols[9], $unidad);
        }

        // TIPO USUARIO
        if (!in_array($tipoUsu, ['1','2','3','4','5','6','7','8'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_023', $cols[3], $tipoUsu);
        }

        // NOMBRES Y APELLIDOS
        if ($ape1 === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_024', $cols[4], '');
        if ($nom1 === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_025', $cols[6], '');

        // SEXO
        if ($sexo === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_030', $cols[10], '');
        } elseif (!in_array($sexo, ['M', 'F'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_034', $cols[10], $sexo);
        }

        // UBICACION
        if (empty($dep)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_032', $cols[11], '');
        if (empty($mun)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_033', $cols[12], '');

        if ($zona === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_033', $cols[13], ''); // Reuso codigo segun tu original
        } elseif (!in_array($zona, ['U', 'R'])) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_US_ERROR_033', $cols[13], $zona);
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
}
