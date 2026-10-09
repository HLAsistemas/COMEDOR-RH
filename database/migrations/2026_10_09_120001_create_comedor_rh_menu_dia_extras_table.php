<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comedor_rh_menu_dia_extras', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre', 150);
            $table->text('descripcion');
            $table->foreignId('registrado_por')
                ->constrained('comedor_rh_users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'id'], 'comedor_rh_menu_dia_extras_fecha_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comedor_rh_menu_dia_extras');
    }
};
