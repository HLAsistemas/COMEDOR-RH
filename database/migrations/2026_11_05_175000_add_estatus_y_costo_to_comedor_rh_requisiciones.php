<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comedor_rh_requisiciones', function (Blueprint $table) {
            $table->string('estatus', 20)->default('pendiente')->after('observaciones');
            $table->string('respuesta_comentario', 500)->nullable()->after('estatus');
            $table->foreignId('revisado_por')->nullable()->after('respuesta_comentario')
                ->constrained('comedor_rh_users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable()->after('revisado_por');
            $table->index('estatus', 'comedor_rh_req_estatus_index');
        });

        Schema::table('comedor_rh_requisicion_items', function (Blueprint $table) {
            $table->decimal('costo', 12, 2)->nullable()->after('comentario');
        });
    }

    public function down(): void
    {
        Schema::table('comedor_rh_requisicion_items', function (Blueprint $table) {
            $table->dropColumn('costo');
        });

        Schema::table('comedor_rh_requisiciones', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropIndex('comedor_rh_req_estatus_index');
            $table->dropColumn(['estatus', 'respuesta_comentario', 'revisado_por', 'revisado_en']);
        });
    }
};
