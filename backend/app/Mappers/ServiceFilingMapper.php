<?php

namespace App\Mappers;

use Illuminate\Support\Str;
use App\Enums\Filing\TypeFilingEnum;
use App\Enums\Service\TypeServiceEnum;

class ServiceFilingMapper
{
    /**
     * Retorna:
     *  - model
     *  - payload específico
     *  - payload service
     */
    public static function map(
        TypeFilingEnum $filingType,
        TypeServiceEnum $serviceType,
        array $json,
        array $context
    ): array {

        return match ($filingType) {

            TypeFilingEnum::FILING_TYPE_001 =>
            self::mapOld($serviceType, $json, $context),

            TypeFilingEnum::FILING_TYPE_002 =>
            self::map2275($serviceType, $json, $context),
        };
    }

    /* ===================== OLD ===================== */

    private static function mapOld(
        TypeServiceEnum $serviceType,
        array $json,
        array $ctx
    ): array {

        return match ($serviceType) {

            TypeServiceEnum::SERVICE_TYPE_001 => [
                'model' => \App\Models\FilingOldMedicalConsultation::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'numFactura' => $json['numFactura'] ?? null,
                    'Codigo_del_prestador_de_servicios_de_salud' => $json['Codigo_del_prestador_de_servicios_de_salud'] ?? null,
                    'Tipo_de_identificacion_del_usuario' => $json['Tipo_de_identificacion_del_usuario'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'Fecha_de_la_consulta' => $json['Fecha_de_la_consulta'] ?? null,
                    'Numero_de_autorizacion' => $json['Numero_de_autorizacion'] ?? null,
                    'Codigo_de_la_consulta' => $json['Codigo_de_la_consulta'] ?? null,
                    'Finalidad_de_la_consulta' => $json['Finalidad_de_la_consulta'] ?? null,
                    'Causa_externa' => $json['Causa_externa'] ?? null,
                    'Codigo_de_diagnostico_principal' => $json['Codigo_de_diagnostico_principal'] ?? null,
                    'Codigo_del_diagnostico_relacionado_No_1' => $json['Codigo_del_diagnostico_relacionado_No_1'] ?? null,
                    'Codigo_del_diagnostico_relacionado_No_2' => $json['Codigo_del_diagnostico_relacionado_No_2'] ?? null,
                    'Codigo_del_diagnostico_relacionado_No_3' => $json['Codigo_del_diagnostico_relacionado_No_3'] ?? null,
                    'Tipo_de_diagnostico_principal' => $json['Tipo_de_diagnostico_principal'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,
                    'Valor_de_la_cuota_moderadora' => $json['Valor_de_la_cuota_moderadora'] ?? null,
                    'Valor_neto_a_pagar' => $json['Valor_neto_a_pagar'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_002 => [
                'model' => \App\Models\FilingOldProcedure::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'numFactura' => $json['numFactura'] ?? null,
                    'Codigo_del_prestador_de_servicios_de_salud' => $json['Codigo_del_prestador_de_servicios_de_salud'] ?? null,
                    'Tipo_de_identificacion_del_usuario' => $json['Tipo_de_identificacion_del_usuario'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'Fecha_del_procedimiento' => $json['Fecha_del_procedimiento'] ?? null,
                    'Numero_de_autorizacion' => $json['Numero_de_autorizacion'] ?? null,
                    'Codigo_del_procedimiento' => $json['Codigo_del_procedimiento'] ?? null,
                    'Ambito_de_realizacion_del_procedimiento' => $json['Ambito_de_realizacion_del_procedimiento'] ?? null,
                    'Finalidad_del_procedimiento' => $json['Finalidad_del_procedimiento'] ?? null,
                    'Personal_que_atiende' => $json['Personal_que_atiende'] ?? null,
                    'Diagnostico_principal' => $json['Diagnostico_principal'] ?? null,
                    'Diagnostico_relacionado' => $json['Diagnostico_relacionado'] ?? null,
                    'Complicacion' => $json['Complicacion'] ?? null,
                    'Forma_de_realizacion_del_acto_quirurgico' => $json['Forma_de_realizacion_del_acto_quirurgico'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_003 => [
                'model' => \App\Models\FilingOldUrgency::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'numFactura' => $json['numFactura'] ?? null,
                    'Codigo_del_prestador_de_servicios_de_salud' => $json['Codigo_del_prestador_de_servicios_de_salud'] ?? null,
                    'Tipo_de_identificacion_del_usuario' => $json['Tipo_de_identificacion_del_usuario'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'Fecha_de_ingreso_del_usuario_a_observacion' => $json['Fecha_de_ingreso_del_usuario_a_observacion'] ?? null,
                    'Hora_de_ingreso_del_usuario_a_observacion' => $json['Hora_de_ingreso_del_usuario_a_observacion'] ?? null,
                    'Numero_de_autorizacion' => $json['Numero_de_autorizacion'] ?? null,
                    'Causa_externa' => $json['Causa_externa'] ?? null,
                    'Diagnostico_a_la_salida' => $json['Diagnostico_a_la_salida'] ?? null,
                    'Diagnostico_relacionado_Nro_1_a_la_salida' => $json['Diagnostico_relacionado_Nro_1_a_la_salida'] ?? null,
                    'Diagnostico_relacionado_Nro_2_a_la_salida' => $json['Diagnostico_relacionado_Nro_2_a_la_salida'] ?? null,
                    'Diagnostico_relacionado_Nro_3_a_la_salida' => $json['Diagnostico_relacionado_Nro_3_a_la_salida'] ?? null,
                    'Destino_del_usuario_a_la_salida_de_observacion' => $json['Destino_del_usuario_a_la_salida_de_observacion'] ?? null,
                    'Estado_a_la_salida' => $json['Estado_a_la_salida'] ?? null,
                    'Causa_basica_de_muerte_en_urgencias' => $json['Causa_basica_de_muerte_en_urgencias'] ?? null,
                    'Fecha_de_la_salida_del_usuario_en_observacion' => $json['Fecha_de_la_salida_del_usuario_en_observacion'] ?? null,
                    'Hora_de_la_salida_del_usuario_en_observacion' => $json['Hora_de_la_salida_del_usuario_en_observacion'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_004 => [
                'model' => \App\Models\FilingOldHospitalization::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'numFactura' => $json['numFactura'] ?? null,
                    'Codigo_del_prestador_de_servicios_de_salud' => $json['Codigo_del_prestador_de_servicios_de_salud'] ?? null,
                    'Tipo_de_identificacion_del_usuario' => $json['Tipo_de_identificacion_del_usuario'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'Via_de_ingreso_a_la_institucion' => $json['Via_de_ingreso_a_la_institucion'] ?? null,
                    'Fecha_de_ingreso_del_usuario_a_la_institucion' => $json['Fecha_de_ingreso_del_usuario_a_la_institucion'] ?? null,
                    'Hora_de_ingreso_del_usuario_a_la_Institucion' => $json['Hora_de_ingreso_del_usuario_a_la_Institucion'] ?? null,
                    'Numero_de_autorizacion' => $json['Numero_de_autorizacion'] ?? null,
                    'Causa_externa' => $json['Causa_externa'] ?? null,
                    'Diagnostico_principal_de_ingreso' => $json['Diagnostico_principal_de_ingreso'] ?? null,
                    'Diagnostico_principal_de_egreso' => $json['Diagnostico_principal_de_egreso'] ?? null,
                    'Diagnostico_relacionado_Nro_1_de_egreso' => $json['Diagnostico_relacionado_Nro_1_de_egreso'] ?? null,
                    'Diagnostico_relacionado_Nro_2_de_egreso' => $json['Diagnostico_relacionado_Nro_2_de_egreso'] ?? null,
                    'Diagnostico_relacionado_Nro_3_de_egreso' => $json['Diagnostico_relacionado_Nro_3_de_egreso'] ?? null,
                    'Diagnostico_de_la_complicacion' => $json['Diagnostico_de_la_complicacion'] ?? null,
                    'Estado_a_la_salida' => $json['Estado_a_la_salida'] ?? null,
                    'Diagnostico_de_la_causa_basica_de_muerte' => $json['Diagnostico_de_la_causa_basica_de_muerte'] ?? null,
                    'Fecha_de_egreso_del_usuario_a_la_institucion' => $json['Fecha_de_egreso_del_usuario_a_la_institucion'] ?? null,
                    'Hora_de_egreso_del_usuario_de_la_institucion' => $json['Hora_de_egreso_del_usuario_de_la_institucion'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_005 => [
                'model' => \App\Models\FilingOldNewlyBorn::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'numFactura' => $json['numFactura'] ?? null,
                    'Codigo_del_prestador_de_servicios_de_salud' => $json['Codigo_del_prestador_de_servicios_de_salud'] ?? null,
                    'Tipo_de_identificacion_de_la_madre' => $json['Tipo_de_identificacion_de_la_madre'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'Fecha_de_nacimiento_del_recien_nacido' => $json['Fecha_de_nacimiento_del_recien_nacido'] ?? null,
                    'Hora_de_nacimiento' => $json['Hora_de_nacimiento'] ?? null,
                    'Edad_gestacional' => $json['Edad_gestacional'] ?? null,
                    'Control_prenatal' => $json['Control_prenatal'] ?? null,
                    'Sexo' => $json['Sexo'] ?? null,
                    'Peso' => $json['Peso'] ?? null,
                    'Diagnostico_del_recien_nacido' => $json['Diagnostico_del_recien_nacido'] ?? null,
                    'Causa_basica_de_muerte' => $json['Causa_basica_de_muerte'] ?? null,
                    'Fecha_de_muerte_del_recien_nacido' => $json['Fecha_de_muerte_del_recien_nacido'] ?? null,
                    'Hora_de_muerte_del_recien_nacido' => $json['Hora_de_muerte_del_recien_nacido'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_006 => [
                'model' => \App\Models\FilingOldMedicine::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'numFactura' => $json['numFactura'] ?? null,
                    'Codigo_del_prestador_de_servicios_de_salud' => $json['Codigo_del_prestador_de_servicios_de_salud'] ?? null,
                    'Tipo_de_identificacion_del_usuario' => $json['Tipo_de_identificacion_del_usuario'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'Numero_de_autorizacion' => $json['Numero_de_autorizacion'] ?? null,
                    'Codigo_del_medicamento' => $json['Codigo_del_medicamento'] ?? null,
                    'Tipo_de_medicamento' => $json['Tipo_de_medicamento'] ?? null,
                    'Nombre_generico_del_medicamento' => $json['Nombre_generico_del_medicamento'] ?? null,
                    'Forma_farmaceutica' => $json['Forma_farmaceutica'] ?? null,
                    'Concentracion_del_medicamento' => $json['Concentracion_del_medicamento'] ?? null,
                    'Unidad_de_medida_del_medicamento' => $json['Unidad_de_medida_del_medicamento'] ?? null,
                    'Numero_de_unidades' => $json['Numero_de_unidades'] ?? null,
                    'Valor_unitario_de_medicamento' => $json['Valor_unitario_de_medicamento'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_007 => [
                'model' => \App\Models\FilingOldOtherService::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'numFactura' => $json['numFactura'] ?? null,
                    'Codigo_del_prestador_de_servicios_de_salud' => $json['Codigo_del_prestador_de_servicios_de_salud'] ?? null,
                    'Tipo_de_identificacion_del_usuario' => $json['Tipo_de_identificacion_del_usuario'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'Numero_de_autorizacion' => $json['Numero_de_autorizacion'] ?? null,
                    'Tipo_de_servicio' => $json['Tipo_de_servicio'] ?? null,
                    'Codigo_del_servicio' => $json['Codigo_del_servicio'] ?? null,
                    'Nombre_del_servicio' => $json['Nombre_del_servicio'] ?? null,
                    'Cantidad' => $json['Cantidad'] ?? null,
                    'Valor_unitario_del_material_e_insumo' => $json['Valor_unitario_del_material_e_insumo'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],

            default => throw new \Exception('Servicio OLD no soportado'),
        };
    }

    /* ===================== 2275 ===================== */

    private static function map2275(
        TypeServiceEnum $serviceType,
        array $json,
        array $ctx
    ): array {

        return match ($serviceType) {

            TypeServiceEnum::SERVICE_TYPE_001 => [
                'model' => \App\Models\Filing2275MedicalConsultation::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'codPrestador' => $json['codPrestador'] ?? null,
                    'fechaInicioAtencion' => $json['fechaInicioAtencion'] ?? null,
                    'numAutorizacion' => $json['numAutorizacion'] ?? null,
                    'codConsulta' => $json['codConsulta'] ?? null,
                    'modalidadGrupoServicioTecSal' => $json['modalidadGrupoServicioTecSal'] ?? null,
                    'grupoServicios' => $json['grupoServicios'] ?? null,
                    'codServicio' => $json['codServicio'] ?? null,
                    'finalidadTecnologiaSalud' => $json['finalidadTecnologiaSalud'] ?? null,
                    'causaMotivoAtencion' => $json['causaMotivoAtencion'] ?? null,
                    'codDiagnosticoPrincipal' => $json['codDiagnosticoPrincipal'] ?? null,
                    'codDiagnosticoRelacionado1' => $json['codDiagnosticoRelacionado1'] ?? null,
                    'codDiagnosticoRelacionado2' => $json['codDiagnosticoRelacionado2'] ?? null,
                    'codDiagnosticoRelacionado3' => $json['codDiagnosticoRelacionado3'] ?? null,
                    'tipoDiagnosticoPrincipal' => $json['tipoDiagnosticoPrincipal'] ?? null,
                    'tipoDocumentoIdentificacion' => $json['tipoDocumentoIdentificacion'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,
                    'conceptoRecaudo' => $json['conceptoRecaudo'] ?? null,
                    'valorPagoModerador' => $json['valorPagoModerador'] ?? null,
                    'numFEVPagoModerador' => $json['numFEVPagoModerador'] ?? null,
                    'consecutivo' => $json['consecutivo'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_002 => [
                'model' => \App\Models\Filing2275Procedure::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'codPrestador' => $json['codPrestador'] ?? null,
                    'fechaInicioAtencion' => $json['fechaInicioAtencion'] ?? null,
                    'idMIPRES' => $json['idMIPRES'] ?? null,
                    'numAutorizacion' => $json['numAutorizacion'] ?? null,
                    'codProcedimiento' => $json['codProcedimiento'] ?? null,
                    'viaIngresoServicioSalud' => $json['viaIngresoServicioSalud'] ?? null,
                    'modalidadGrupoServicioTecSal' => $json['modalidadGrupoServicioTecSal'] ?? null,
                    'grupoServicios' => $json['grupoServicios'] ?? null,
                    'codServicio' => $json['codServicio'] ?? null,
                    'finalidadTecnologiaSalud' => $json['finalidadTecnologiaSalud'] ?? null,
                    'tipoDocumentoIdentificacion' => $json['tipoDocumentoIdentificacion'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'codDiagnosticoPrincipal' => $json['codDiagnosticoPrincipal'] ?? null,
                    'codDiagnosticoRelacionado' => $json['codDiagnosticoRelacionado'] ?? null,
                    'codComplicacion' => $json['codComplicacion'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,
                    'conceptoRecaudo' => $json['conceptoRecaudo'] ?? null,
                    'valorPagoModerador' => $json['valorPagoModerador'] ?? null,
                    'numFEVPagoModerador' => $json['numFEVPagoModerador'] ?? null,
                    'consecutivo' => $json['consecutivo'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_003 => [
                'model' => \App\Models\Filing2275Urgency::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'codPrestador' => $json['codPrestador'] ?? null,
                    'fechaInicioAtencion' => $json['fechaInicioAtencion'] ?? null,
                    'causaMotivoAtencion' => $json['causaMotivoAtencion'] ?? null,
                    'codDiagnosticoPrincipal' => $json['codDiagnosticoPrincipal'] ?? null,
                    'codDiagnosticoPrincipalE' => $json['codDiagnosticoPrincipalE'] ?? null,
                    'codDiagnosticoRelacionadoE1' => $json['codDiagnosticoRelacionadoE1'] ?? null,
                    'codDiagnosticoRelacionadoE2' => $json['codDiagnosticoRelacionadoE2'] ?? null,
                    'codDiagnosticoRelacionadoE3' => $json['codDiagnosticoRelacionadoE3'] ?? null,
                    'condicionDestinoUsuarioEgreso' => $json['condicionDestinoUsuarioEgreso'] ?? null,
                    'codDiagnosticoCausaMuerte' => $json['codDiagnosticoCausaMuerte'] ?? null,
                    'fechaEgreso' => $json['fechaEgreso'] ?? null,
                    'consecutivo' => $json['consecutivo'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_004 => [
                'model' => \App\Models\Filing2275Hospitalization::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'codPrestador' => $json['codPrestador'] ?? null,
                    'viaIngresoServicioSalud' => $json['viaIngresoServicioSalud'] ?? null,
                    'fechaInicioAtencion' => $json['fechaInicioAtencion'] ?? null,
                    'numAutorizacion' => $json['numAutorizacion'] ?? null,
                    'causaMotivoAtencion' => $json['causaMotivoAtencion'] ?? null,
                    'codDiagnosticoPrincipal' => $json['codDiagnosticoPrincipal'] ?? null,
                    'codDiagnosticoPrincipalE' => $json['codDiagnosticoPrincipalE'] ?? null,
                    'codDiagnosticoRelacionadoE1' => $json['codDiagnosticoRelacionadoE1'] ?? null,
                    'codDiagnosticoRelacionadoE2' => $json['codDiagnosticoRelacionadoE2'] ?? null,
                    'codDiagnosticoRelacionadoE3' => $json['codDiagnosticoRelacionadoE3'] ?? null,
                    'codComplicacion' => $json['codComplicacion'] ?? null,
                    'condicionDestinoUsuarioEgreso' => $json['condicionDestinoUsuarioEgreso'] ?? null,
                    'codDiagnosticoCausaMuerte' => $json['codDiagnosticoCausaMuerte'] ?? null,
                    'fechaEgreso' => $json['fechaEgreso'] ?? null,
                    'consecutivo' => $json['consecutivo'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_005 => [
                'model' => \App\Models\Filing2275NewlyBorn::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'codPrestador' => $json['codPrestador'] ?? null,
                    'tipoDocumentoIdentificacion' => $json['tipoDocumentoIdentificacion'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'fechaNacimiento' => $json['fechaNacimiento'] ?? null,
                    'edadGestacional' => $json['edadGestacional'] ?? null,
                    'numConsultasCPrenatal' => $json['numConsultasCPrenatal'] ?? null,
                    'codSexoBiologico' => $json['codSexoBiologico'] ?? null,
                    'peso' => $json['peso'] ?? null,
                    'codDiagnosticoPrincipal' => $json['codDiagnosticoPrincipal'] ?? null,
                    'condicionDestinoUsuarioEgreso' => $json['condicionDestinoUsuarioEgreso'] ?? null,
                    'codDiagnosticoCausaMuerte' => $json['codDiagnosticoCausaMuerte'] ?? null,
                    'fechaEgreso' => $json['fechaEgreso'] ?? null,
                    'consecutivo' => $json['consecutivo'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_006 => [
                'model' => \App\Models\Filing2275Medicine::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'codPrestador' => $json['codPrestador'] ?? null,
                    'numAutorizacion' => $json['numAutorizacion'] ?? null,
                    'idMIPRES' => $json['idMIPRES'] ?? null,
                    'fechaDispensAdmon' => $json['fechaDispensAdmon'] ?? null,
                    'codDiagnosticoPrincipal' => $json['codDiagnosticoPrincipal'] ?? null,
                    'codDiagnosticoRelacionado' => $json['codDiagnosticoRelacionado'] ?? null,
                    'tipoMedicamento' => $json['tipoMedicamento'] ?? null,
                    'codTecnologiaSaludable_type' => $json['codTecnologiaSaludable_type'] ?? null,
                    'codTecnologiaSaludable_id' => $json['codTecnologiaSaludable_id'] ?? null,
                    'codTecnologiaSalud' => $json['codTecnologiaSalud'] ?? null,
                    'nomTecnologiaSalud' => $json['nomTecnologiaSalud'] ?? null,
                    'concentracionMedicamento' => $json['concentracionMedicamento'] ?? null,
                    'unidadMedida' => $json['unidadMedida'] ?? null,
                    'formaFarmaceutica' => $json['formaFarmaceutica'] ?? null,
                    'unidadMinDispensa' => $json['unidadMinDispensa'] ?? null,
                    'cantidadMedicamento' => $json['cantidadMedicamento'] ?? null,
                    'diasTratamiento' => $json['diasTratamiento'] ?? null,
                    'tipoDocumentoIdentificacion' => $json['tipoDocumentoIdentificacion'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'vrUnitMedicamento' => $json['vrUnitMedicamento'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,
                    'conceptoRecaudo' => $json['conceptoRecaudo'] ?? null,
                    'valorPagoModerador' => $json['valorPagoModerador'] ?? null,
                    'numFEVPagoModerador' => $json['numFEVPagoModerador'] ?? null,
                    'consecutivo' => $json['consecutivo'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            TypeServiceEnum::SERVICE_TYPE_007 => [
                'model' => \App\Models\Filing2275OtherService::class,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'codPrestador' => $json['codPrestador'] ?? null,
                    'numAutorizacion' => $json['numAutorizacion'] ?? null,
                    'idMIPRES' => $json['idMIPRES'] ?? null,
                    'fechaSuministroTecnologia' => $json['fechaSuministroTecnologia'] ?? null,
                    'tipoOS' => $json['tipoOS'] ?? null,
                    'codTecnologiaSalud' => $json['codTecnologiaSalud'] ?? null,
                    'nomTecnologiaSalud' => $json['nomTecnologiaSalud'] ?? null,
                    'cantidadOS' => $json['cantidadOS'] ?? null,
                    'tipoDocumentoIdentificacion' => $json['tipoDocumentoIdentificacion'] ?? null,
                    'numDocumentoIdentificacion' => $json['numDocumentoIdentificacion'] ?? null,
                    'vrUnitOS' => $json['vrUnitOS'] ?? null,
                    'vrServicio' => $json['vrServicio'] ?? null,
                    'conceptoRecaudo' => $json['conceptoRecaudo'] ?? null,
                    'valorPagoModerador' => $json['valorPagoModerador'] ?? null,
                    'numFEVPagoModerador' => $json['numFEVPagoModerador'] ?? null,
                    'consecutivo' => $json['consecutivo'] ?? null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],

            default => throw new \Exception('Servicio 2275 no soportado'),
        };
    }

    /* ===================== SERVICE ===================== */

    public static function mapService(
        TypeServiceEnum $serviceType,
        string $serviceableId,
        string $serviceableType,
        array $ctx,
        array $json
    ): array {

        return [
            'id' => (string) Str::uuid(),
            'company_id' => $ctx['company_id'],
            'invoice_audit_id' => $ctx['invoice_audit_id'],
            'patient_id' => $ctx['patient_id'],
            'detail_code' => $json['Codigo_del_procedimiento'] ?? null,
            'type' => $serviceType->value,
            'serviceable_type' => $serviceableType,
            'serviceable_id' => $serviceableId,
            'description' => $serviceType->description(),
            'total_value' => $json['vrServicio'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
