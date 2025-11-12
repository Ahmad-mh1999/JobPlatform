<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title')->nullable(); // Senior Developer , Mid Graphic Designer ....
            $table->text('bio')->nullable();
            $table->text('summary')->nullable();
            $table->string('location')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('cv_file')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->decimal('expected_salary', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('languages')->nullable(); // ['Arabic' => 'Native', 'English' => 'Fluent']
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};