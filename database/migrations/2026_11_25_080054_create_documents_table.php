<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('doc_code', 40)->unique();
            $table->string('doc_type', 20)->default('SOP'); // SOP, MOP, HLD, REPORT
            $table->string('version', 20)->default('v1.0');
            $table->string('title');
            $table->string('status', 20)->default('draft'); // draft, approved, archived
            $table->string('file_path')->nullable();
            $table->longText('content_text')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};