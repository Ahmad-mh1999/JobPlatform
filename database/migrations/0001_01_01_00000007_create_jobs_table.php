<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('responsibilities')->nullable();
            $table->string('location');
            $table->enum('job_type', ['full-time', 'part-time', 'contract', 'freelance', 'internship'])->default('full-time');
            $table->enum('work_mode', ['onsite', 'remote', 'hybrid'])->default('onsite');
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'lead'])->default('mid');
            $table->decimal('salary_min')->nullable();
            $table->decimal('salary_max')->nullable();
            $table->string('salary_currency', 3)->default('USD');
            $table->enum('salary_period', ['hour', 'day', 'month', 'year'])->default('month');
            $table->integer('vacancies')->default(1);
            $table->date('deadline')->nullable();
            $table->enum('status', ['draft', 'published', 'closed', 'filled'])->default('published');
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Pivot table for job skills
        Schema::create('job_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_skill');
        Schema::dropIfExists('jobs');
    }
};