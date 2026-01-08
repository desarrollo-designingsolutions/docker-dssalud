<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use Illuminate\Support\Facades\Redis;

class AFFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        // Mapeo de columnas (UX)
        $cols = [
            0 => 'Columna 1: Código del prestador',
            1 => 'Columna 2: Razón social',
            2 => 'Columna 3: Tipo ID',
            3 => 'Columna 4: Num ID Prestador',
            4 => 'Columna 5: Num Factura',
            5 => 'Columna 6: Fecha Expedición',
            6 => 'Columna 7: Fecha Inicio',
            7 => 'Columna 8: Fecha Final',
            8 => 'Columna 9: Cod Entidad',
            // ... resto de columnas
        ];

        // Variables
        $codPrestador = $data[0] ?? '';
        $razonSocial  = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $numId        = $data[3] ?? '';
        $numFactura   = $data[4] ?? '';
        $fecExp       = $data[5] ?? '';
        $fecIni       = $data[6] ?? '';
        $fecFin       = $data[7] ?? '';
        $codEntidad   = $data[8] ?? '';

        // -----------------------------------------------------------
        // 1. CÓDIGO PRESTADOR (Col 1)
        // -----------------------------------------------------------

        // A. Validación Obligatorio
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_001',
                "Dato obligatorio.", $cols[0], '');
        }

        // B. Validación Cruzada contra CT (OPTIMIZADA) 🚀
        // Recuperamos el código que guardamos en la Fase 2
        $providerCodeCT = Redis::connection('redis_6380')->hget("batch:{$batchId}:header_info", 'provider_code');

        if ($providerCodeCT && $codPrestador !== $providerCodeCT) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_CROSS',
                "No coincide con el CT ($providerCodeCT).", $cols[0], $codPrestador);
        }

        // -----------------------------------------------------------
        // 2. RAZÓN SOCIAL (Col 2)
        // -----------------------------------------------------------
        if ($razonSocial === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_003',
                "Dato obligatorio.", $cols[1], '');
        }

        // -----------------------------------------------------------
        // 3. TIPO ID PRESTADOR (Col 3)
        // -----------------------------------------------------------
        $allowedPrefixes = ['NI', 'CC', 'CE', 'CD', 'PA', 'PE'];
        if (!in_array($tipoId, $allowedPrefixes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_005',
                "Valor no permitido.", $cols[2], $tipoId);
        }

        // -----------------------------------------------------------
        // 4. NUM ID PRESTADOR (Col 4)
        // -----------------------------------------------------------
        if ($numId === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_006',
                "Dato obligatorio.", $cols[3], '');
        }

        // -----------------------------------------------------------
        // 5. NUM FACTURA (Col 5)
        // -----------------------------------------------------------
        if ($numFactura === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_007',
                "Dato obligatorio.", $cols[4], '');
        }

        // -----------------------------------------------------------
        // 6. FECHAS (Col 6, 7, 8)
        // -----------------------------------------------------------
        // Expedición
        if (!self::isValidDate($fecExp)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_008',
                "Fecha expedición inválida.", $cols[5], $fecExp);
        }
        // Inicio
        if (!self::isValidDate($fecIni)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_009',
                "Fecha inicio inválida.", $cols[6], $fecIni);
        }
        // Final
        if (!self::isValidDate($fecFin)) {
             self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_010',
                "Fecha final inválida.", $cols[7], $fecFin);
        }

        // Lógica: Inicio <= Final
        if (self::isValidDate($fecIni) && self::isValidDate($fecFin)) {
            $dIni = \DateTime::createFromFormat('d/m/Y', $fecIni);
            $dFin = \DateTime::createFromFormat('d/m/Y', $fecFin);
            if ($dIni > $dFin) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_DATES',
                    "Fecha Inicio mayor a Final.", $cols[6], "$fecIni - $fecFin");
            }
        }

        // -----------------------------------------------------------
        // 9. CÓDIGO ENTIDAD (Col 9)
        // -----------------------------------------------------------
        if ($codEntidad === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_011',
                "Dato obligatorio.", $cols[8], '');
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
        if ($date === '') return false; // Vacío es inválido aquí, si es opcional cambiar lógica
        $parts = explode('/', $date);
        if (count($parts) !== 3) return false;
        return checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]);
    }
}
