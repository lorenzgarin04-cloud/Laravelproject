<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 30)->unique();
            $table->string('name');
            $table->string('category', 20)->default('otc'); // otc | rx | both
            $table->string('subtitle')->nullable();          // e.g. Analgesic
            $table->string('unit', 30)->default('tablet');   // per tablet / bottle...
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->date('expiration_date');
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('expiration_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
