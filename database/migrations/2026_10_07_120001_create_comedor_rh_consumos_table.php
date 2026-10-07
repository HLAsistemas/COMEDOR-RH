<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comedor_rh_consumos', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 20);
            $table->string('empleado_clave', 10);
            $table->string('empleado_nombre', 250);
            $table->date('fecha_consumo');
            $table->foreignId('registrado_por')
                ->constrained('comedor_rh_users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['empresa', 'empleado_clave', 'fecha_consumo'],
                'comedor_rh_consumo_empleado_fecha_unique'
            );
            $table->index(['fecha_consumo', 'empresa'], 'comedor_rh_consumo_fecha_empresa_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comedor_rh_consumos');
    }
};
