<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('agent_conversations')->cascadeOnDelete();
            $table->string('role', 20); // user, assistant, tool
            $table->longText('content');
            $table->string('provider', 30)->nullable(); // llama, mistral, system
            $table->string('model_name', 80)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
    }
};