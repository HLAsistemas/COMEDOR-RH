<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comedor_rh_menu_dia_extras_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion');
            $table->boolean('is_active')->default(true);
            $table->foreignId('registrado_por')
                ->constrained('comedor_rh_users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'nombre'], 'comedor_rh_extras_fijos_active_nombre_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comedor_rh_menu_dia_extras_fijos');
    }
};
