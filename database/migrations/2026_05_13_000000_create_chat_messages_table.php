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
        Schema::create('chat_messages', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $blueprint->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $blueprint->text('message');
            $blueprint->boolean('is_read')->default(false);
            $blueprint->timestamps();

            $blueprint->index(['sender_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
