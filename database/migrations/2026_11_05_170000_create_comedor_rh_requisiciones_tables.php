<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comedor_rh_requisiciones', function (Blueprint $table) {
            $table->id();
            $table->string('solicitante_clave', 20);
            $table->string('solicitante_nombre', 250);
            $table->string('empresa', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['solicitante_clave', 'created_at'], 'comedor_rh_req_solicitante_index');
        });

        Schema::create('comedor_rh_requisicion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')
                ->constrained('comedor_rh_requisiciones')
                ->cascadeOnDelete();
            $table->string('insumo', 200);
            $table->unsignedInteger('personas');
            $table->date('fecha_requerida');
            $table->timestamps();

            $table->index('fecha_requerida', 'comedor_rh_req_items_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comedor_rh_requisicion_items');
        Schema::dropIfExists('comedor_rh_requisiciones');
    }
};
