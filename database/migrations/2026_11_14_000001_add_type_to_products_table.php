<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->default('generic')->after('name'); // branded | generic | grocery
            $table->index('type');
        });

        // Backfill ng mga sample na gamot (ang iba ay mananatiling "generic": palitan sa Edit Product kung kailangan).
        DB::table('products')->whereIn('sku', ['VTC-120', 'CGH-060', 'EYE-015', 'ANT-250', 'BND-100'])->update(['type' => 'branded']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
