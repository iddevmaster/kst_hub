<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('id_number', 32)->nullable()->after('username');
            $table->string('sso_source', 20)->nullable()->after('id_number');
            $table->index(['id_number', 'agency'], 'users_id_number_agency_index');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->after('brn_id');
        });

        // Additive only: copy 13-digit Thai ID usernames into id_number. Nothing else is changed.
        DB::table('users')->whereNull('id_number')->orderBy('id')
            ->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    if (preg_match('/^\d{13}$/', (string) $row->username)) {
                        DB::table('users')->where('id', $row->id)->update(['id_number' => $row->username]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_id_number_agency_index');
            $table->dropColumn(['id_number', 'sso_source']);
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
