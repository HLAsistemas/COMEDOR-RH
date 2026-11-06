<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comedor_rh_requisicion_items', function (Blueprint $table) {
            $table->renameColumn('personas', 'cantidad');
        });

        Schema::table('comedor_rh_requisicion_items', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 2)->change();
            $table->time('hora_requerida')->default('00:00:00')->after('fecha_requerida');
            $table->string('comentario', 500)->nullable()->after('hora_requerida');
        });
    }

    public function down(): void
    {
        Schema::table('comedor_rh_requisicion_items', function (Blueprint $table) {
            $table->dropColumn(['hora_requerida', 'comentario']);
        });

        Schema::table('comedor_rh_requisicion_items', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->change();
            $table->renameColumn('cantidad', 'personas');
        });
    }
};
