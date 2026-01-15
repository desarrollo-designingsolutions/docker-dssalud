<?php

namespace App\Helpers\FilingOld;

class ErrorCodes
{
    // ========================================================================
    // 📦 ERRORES DE ESTRUCTURA ZIP
    // ========================================================================
    const ZIP_CRITICAL_001 = ['code' => 'ZIP_CRITICAL_001', 'message' => 'El archivo ZIP no existe en el servidor.'];

    const ZIP_CRITICAL_002 = ['code' => 'ZIP_CRITICAL_002', 'message' => 'El archivo subido no tiene extensión .zip.'];

    const ZIP_CRITICAL_003 = ['code' => 'ZIP_CRITICAL_003', 'message' => 'El archivo no es un ZIP válido o está corrupto.'];

    const ZIP_CONTENT_001 = ['code' => 'ZIP_CONTENT_001', 'message' => 'El ZIP contiene carpetas (solo se permiten archivos en la raíz).'];

    const ZIP_CONTENT_002 = ['code' => 'ZIP_CONTENT_002', 'message' => 'El ZIP contiene %d archivos. El máximo permitido es 10.'];

    const ZIP_CONTENT_003 = ['code' => 'ZIP_CONTENT_003', 'message' => 'El ZIP contiene solo %d archivos. El mínimo requerido es 4.'];

    const ZIP_MISSING_AF = ['code' => 'ZIP_MISSING_AF', 'message' => 'Falta el archivo de transacciones (AF).'];

    const ZIP_MISSING_US = ['code' => 'ZIP_MISSING_US', 'message' => 'Falta el archivo de usuarios (US).'];

    const ZIP_MISSING_DETAIL = ['code' => 'ZIP_MISSING_DETAIL', 'message' => 'Falta al menos un archivo de detalle (AC, AP, AM o AT).'];

    // ========================================================================
    // 🏗️ ERRORES DE ESTRUCTURA INTERNA
    // ========================================================================
    const FILE_STRUCT_MISMATCH = ['code' => 'FILE_STRUCT_MISMATCH', 'message' => 'Archivo %s: Esperaba %d columnas, tiene %d.'];

    const ROW_EXCEPTION = ['code' => 'ROW_EXCEPTION', 'message' => 'Error crítico al procesar la fila: %s'];

    // ========================================================================
    // 📄 ERRORES DE ARCHIVO CT (Control)
    // ========================================================================
    const FILE_CT_ERROR_001 = ['code' => 'FILE_CT_ERROR_001', 'message' => 'El valor no es numérico.'];

    const FILE_CT_ERROR_002 = ['code' => 'FILE_CT_ERROR_002', 'message' => 'Debe tener 12 caracteres.'];

    const FILE_CT_ERROR_003 = ['code' => 'FILE_CT_ERROR_003', 'message' => 'Fecha inválida (formato dd/mm/aaaa).'];

    const FILE_CT_ERROR_004 = ['code' => 'FILE_CT_ERROR_004', 'message' => 'La fecha es futura.'];

    const FILE_CT_ERROR_005 = ['code' => 'FILE_CT_ERROR_005', 'message' => 'El prefijo del archivo no es válido.'];

    const FILE_CT_ERROR_006 = ['code' => 'FILE_CT_ERROR_006', 'message' => 'El archivo está duplicado en este CT.'];

    const FILE_CT_ERROR_007 = ['code' => 'FILE_CT_ERROR_007', 'message' => 'El total no es numérico.'];

    const FILE_CT_ERROR_008 = ['code' => 'FILE_CT_ERROR_008', 'message' => 'El archivo referenciado no existe en el ZIP.'];

    const FILE_CT_ERROR_009 = ['code' => 'FILE_CT_ERROR_009', 'message' => 'Inconsistencia: CT dice %d registros, archivo real tiene %d.'];

    // ========================================================================
    // 👥 ERRORES DE ARCHIVO US (Usuarios)
    // ========================================================================
    const FILE_US_ERROR_001 = ['code' => 'FILE_US_ERROR_001', 'message' => 'El tipo de identificación no es válido.'];

