<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every step checks first, so a run that died half way can simply be started again.
        if (!Schema::hasColumn('users', 'id_number')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('id_number', 64)->nullable()->after('username'));
        }
        if (!Schema::hasColumn('users', 'sso_source')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('sso_source', 20)->nullable()->after('id_number'));
        }
        if (!$this->indexExists('users', 'users_id_number_agency_index')) {
            Schema::table('users', fn (Blueprint $table) => $table->index(['id_number', 'agency'], 'users_id_number_agency_index'));
        }
        if (!Schema::hasColumn('branches', 'source')) {
            Schema::table('branches', fn (Blueprint $table) => $table->string('source', 20)->nullable()->after('brn_id'));
        }

        // Additive only: copy 13-digit Thai ID usernames into id_number. Nothing else is changed.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE users SET id_number = username WHERE id_number IS NULL AND username REGEXP '^[0-9]{13}$'");
            return;
        }
        DB::table('users')->whereNull('id_number')->orderBy('id')
            ->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    if (preg_match('/^\d{13}$/', (string) $row->username)) {
                        DB::table('users')->where('id', $row->id)->update(['id_number' => $row->username]);
                    }
                }
            });
    }

    private function indexExists(string $table, string $name): bool
    {
        $conn = DB::connection();
        if ($conn->getDriverName() === 'sqlite') {
            return collect($conn->select("PRAGMA index_list('{$table}')"))->contains(fn ($r) => $r->name === $name);
        }
        return count($conn->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])) > 0;
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
