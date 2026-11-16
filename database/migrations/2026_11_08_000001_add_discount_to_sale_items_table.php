<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('line_total');
        });

        // Backfill: hatiin ang discount ng mga lumang Senior/PWD na benta sa kanilang mga linya.
        DB::table('sales')->where('discount', '>', 0)->orderBy('id')->each(function ($sale) {
            $items = DB::table('sale_items')->where('sale_id', $sale->id)->orderBy('id')->get();
            if ($items->isEmpty() || (float) $sale->subtotal <= 0) {
                return;
            }

            $allocated = 0.0;
            $last = $items->count() - 1;

            foreach ($items as $i => $item) {
                $d = $i === $last
                    ? round((float) $sale->discount - $allocated, 2)
                    : round((float) $item->line_total / (float) $sale->subtotal * (float) $sale->discount, 2);
                $allocated += $d;

                DB::table('sale_items')->where('id', $item->id)->update(['discount' => $d]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
