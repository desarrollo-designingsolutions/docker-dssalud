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
        Schema::create('filing_old_other_services', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('numFactura')->nullable();
            $table->string('Codigo_del_prestador_de_servicios_de_salud')->nullable();
            $table->string('Tipo_de_identificacion_del_usuario')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('Numero_de_autorizacion')->nullable();
            $table->string('Tipo_de_servicio')->nullable();
            $table->string('Codigo_del_servicio')->nullable();
            $table->string('Nombre_del_servicio')->nullable();
            $table->string('Cantidad')->nullable();
            $table->string('Valor_unitario_del_material_e_insumo')->nullable();
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
        Schema::dropIfExists('filing_old_other_services');
    }
};
