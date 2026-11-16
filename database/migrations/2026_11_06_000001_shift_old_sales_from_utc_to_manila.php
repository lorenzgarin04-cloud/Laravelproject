<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lahat ng benta bago ang timezone fix ay na-save sa UTC (8 oras na mas maaga).
 * Isang beses itong tatakbo at dadagdagan ng 8 oras ang mga lumang oras para maging oras ng Pilipinas.
 * Ang mga bagong benta ay naka-save na sa oras ng Pilipinas, kaya hindi na ito gagalawin ulit.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        foreach (['sales', 'sale_items'] as $table) {
            if ($driver === 'sqlite') {
                DB::statement("UPDATE {$table} SET created_at = datetime(created_at, '+8 hours'), updated_at = datetime(updated_at, '+8 hours') WHERE created_at IS NOT NULL");
            } else {
                DB::statement("UPDATE {$table} SET created_at = DATE_ADD(created_at, INTERVAL 8 HOUR), updated_at = DATE_ADD(updated_at, INTERVAL 8 HOUR) WHERE created_at IS NOT NULL");
            }
        }
    }

    public function down(): void
    {
        // walang rollback
    }
};
