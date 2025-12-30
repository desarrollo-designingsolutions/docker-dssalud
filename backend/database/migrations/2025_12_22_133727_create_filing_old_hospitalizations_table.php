<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filing_old_hospitalizations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('numFactura')->nullable();
            $table->string('Codigo_del_prestador_de_servicios_de_salud')->nullable();
            $table->string('Tipo_de_identificacion_del_usuario')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('Via_de_ingreso_a_la_institucion')->nullable();
            $table->string('Fecha_de_ingreso_del_usuario_a_la_institucion')->nullable();
            $table->string('Hora_de_ingreso_del_usuario_a_la_Institucion')->nullable();
            $table->string('Numero_de_autorizacion')->nullable();
            $table->string('Causa_externa')->nullable();
            $table->string('Diagnostico_principal_de_ingreso')->nullable();
            $table->string('Diagnostico_principal_de_egreso')->nullable();
            $table->string('Diagnostico_relacionado_Nro_1_de_egreso')->nullable();
            $table->string('Diagnostico_relacionado_Nro_2_de_egreso')->nullable();
            $table->string('Diagnostico_relacionado_Nro_3_de_egreso')->nullable();
            $table->string('Diagnostico_de_la_complicacion')->nullable();
            $table->string('Estado_a_la_salida')->nullable();
            $table->string('Diagnostico_de_la_causa_basica_de_muerte')->nullable();
            $table->string('Fecha_de_egreso_del_usuario_a_la_institucion')->nullable();
            $table->string('Hora_de_egreso_del_usuario_de_la_institucion')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filing_old_hospitalizations');
    }
};
