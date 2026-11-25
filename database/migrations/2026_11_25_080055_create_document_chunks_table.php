<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->string('section_title')->nullable();
            $table->longText('chunk_text');
            $table->timestamps();

            $table->fullText('chunk_text');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};