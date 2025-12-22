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
        Schema::create('filing_old_medical_consultations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('numFactura')->nullable();
            $table->string('Codigo_del_prestador_de_servicios_de_salud')->nullable();
            $table->string('Tipo_de_identificacion_del_usuario')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('Fecha_de_la_consulta')->nullable();
            $table->string('Numero_de_autorizacion')->nullable();
            $table->string('Codigo_de_la_consulta')->nullable();
            $table->string('Finalidad_de_la_consulta')->nullable();
            $table->string('Causa_externa')->nullable();
            $table->string('Codigo_de_diagnostico_principal')->nullable();
            $table->string('Codigo_del_diagnostico_relacionado_No_1')->nullable();
            $table->string('Codigo_del_diagnostico_relacionado_No_2')->nullable();
            $table->string('Codigo_del_diagnostico_relacionado_No_3')->nullable();
            $table->string('Tipo_de_diagnostico_principal')->nullable();
            $table->string('vrServicio')->nullable();
            $table->string('Valor_de_la_cuota_moderadora')->nullable();
            $table->string('Valor_neto_a_pagar')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filing_old_medical_consultations');
    }
};
