<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('applications', 'job_seeker_id') && Schema::hasColumn('applications', 'recommendation_message')) {
            return;
        }

        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'job_seeker_id')) {
                $table->foreignId('job_seeker_id')
                    ->nullable()
                    ->after('employee_profile_id')
                    ->constrained('users')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('applications', 'recommendation_message')) {
                $table->text('recommendation_message')->nullable()->after('cover_letter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'job_seeker_id')) {
                $table->dropForeign(['job_seeker_id']);
                $table->dropColumn('job_seeker_id');
            }
            if (Schema::hasColumn('applications', 'recommendation_message')) {
                $table->dropColumn('recommendation_message');
            }
        });
    }
};
