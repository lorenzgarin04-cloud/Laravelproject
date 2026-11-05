<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tinatanggal ang sample na address/phone/TIN sa resibo (kung hindi pa napapalitan sa Settings).
 * Ang mga napalitan na ng totoong data ay hindi gagalawin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('settings')->where('key', 'store_address')->where('value', '124 Main Health Ave, Brgy. Central, Quezon City')
            ->update(['value' => 'Urdaneta City, Pangasinan', 'updated_at' => $now]);
        DB::table('settings')->where('key', 'store_phone')->where('value', '(02) 8123-4567')
            ->update(['value' => '', 'updated_at' => $now]);
        DB::table('settings')->where('key', 'store_tin')->where('value', '412-889-102-000')
            ->update(['value' => '', 'updated_at' => $now]);

        Cache::forget('settings.all');
    }

    public function down(): void
    {
        // walang rollback: ang sample data ay hindi na kailangan
    }
};
