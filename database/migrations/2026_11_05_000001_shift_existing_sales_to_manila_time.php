<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ang mga lumang benta ay na-save sa UTC (8 oras na mas maaga kaysa Pilipinas).
 * Isang beses lang itong tatakbo: dinadagdagan ng 8 oras ang mga lumang oras,
 * pero KUNG naka-set na ang APP_TIMEZONE=Asia/Manila sa .env.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (config('app.timezone') !== 'Asia/Manila') {
            return; // hindi pa naka-set ang timezone: walang gagalawin
        }

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
