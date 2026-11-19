<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('type', 20);                       // sale | adjust | initial
            $table->integer('quantity');                      // may +/- (hal. -3 = nabenta, +10 = dinagdag)
            $table->unsignedInteger('stock_after')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('created_at');
        });

        // Backfill: ang mga lumang benta ay nagbawas din ng stock noon.
        DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->select('sale_items.product_id', 'sale_items.quantity', 'sales.id as sale_id', 'sales.user_id', 'sales.created_at')
            ->orderBy('sale_items.id')
            ->get()
            ->chunk(200)
            ->each(function ($chunk) {
                DB::table('stock_movements')->insert($chunk->map(fn ($r) => [
                    'product_id' => $r->product_id,
                    'user_id'    => $r->user_id,
                    'sale_id'    => $r->sale_id,
                    'type'       => 'sale',
                    'quantity'   => -1 * (int) $r->quantity,
                    'stock_after' => null,
                    'note'       => 'Backfilled from sale',
                    'created_at' => $r->created_at,
                    'updated_at' => $r->created_at,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
