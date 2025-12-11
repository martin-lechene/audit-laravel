<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_session_id')->constrained()->onDelete('cascade');
            $table->string('category'); // seo, security, performance, database, code_quality, infrastructure
            $table->string('rule_name');
            $table->string('severity'); // critical, high, medium, low, info
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('affected_items')->nullable();
            $table->text('fix_suggestion')->nullable();
            $table->json('evidence')->nullable();
            $table->decimal('score', 5, 2)->default(0);
            $table->timestamps();

            $table->index('audit_session_id');
            $table->index('category');
            $table->index('severity');
            $table->index('rule_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};

