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
        Schema::table('colors', function (Blueprint $table) {
            // Drop the index by its actual name found in the schema
            $table->dropIndex('colors_category_id_foreign');
            $table->dropColumn('category_id');
        });

        Schema::table('sizes', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique(['name', 'category_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colors', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('id');
            $table->index('category_id', 'colors_category_id_foreign');
        });

        Schema::table('sizes', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->unique(['name', 'category_id']);
        });
    }
};
