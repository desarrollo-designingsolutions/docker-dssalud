<?php

namespace App\Helpers\InvoiceAudit;

class ErrorCodes
{
    // ========================================================================
    // 📄 ERRORES DE CONTENIDO CSV
    // ========================================================================
    const CSV_MISSING_COLUMNS = ['code' => 'CSV_001', 'message' => 'Faltan columnas obligatorias: %s'];
    const CSV_INVALID_FORMAT = ['code' => 'CSV_002', 'message' => 'Fila %d: El campo %s tiene un formato inválido o está vacío.'];
    const CSV_EMPTY_FILE = ['code' => 'CSV_003', 'message' => 'El archivo CSV está vacío.'];
    const THIRD_NOT_FOUND = ['code' => 'CSV_004', 'message' => 'Fila %d: El NIT %s no fue asignado a su compañia o no existe.'];


    /**
     * Obtiene el mensaje formateado.
     */
    public static function getMessage(string $constantName, ...$args): string
    {
        if (defined("self::$constantName")) {
            $error = constant("self::$constantName");
            $message = $error['message'] ?? 'Error desconocido.';

            return empty($args) ? $message : vsprintf($message, $args);
        }

        return "Código de error no encontrado: $constantName";
    }

    /**
     * Obtiene el código (string corto) de una constante.
     */
    public static function getCode(string $constantName): string
    {
        if (defined("self::$constantName")) {
            return constant("self::$constantName")['code'];
        }

        return 'UNKNOWN_CODE';
    }
}
