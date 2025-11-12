<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->json('images')->nullable(); 
            $table->string('video')->nullable();
            $table->enum('type', ['text', 'job_post', 'article', 'achievement'])->default('text');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->enum('visibility', ['public', 'connections', 'private'])->default('public');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};