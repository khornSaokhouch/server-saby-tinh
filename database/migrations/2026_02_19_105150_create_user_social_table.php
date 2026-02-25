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
        Schema::create('user_social', function (Blueprint $table) {
            $table->id(); // id integer [pk, increment]
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // links to users table
            $table->string('provider');   // e.g., google, facebook, apple
            $table->string('social_id');  // unique ID from provider
            $table->string('avatar')->nullable(); // optional avatar
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social');
    }
};
