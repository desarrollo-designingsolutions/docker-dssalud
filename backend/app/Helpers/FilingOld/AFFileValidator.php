<?php

namespace App\Helpers\FilingOld;

use App\Helpers\Common\ErrorCollector;
use App\Helpers\FilingOld\ErrorCodes;
use Illuminate\Support\Facades\Redis;

class AFFileValidator
{
    public static function validate(string $fileName, string $rowData, int $rowNumber, string $batchId): void
    {
        $data = array_map('trim', explode(',', $rowData));

        $cols = [
            0 => 'Columna 1: Código del prestador',
            // ... resto cols
            5 => 'Columna 6: Fecha Expedición',
            6 => 'Columna 7: Fecha Inicio',
            7 => 'Columna 8: Fecha Final',
            8 => 'Columna 9: Cod Entidad',
        ];

        $codPrestador = $data[0] ?? '';
        $razonSocial  = $data[1] ?? '';
        $tipoId       = $data[2] ?? '';
        $numId        = $data[3] ?? '';
        $numFactura   = $data[4] ?? '';
        $fecExp       = $data[5] ?? '';
        $fecIni       = $data[6] ?? '';
        $fecFin       = $data[7] ?? '';
        $codEntidad   = $data[8] ?? '';

        // 1. Prestador
        if ($codPrestador === '') {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_001', $cols[0], '');
        }

        // Cruzada CT
        $providerCodeCT = Redis::connection('redis_6380')->hget("batch:{$batchId}:header_info", 'provider_code');
        if ($providerCodeCT && $codPrestador !== $providerCodeCT) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_CROSS', $cols[0], $codPrestador, $providerCodeCT);
        }

        // 2. Razon Social
        if ($razonSocial === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_003', $cols[1], '');

        // 3. Tipo ID
        $allowedPrefixes = ['NI', 'CC', 'CE', 'CD', 'PA', 'PE'];
        if (!in_array($tipoId, $allowedPrefixes)) {
            self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_005', $cols[2], $tipoId);
        }

        // 4. Num ID
        if ($numId === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_006', $cols[3], '');

        // 5. Factura
        if ($numFactura === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_007', $cols[4], '');

        // 6. Fechas
        if (!self::isValidDate($fecExp)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_008', $cols[5], $fecExp);
        if (!self::isValidDate($fecIni)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_009', $cols[6], $fecIni);
        if (!self::isValidDate($fecFin)) self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_010', $cols[7], $fecFin);

        if (self::isValidDate($fecIni) && self::isValidDate($fecFin)) {
            $dIni = \DateTime::createFromFormat('d/m/Y', $fecIni);
            $dFin = \DateTime::createFromFormat('d/m/Y', $fecFin);
            if ($dIni > $dFin) {
                self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_DATES', $cols[6], "$fecIni - $fecFin");
            }
        }

        // 9. Entidad
        if ($codEntidad === '') self::logError($batchId, $rowNumber, $fileName, $data, 'FILE_AF_ERROR_011', $cols[8], '');
    }

    private static function logError($batchId, $row, $fileName, $data, $constName, $colTitle, $val, ...$msgArgs) {
        $debugData = ['file' => $fileName, 'code' => ErrorCodes::getCode($constName), 'row_data' => $data];
        ErrorCollector::addError(
            $batchId, $row, $colTitle,
            "[" . ErrorCodes::getCode($constName) . "] " . ErrorCodes::getMessage($constName, ...$msgArgs),
            'R', $val, json_encode($debugData)
        );
    }

    private static function isValidDate(string $date): bool {
        if ($date === '') return false;
        $parts = explode('/', $date);
        return count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]);
    }
}
