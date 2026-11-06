<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comedor_rh_requisiciones', function (Blueprint $table) {
            $table->foreignId('capturada_por')->nullable()->after('empresa')
                ->constrained('comedor_rh_users')->nullOnDelete();
            $table->index('created_at', 'comedor_rh_req_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('comedor_rh_requisiciones', function (Blueprint $table) {
            $table->dropForeign(['capturada_por']);
            $table->dropIndex('comedor_rh_req_created_index');
            $table->dropColumn('capturada_por');
        });
    }
};
