<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comedor_rh_users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('username')->nullable();
            $table->boolean('is_admin')->default(false);
        });

        DB::table('comedor_rh_users')
            ->select(['id', 'email'])
            ->orderBy('id')
            ->each(function (object $user): void {
                $baseUsername = Str::lower((string) Str::before($user->email, '@'));
                $username = trim((string) preg_replace('/[^a-z0-9._-]+/', '-', $baseUsername), '._-');
                $username = Str::limit($username !== '' ? $username : 'usuario', 50, '');

                if ($username === '') {
                    $username = 'usuario';
                }

                $candidate = $username;
                $suffix = 1;

                while (DB::table('comedor_rh_users')->where('username', $candidate)->exists()) {
                    $tail = '-'.$suffix++;
                    $candidate = Str::limit($username, 50 - strlen($tail), '').$tail;
                }

                DB::table('comedor_rh_users')
                    ->where('id', $user->id)
                    ->update(['username' => $candidate]);
            });

        Schema::table('comedor_rh_users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('comedor_rh_users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'is_admin']);
        });
    }
};
