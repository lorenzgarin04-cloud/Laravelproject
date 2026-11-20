<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** May mga produktong walang expiration (hal. sabon, napkin, tissue): puwedeng walang petsa. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('products')->whereNull('expiration_date')->update(['expiration_date' => '2099-12-31']);

        Schema::table('products', function (Blueprint $table) {
            $table->date('expiration_date')->nullable(false)->change();
        });
    }
};
