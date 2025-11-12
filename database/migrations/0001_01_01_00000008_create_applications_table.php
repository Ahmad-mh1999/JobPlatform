<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_profile_id')->constrained()->onDelete('cascade');
            $table->text('cover_letter')->nullable();
            $table->string('cv_file')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'shortlisted', 'interviewed', 'accepted', 'rejected'])->default('pending');
            $table->text('notes')->nullable(); 
            $table->integer('match_score')->nullable(); // AI matching score
            $table->timestamps();
            
            // Prevent duplicate applications
            $table->unique(['job_id', 'employee_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};