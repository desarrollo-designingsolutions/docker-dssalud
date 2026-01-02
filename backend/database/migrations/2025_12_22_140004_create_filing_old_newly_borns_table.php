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
        Schema::create('filing_old_newly_borns', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('numFactura')->nullable();
            $table->string('Codigo_del_prestador_de_servicios_de_salud')->nullable();
            $table->string('Tipo_de_identificacion_de_la_madre')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('Fecha_de_nacimiento_del_recien_nacido')->nullable();
            $table->string('Hora_de_nacimiento')->nullable();
            $table->string('Edad_gestacional')->nullable();
            $table->string('Control_prenatal')->nullable();
            $table->string('Sexo')->nullable();
            $table->string('Peso')->nullable();
            $table->string('Diagnostico_del_recien_nacido')->nullable();
            $table->string('Causa_basica_de_muerte')->nullable();
            $table->string('Fecha_de_muerte_del_recien_nacido')->nullable();
            $table->string('Hora_de_muerte_del_recien_nacido')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filing_old_newly_borns');
    }
};
