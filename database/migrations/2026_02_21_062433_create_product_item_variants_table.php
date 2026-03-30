<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_item_id')->constrained('product_items')->onDelete('cascade');
            $table->foreignId('color_id')->nullable()->constrained('colors')->onDelete('cascade');
            $table->foreignId('size_id')->nullable()->constrained('sizes')->onDelete('cascade');
            $table->decimal('price_modifier', 15, 2)->default(0);
            $table->integer('quantity_in_stock');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item_variants');
    }
};
