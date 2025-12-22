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
        Schema::create('filing_old_procedures', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('numFactura')->nullable();
            $table->string('Codigo_del_prestador_de_servicios_de_salud')->nullable();
            $table->string('Tipo_de_identificacion_del_usuario')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('Fecha_del_procedimiento')->nullable();
            $table->string('Numero_de_autorizacion')->nullable();
            $table->string('Codigo_del_procedimiento')->nullable();
            $table->string('Ambito_de_realizacion_del_procedimiento')->nullable();
            $table->string('Finalidad_del_procedimiento')->nullable();
            $table->string('Personal_que_atiende')->nullable();
            $table->string('Diagnostico_principal')->nullable();
            $table->string('Diagnostico_relacionado')->nullable();
            $table->string('Complicacion')->nullable();
            $table->string('Forma_de_realizacion_del_acto_quirurgico')->nullable();
            $table->string('vrServicio')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filing_old_procedures');
    }
};
