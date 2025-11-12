<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\Job;
use App\Models\Application;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SuggestionController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get job recommendations for authenticated employee
     */
    public function getRecommendedJobs(Request $request)
    {
        try {
            $user = auth()->user();

            if ($user->role !== 'employee') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للموظفين فقط'
                ], 403);
            }

            $profile = EmployeeProfile::with(['skills', 'experiences', 'education'])
                ->where('user_id', $user->id)
                ->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنشاء البروفايل أولاً'
                ], 404);
            }

            if ($profile->skills->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إضافة مهارات لبروفايلك أولاً للحصول على توصيات'
                ], 400);
            }

            // Check cache first (valid for 1 hour)
            $cacheKey = "job_recommendations_user_{$user->id}";
            
            if ($request->has('refresh') && $request->refresh == true) {
                Cache::forget($cacheKey);
            }

            $recommendations = Cache::remember($cacheKey, 3600, function () use ($profile) {
                // Get active jobs
                $jobs = Job::with(['company', 'skills'])
                    ->active()
                    ->take(20) // Limit to 20 jobs for AI processing
                    ->get();

                if ($jobs->isEmpty()) {
                    return [];
                }

                // Get AI recommendations
                $aiRecommendations = $this->aiService->getJobRecommendations($profile, $jobs);

                // If AI fails, use fallback algorithm
                if (empty($aiRecommendations)) {
                    return $this->getFallbackRecommendations($profile, $jobs);
                }

                // Enrich recommendations with full job data
                return $this->enrichRecommendations($aiRecommendations, $jobs);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'total' => count($recommendations)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب التوصيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fallback recommendations if AI fails
     */
    protected function getFallbackRecommendations($profile, $jobs)
    {
        $employeeSkills = $profile->skills->pluck('name')->toArray();
        $recommendations = [];

        foreach ($jobs as $job) {
            $jobSkills = $job->skills->pluck('name')->toArray();
            $matchScore = $this->aiService->calculateMatchScore($employeeSkills, $jobSkills);

            if ($matchScore > 0) {
                $recommendations[] = [
                    'job_id' => $job->id,
                    'match_score' => $matchScore,
                    'job' => $job,
                    'reasoning' => 'تتطابق ' . $matchScore . '% من المهارات المطلوبة مع مهاراتك',
                    'pros' => $this->getMatchingSkills($employeeSkills, $jobSkills),
                    'cons' => $this->getMissingSkills($employeeSkills, $jobSkills),
                    'advice' => $matchScore >= 70 
                        ? 'تطابق ممتاز! ننصحك بالتقديم فوراً' 
                        : 'يمكنك التقديم وتعلم المهارات الناقصة'
                ];
            }
        }

        // Sort by match score
        usort($recommendations, function($a, $b) {
            return $b['match_score'] - $a['match_score'];
        });

        return array_slice($recommendations, 0, 10);
    }

    /**
     * Enrich AI recommendations with full job data
     */
    protected function enrichRecommendations($aiRecommendations, $jobs)
    {
        $enriched = [];

        foreach ($aiRecommendations as $recommendation) {
            $job = $jobs->firstWhere('id', $recommendation['job_id']);
            
            if ($job) {
                $recommendation['job'] = $job;
                $enriched[] = $recommendation;
            }
        }

        return $enriched;
    }

    /**
     * Get matching skills
     */
    protected function getMatchingSkills($employeeSkills, $jobSkills)
    {
        return array_values(array_intersect(
            array_map('strtolower', $employeeSkills),
            array_map('strtolower', $jobSkills)
        ));
    }

    /**
     * Get missing skills
     */
    protected function getMissingSkills($employeeSkills, $jobSkills)
    {
        return array_values(array_diff(
            array_map('strtolower', $jobSkills),
            array_map('strtolower', $employeeSkills)
        ));
    }

    /**
     * Generate AI cover letter
     */
    public function generateCoverLetter($jobId)
    {

        try {
            $user = auth()->user();
            $profile = EmployeeProfile::with('skills')->where('user_id', $user->id)->first();
            
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنشاء البروفايل أولاً'
                ], 404);
            }

            $job = Job::with('company')->findOrFail($jobId);
            
            $coverLetter = $this->aiService->generateCoverLetter($profile, $job);

            if (!$coverLetter) {
                return response()->json([
                    'success' => $coverLetter,
                    'message' => 'فشل في إنشاء خطاب التقديم'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'cover_letter' => $coverLetter
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء خطاب التقديم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyze CV text and extract information
     */
    public function analyzeCv(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'cv_text' => 'required|string|min:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إدخال نص السيرة الذاتية',
                    'errors' => $validator->errors()
                ], 400);
            }

            $analysis = $this->aiService->analyzeCVText($request->cv_text);

            if (!$analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تحليل السيرة الذاتية'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $analysis
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحليل السيرة الذاتية',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}