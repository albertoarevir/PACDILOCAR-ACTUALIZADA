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
        if (!Schema::hasTable('pago_facturas')) {
            Schema::create('pago_facturas', function (Blueprint $table) {
                $table->bigIncrements('id_pago');
                $table->unsignedBigInteger('id_proyecto');
                $table->unsignedBigInteger('id_licitacion')->nullable();
                $table->unsignedBigInteger('id_orden_compra');
                $table->string('numero_oficio', 100);
                $table->date('fecha_oficio');
                $table->unsignedSmallInteger('tipo_documento_id');
                $table->string('rut_proveedor', 15);
                $table->string('razon_social', 255);
                $table->string('numero_factura', 50);
                $table->date('fecha_factura');
                $table->decimal('monto_factura', 15, 0);
                $table->timestamps();

                $table->foreign('id_proyecto')
                    ->references('id')
                    ->on('pacs')
                    ->onDelete('cascade');

                $table->foreign('id_licitacion')
                    ->references('id')
                    ->on('modalidads')
                    ->onDelete('set null');

                $table->foreign('id_orden_compra')
                    ->references('id')
                    ->on('ordens')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_facturas');
    }
};

