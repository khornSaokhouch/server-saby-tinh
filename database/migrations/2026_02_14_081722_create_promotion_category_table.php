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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('discount_percentage');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('promotion_category', function (Blueprint $table) {
            $table->foreignId('promotion_id')
                  ->constrained('promotions')
                  ->onDelete('cascade');
            $table->foreignId('category_id')
                  ->constrained('categories') // make sure your table name matches
                  ->onDelete('cascade');
            $table->primary(['promotion_id', 'category_id']);
            $table->timestamps(); // optional if you want created_at/updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_category');
        Schema::dropIfExists('promotions');
    }
};
