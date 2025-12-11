<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('environment');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('total_findings')->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->json('findings_by_severity')->nullable();
            $table->json('findings_by_category')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('environment');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_sessions');
    }
};