    const FILE_US_ERROR_002 = ['code' => 'FILE_US_ERROR_002', 'message' => 'El valor debe ser numérico.'];

    const FILE_US_ERROR_009 = ['code' => 'FILE_US_ERROR_009', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_005 = ['code' => 'FILE_US_ERROR_005', 'message' => 'El valor debe ser numérico.'];

    const FILE_US_ERROR_018 = ['code' => 'FILE_US_ERROR_018', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_010 = ['code' => 'FILE_US_ERROR_010', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_011 = ['code' => 'FILE_US_ERROR_011', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_012 = ['code' => 'FILE_US_ERROR_012', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_013 = ['code' => 'FILE_US_ERROR_013', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_014 = ['code' => 'FILE_US_ERROR_014', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_015 = ['code' => 'FILE_US_ERROR_015', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_016 = ['code' => 'FILE_US_ERROR_016', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_019 = ['code' => 'FILE_US_ERROR_019', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_020 = ['code' => 'FILE_US_ERROR_020', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_021 = ['code' => 'FILE_US_ERROR_021', 'message' => 'Longitud excede el máximo permitido.'];

    const FILE_US_ERROR_017 = ['code' => 'FILE_US_ERROR_017', 'message' => 'El valor debe ser numérico.'];

    const FILE_US_ERROR_026 = ['code' => 'FILE_US_ERROR_026', 'message' => 'El dato edad es obligatorio.'];

    const FILE_US_ERROR_AGE = ['code' => 'FILE_US_ERROR_AGE', 'message' => 'La edad debe ser un número.'];

    const FILE_US_ERROR_029 = ['code' => 'FILE_US_ERROR_029', 'message' => 'El registro del dato es obligatorio.'];

    const FILE_US_ERROR_028 = ['code' => 'FILE_US_ERROR_028', 'message' => 'Dato inválido (Permitido: 1, 2, 3).'];

    const FILE_US_ERROR_003 = ['code' => 'FILE_US_ERROR_003', 'message' => 'El campo unidad de medida es diferente a 1.'];

    const FILE_US_ERROR_004 = ['code' => 'FILE_US_ERROR_004', 'message' => 'El campo unidad de medida es diferente a 1.'];

    const FILE_US_ERROR_007 = ['code' => 'FILE_US_ERROR_007', 'message' => 'El campo unidad de medida es diferente a 1.'];

    const FILE_US_ERROR_006 = ['code' => 'FILE_US_ERROR_006', 'message' => 'El campo unidad de medida es diferente a 1.'];

    const FILE_US_ERROR_022 = ['code' => 'FILE_US_ERROR_022', 'message' => 'El campo unidad de medida es diferente a 3.'];

    const FILE_US_ERROR_023 = ['code' => 'FILE_US_ERROR_023', 'message' => 'El dato registrado no es un valor permitido.'];

    const FILE_US_ERROR_024 = ['code' => 'FILE_US_ERROR_024', 'message' => 'El primer apellido es un dato obligatorio.'];

    const FILE_US_ERROR_025 = ['code' => 'FILE_US_ERROR_025', 'message' => 'El primer nombre es un dato obligatorio.'];

    const FILE_US_ERROR_030 = ['code' => 'FILE_US_ERROR_030', 'message' => 'El registro del dato es obligatorio.'];

    const FILE_US_ERROR_034 = ['code' => 'FILE_US_ERROR_034', 'message' => 'Dato inválido.'];

    const FILE_US_ERROR_032 = ['code' => 'FILE_US_ERROR_032', 'message' => 'El registro del dato es obligatorio.'];

    const FILE_US_ERROR_033 = ['code' => 'FILE_US_ERROR_033', 'message' => 'El registro del dato es obligatorio.']; // Usado también para Zona

    // ========================================================================
    // 🏥 ERRORES DE ARCHIVO AF (Transacciones)
    // ========================================================================
    const FILE_AF_ERROR_001 = ['code' => 'FILE_AF_ERROR_001', 'message' => 'Dato obligatorio.'];

    const FILE_AF_ERROR_CROSS = ['code' => 'FILE_AF_ERROR_CROSS', 'message' => 'No coincide con el CT (%s).'];

    const FILE_AF_ERROR_003 = ['code' => 'FILE_AF_ERROR_003', 'message' => 'Dato obligatorio.'];

    const FILE_AF_ERROR_005 = ['code' => 'FILE_AF_ERROR_005', 'message' => 'Valor no permitido.'];

    const FILE_AF_ERROR_006 = ['code' => 'FILE_AF_ERROR_006', 'message' => 'Dato obligatorio.'];

    const FILE_AF_ERROR_007 = ['code' => 'FILE_AF_ERROR_007', 'message' => 'Dato obligatorio.'];

    const FILE_AF_ERROR_008 = ['code' => 'FILE_AF_ERROR_008', 'message' => 'Fecha expedición inválida.'];

    const FILE_AF_ERROR_009 = ['code' => 'FILE_AF_ERROR_009', 'message' => 'Fecha inicio inválida.'];

    const FILE_AF_ERROR_010 = ['code' => 'FILE_AF_ERROR_010', 'message' => 'Fecha final inválida.'];

    const FILE_AF_ERROR_DATES = ['code' => 'FILE_AF_ERROR_DATES', 'message' => 'Fecha Inicio mayor a Final.'];

    const FILE_AF_ERROR_011 = ['code' => 'FILE_AF_ERROR_011', 'message' => 'Dato obligatorio.'];

    // ========================================================================
    // 📋 ERRORES DE ARCHIVO AC (Consultas)
    // ========================================================================
    const FILE_AC_ERROR_001 = ['code' => 'FILE_AC_ERROR_001', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_002 = ['code' => 'FILE_AC_ERROR_002', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_003 = ['code' => 'FILE_AC_ERROR_003', 'message' => 'Valor no permitido.'];

    const FILE_AC_ERROR_003_ID = ['code' => 'FILE_AC_ERROR_003_ID', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_004 = ['code' => 'FILE_AC_ERROR_004', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_004_F = ['code' => 'FILE_AC_ERROR_004_F', 'message' => 'Formato fecha inválido.'];

    const FILE_AC_ERROR_005 = ['code' => 'FILE_AC_ERROR_005', 'message' => 'Código CUPS no permitido o no existe en la lista.'];

    const FILE_AC_ERROR_006 = ['code' => 'FILE_AC_ERROR_006', 'message' => 'Valor no permitido.'];

    const FILE_AC_ERROR_007 = ['code' => 'FILE_AC_ERROR_007', 'message' => 'Valor no permitido.'];

    const FILE_AC_ERROR_008 = ['code' => 'FILE_AC_ERROR_008', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_009 = ['code' => 'FILE_AC_ERROR_009', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_011 = ['code' => 'FILE_AC_ERROR_011', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_012 = ['code' => 'FILE_AC_ERROR_012', 'message' => 'Valor inválido (1=Impresión, 2=Confirmado Nuevo, 3=Confirmado Repetido).'];

    const FILE_AC_ERROR_013 = ['code' => 'FILE_AC_ERROR_013', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_013_N = ['code' => 'FILE_AC_ERROR_013_N', 'message' => 'Debe ser numérico.'];

    const FILE_AC_ERROR_014 = ['code' => 'FILE_AC_ERROR_014', 'message' => 'Dato obligatorio.'];

    const FILE_AC_ERROR_014_N = ['code' => 'FILE_AC_ERROR_014_N', 'message' => 'Debe ser numérico.'];

    // ========================================================================
    // 💉 ERRORES DE ARCHIVO AP (Procedimientos)
    // ========================================================================
    const FILE_AP_ERROR_001 = ['code' => 'FILE_AP_ERROR_001', 'message' => 'El numero de factura es un dato obligatorio.'];

    const FILE_AP_ERROR_002 = ['code' => 'FILE_AP_ERROR_002', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AP_ERROR_003 = ['code' => 'FILE_AP_ERROR_003', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AP_ERROR_004 = ['code' => 'FILE_AP_ERROR_004', 'message' => 'El dato ingresado no es permitido.'];

    const FILE_AP_ERROR_005 = ['code' => 'FILE_AP_ERROR_005', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AP_ERROR_006 = ['code' => 'FILE_AP_ERROR_006', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AP_ERROR_006_F = ['code' => 'FILE_AP_ERROR_006_F', 'message' => 'Formato fecha inválido (dd/mm/aaaa).'];

    const FILE_AP_ERROR_007 = ['code' => 'FILE_AP_ERROR_007', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AP_ERROR_008 = ['code' => 'FILE_AP_ERROR_008', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AP_ERROR_009 = ['code' => 'FILE_AP_ERROR_009', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AP_ERROR_009_N = ['code' => 'FILE_AP_ERROR_009_N', 'message' => 'Debe ser un valor numérico.'];

    // ========================================================================
    // 🚑 ERRORES DE ARCHIVO AU (Urgencias)
    // ========================================================================
    const FILE_AU_ERROR_001 = ['code' => 'FILE_AU_ERROR_001', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_002 = ['code' => 'FILE_AU_ERROR_002', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_003 = ['code' => 'FILE_AU_ERROR_003', 'message' => 'Valor no permitido.'];

    const FILE_AU_ERROR_004 = ['code' => 'FILE_AU_ERROR_004', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_004_F = ['code' => 'FILE_AU_ERROR_004_F', 'message' => 'Formato fecha inválido.'];

    const FILE_AU_ERROR_005 = ['code' => 'FILE_AU_ERROR_005', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_005_F = ['code' => 'FILE_AU_ERROR_005_F', 'message' => 'Formato hora inválido (HH:MM).'];

    const FILE_AU_ERROR_006 = ['code' => 'FILE_AU_ERROR_006', 'message' => 'Valor no permitido.'];

    const FILE_AU_ERROR_007 = ['code' => 'FILE_AU_ERROR_007', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_008 = ['code' => 'FILE_AU_ERROR_008', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_009 = ['code' => 'FILE_AU_ERROR_009', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_009_V = ['code' => 'FILE_AU_ERROR_009_V', 'message' => 'Valor no permitido.'];

    const FILE_AU_ERROR_EST = ['code' => 'FILE_AU_ERROR_EST', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_EST_V = ['code' => 'FILE_AU_ERROR_EST_V', 'message' => 'Valor no permitido (1=Vivo, 2=Muerto).'];

    const FILE_AU_ERROR_MUE = ['code' => 'FILE_AU_ERROR_MUE', 'message' => 'Si estado es Muerto, Causa Muerte es obligatoria.'];

    const FILE_AU_ERROR_010 = ['code' => 'FILE_AU_ERROR_010', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_010_F = ['code' => 'FILE_AU_ERROR_010_F', 'message' => 'Formato fecha inválido.'];

    const FILE_AU_ERROR_DATE_SEQ = ['code' => 'FILE_AU_ERROR_DATE_SEQ', 'message' => 'Fecha Ingreso mayor a Salida.'];

    const FILE_AU_ERROR_011 = ['code' => 'FILE_AU_ERROR_011', 'message' => 'Dato obligatorio.'];

    const FILE_AU_ERROR_011_F = ['code' => 'FILE_AU_ERROR_011_F', 'message' => 'Formato hora inválido.'];

    // ========================================================================
    // 🏥 ERRORES DE ARCHIVO AH (Hospitalización)
    // ========================================================================
    const FILE_AH_ERROR_001 = ['code' => 'FILE_AH_ERROR_001', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AH_ERROR_002 = ['code' => 'FILE_AH_ERROR_002', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AH_ERROR_003 = ['code' => 'FILE_AH_ERROR_003', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AH_ERROR_004 = ['code' => 'FILE_AH_ERROR_004', 'message' => 'El dato ingresado no es permitido.'];

    const FILE_AH_ERROR_005 = ['code' => 'FILE_AH_ERROR_005', 'message' => 'La Fecha de ingreso del usuario a la institución es un dato obligatorio.'];

    const FILE_AH_ERROR_005_F = ['code' => 'FILE_AH_ERROR_005_F', 'message' => 'Formato fecha inválido.'];

    const FILE_AH_ERROR_006 = ['code' => 'FILE_AH_ERROR_006', 'message' => 'La Hora de ingreso del usuario a la Institución es un dato obligatorio.'];

    const FILE_AH_ERROR_007 = ['code' => 'FILE_AH_ERROR_007', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AH_ERROR_009 = ['code' => 'FILE_AH_ERROR_009', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AH_ERROR_009_F = ['code' => 'FILE_AH_ERROR_009_F', 'message' => 'Formato fecha inválido.'];

    const FILE_AH_ERROR_010 = ['code' => 'FILE_AH_ERROR_010', 'message' => 'El dato registrado es obligatorio.'];

    // ========================================================================
    // 👶 ERRORES DE ARCHIVO AN (Recién Nacidos)
    // ========================================================================
    const FILE_AN_ERROR_001 = ['code' => 'FILE_AN_ERROR_001', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AN_ERROR_002 = ['code' => 'FILE_AN_ERROR_002', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AN_ERROR_003 = ['code' => 'FILE_AN_ERROR_003', 'message' => 'El dato registrado no es un valor permitido.'];

    const FILE_AN_ERROR_004 = ['code' => 'FILE_AN_ERROR_004', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AN_ERROR_005 = ['code' => 'FILE_AN_ERROR_005', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AN_ERROR_006 = ['code' => 'FILE_AN_ERROR_006', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AN_ERROR_007 = ['code' => 'FILE_AN_ERROR_007', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AN_ERROR_008 = ['code' => 'FILE_AN_ERROR_008', 'message' => 'El dato registrado no es un valor permitido.'];

    const FILE_AN_ERROR_009 = ['code' => 'FILE_AN_ERROR_009', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AN_ERROR_010 = ['code' => 'FILE_AN_ERROR_010', 'message' => 'El dato registrado no es un valor permitido.'];

    const FILE_AN_ERROR_011 = ['code' => 'FILE_AN_ERROR_011', 'message' => 'El dato registrado es obligatorio.'];

    // ========================================================================
    // 💊 ERRORES DE ARCHIVO AM (Medicamentos)
    // ========================================================================
    const FILE_AM_ERROR_001 = ['code' => 'FILE_AM_ERROR_001', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_002 = ['code' => 'FILE_AM_ERROR_002', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_003 = ['code' => 'FILE_AM_ERROR_003', 'message' => 'El dato ingresado no es permitido.'];

    const FILE_AM_ERROR_004 = ['code' => 'FILE_AM_ERROR_004', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_005 = ['code' => 'FILE_AM_ERROR_005', 'message' => 'El dato ingresado no es permitido.'];

    const FILE_AM_ERROR_006 = ['code' => 'FILE_AM_ERROR_006', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_007 = ['code' => 'FILE_AM_ERROR_007', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_008 = ['code' => 'FILE_AM_ERROR_008', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_009 = ['code' => 'FILE_AM_ERROR_009', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_010 = ['code' => 'FILE_AM_ERROR_010', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AM_ERROR_011 = ['code' => 'FILE_AM_ERROR_011', 'message' => 'El dato registrado es obligatorio.'];

    // ========================================================================
    // 🛠️ ERRORES DE ARCHIVO AT (Otros Servicios)
    // ========================================================================
    const FILE_AT_ERROR_001 = ['code' => 'FILE_AT_ERROR_001', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AT_ERROR_002 = ['code' => 'FILE_AT_ERROR_002', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AT_ERROR_003 = ['code' => 'FILE_AT_ERROR_003', 'message' => 'El dato ingresado no es permitido.'];

    const FILE_AT_ERROR_004 = ['code' => 'FILE_AT_ERROR_004', 'message' => 'El dato registrado es obligatorio.'];

    const FILE_AT_ERROR_005 = ['code' => 'FILE_AT_ERROR_005', 'message' => 'El dato registrado es obligatorio.'];

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
