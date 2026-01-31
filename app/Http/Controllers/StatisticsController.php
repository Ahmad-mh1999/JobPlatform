<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    // Get platform statistics
    public function getPlatformStatistics()
    {
        try {
            // Count active jobs
            $activeJobsCount = Job::where('is_active', true)->count();
            
            // Count verified companies
            $verifiedCompaniesCount = Company::where('is_verified', true)->count();
            
            // Count job seekers (employees)
            $jobSeekersCount = User::where('role', 'employee')->count();
            
            // Count total companies
            $totalCompaniesCount = Company::count();
            
            // Count total users
            $totalUsersCount = User::count();
            
            // Count applications (if applications table exists)
            $applicationsCount = 0;
            if (DB::getSchemaBuilder()->hasTable('applications')) {
                $applicationsCount = DB::table('applications')->count();
            }
            
            // Get recent jobs count (last 30 days)
            $recentJobsCount = Job::where('created_at', '>=', now()->subDays(30))->count();
            
            // Get new users count (last 30 days)
            $newUsersCount = User::where('created_at', '>=', now()->subDays(30))->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'active_jobs' => $activeJobsCount,
                    'verified_companies' => $verifiedCompaniesCount,
                    'job_seekers' => $jobSeekersCount,
                    'total_companies' => $totalCompaniesCount,
                    'total_users' => $totalUsersCount,
                    'applications' => $applicationsCount,
                    'recent_jobs' => $recentJobsCount,
                    'new_users' => $newUsersCount,
                    'updated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الإحصائيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get detailed statistics for admin dashboard
    public function getDetailedStatistics()
    {
        try {
            // Jobs by category
            $jobsByCategory = Job::select('category', DB::raw('count(*) as count'))
                ->where('is_active', true)
                ->groupBy('category')
                ->get();

            // Users by role
            $usersByRole = User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->get();

            // Companies by size
            $companiesBySize = Company::select('company_size', DB::raw('count(*) as count'))
                ->groupBy('company_size')
                ->get();

            // Monthly registrations (last 12 months)
            $monthlyRegistrations = User::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('count(*) as count')
                )
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Monthly job postings (last 12 months)
            $monthlyJobPostings = Job::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('count(*) as count')
                )
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'jobs_by_category' => $jobsByCategory,
                    'users_by_role' => $usersByRole,
                    'companies_by_size' => $companiesBySize,
                    'monthly_registrations' => $monthlyRegistrations,
                    'monthly_job_postings' => $monthlyJobPostings,
                    'updated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الإحصائيات التفصيلية',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get public statistics (for About page)
    public function getPublicStatistics()
    {
        try {
            // Get basic platform statistics for public display
            $activeJobsCount = Job::where('is_active', true)->count();
            $verifiedCompaniesCount = Company::where('is_verified', true)->count();
            $jobSeekersCount = User::where('role', 'employee')->count();
            
            // Get some impressive numbers for marketing
            $totalApplications = 0;
            if (DB::getSchemaBuilder()->hasTable('applications')) {
                $totalApplications = DB::table('applications')->count();
            }

            // Add some buffer numbers for better presentation
            $displayJobs = max($activeJobsCount, 50);
            $displayCompanies = max($verifiedCompaniesCount, 25);
            $displayJobSeekers = max($jobSeekersCount, 100);

            return response()->json([
                'success' => true,
                'data' => [
                    'active_jobs' => $displayJobs,
                    'verified_companies' => $displayCompanies,
                    'job_seekers' => $displayJobSeekers,
                    'total_applications' => $totalApplications,
                    'real_counts' => [
                        'active_jobs' => $activeJobsCount,
                        'verified_companies' => $verifiedCompaniesCount,
                        'job_seekers' => $jobSeekersCount
                    ],
                    'updated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الإحصائيات العامة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
