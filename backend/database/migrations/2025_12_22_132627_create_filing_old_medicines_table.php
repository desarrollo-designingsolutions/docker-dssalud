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
        Schema::create('filing_old_medicines', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('numFactura')->nullable();
            $table->string('Codigo_del_prestador_de_servicios_de_salud')->nullable();
            $table->string('Tipo_de_identificacion_del_usuario')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('Numero_de_autorizacion')->nullable();
            $table->string('Codigo_del_medicamento')->nullable();
            $table->string('Tipo_de_medicamento')->nullable();
            $table->string('Nombre_generico_del_medicamento')->nullable();
            $table->string('Forma_farmaceutica')->nullable();
            $table->string('Concentracion_del_medicamento')->nullable();
            $table->string('Unidad_de_medida_del_medicamento')->nullable();
            $table->string('Numero_de_unidades')->nullable();
            $table->string('Valor_unitario_de_medicamento')->nullable();
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
        Schema::dropIfExists('filing_old_medicines');
    }
};
