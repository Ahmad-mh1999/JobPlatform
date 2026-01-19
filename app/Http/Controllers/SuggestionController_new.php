<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SuggestionController extends Controller
{
    private $apiEndpoint;
    private $apiKey;
    private $model = 'deepseek/deepseek-chat-v3.1:free';

    public function __construct()
    {
        $this->apiEndpoint = 'https://openrouter.ai/api/v1/chat/completions';
        $this->apiKey = config('services.openrouter.key');
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

                // Get AI recommendations (returns only job IDs)
                $aiRecommendations = $this->getAIJobRecommendations($profile, $jobs);

                // If AI fails, use fallback algorithm
                if (empty($aiRecommendations)) {
                    return $this->getFallbackRecommendations($profile, $jobs);
                }

                // Process AI recommendations and return full job data in order
                return $this->processAIRecommendations($aiRecommendations, $jobs);
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
     * Get AI job recommendations using same logic as AiController
     */
    private function getAIJobRecommendations($profile, $jobs)
    {
        try {
            // Format profile data
            $formattedProfile = $this->formatProfileData($profile->toArray());
            $formattedJobs = $this->formatAvailableJobs($jobs->toArray());

            $systemPrompt = "أنت مساعد متخصص في التوظيف. مهمتك هي تحليل ملف المرشح (السيرة الذاتية والخبرات والمهارات) ومقارنته بقائمة الوظائف المتاحة. يجب عليك اختيار **أفضل 5 وظائف** من القائمة المقدمة والتي تتطابق مع مؤهلات المرشح. قم بترتيب الوظائف الخمس المختارة من الأكثر ملاءمة إلى الأقل ملاءمة. يجب أن يكون الرد النهائي بصيغة JSON فقط يحتوي على مصفوفة باسم 'recommended_job_ids' تحتوي على أرقام تعريف الوظائف بالترتيب.\n\n"
                          . "مثال على الرد:\n"
                          . "```json\n"
                          . "{\n"
                          . "  \"recommended_job_ids\": [1, 2, 3, 4, 5]\n"
                          . "}\n"
                          . "```\n"
                          . "لا تقم بإضافة أي نصوص إضافية قبل أو بعد كود JSON.";

            $userPrompt = "إليك البيانات:\n" . $formattedProfile . "\n" . $formattedJobs;

            // Call AI service
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI Job Search Service'
            ])->timeout(120)
              ->withOptions(['connect_timeout' => 120])
              ->post($this->apiEndpoint, [ 
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            if ($response->successful()) {
                $content = json_decode($response->body(), true)['choices'][0]['message']['content'];
                $data = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['recommended_job_ids'])) {
                    return $data['recommended_job_ids'];
                }
            }

            Log::error('AI Service Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format profile data for AI (from AiController)
     */
    private function formatProfileData(array $profileData): string
    {
        $formattedData = "--- بيانات الموظف ---\n";

        // معلومات المستخدم والبروفايل الأساسية
        $formattedData .= "- اسم المرشح: " . ($profileData['user']['name'] ?? 'غير محدد') . "\n";
        $formattedData .= "- البريد الإلكتروني: " . ($profileData['user']['email'] ?? 'غير محدد') . "\n";
        $formattedData .= "- المسمى الوظيفي الحالي: " . ($profileData['title'] ?? 'غير محدد') . "\n";
        $formattedData .= "- ملخص البروفايل: " . ($profileData['summary'] ?? $profileData['bio'] ?? 'لا يوجد ملخص') . "\n";
        $formattedData .= "- سنوات الخبرة: " . ($profileData['years_of_experience'] ?? 'غير محدد') . "\n";
        $formattedData .= "- الموقع: " . ($profileData['location'] ?? 'غير محدد') . "\n";
        $formattedData .= "- اللغات (تفصيل): " . ($profileData['languages'] ?? 'غير محدد') . "\n";
        $formattedData .= "\n";

        // الخبرات العملية
        $formattedData .= "--- الخبرات العملية ---\n";
        if (!empty($profileData['experiences'])) {
            foreach ($profileData['experiences'] as $exp) {
                $formattedData .= "  * المسمى الوظيفي: " . ($exp['job_title'] ?? 'غير محدد') . "\n";
                $formattedData .= "  * الشركة: " . ($exp['company_name'] ?? 'غير محدد') . "\n";
                $formattedData .= "  * الوصف والمسؤوليات: " . ($exp['description'] ?? 'لا يوجد وصف') . "\n";
                $formattedData .= "  * الفترة: من " . ($exp['start_date'] ?? 'غير محدد') . " إلى " . ($exp['end_date'] ?? 'الآن') . "\n";
                $formattedData .= "--- \n";
            }
        } else {
            $formattedData .= "لا توجد خبرات عملية مسجلة.\n";
        }
        $formattedData .= "\n";

        // المهارات
        $formattedData .= "--- المهارات ---\n";
        if (!empty($profileData['skills'])) {
            foreach ($profileData['skills'] as $skill) {
                $level = $skill['pivot']['level'] ?? 'غير محدد';
                $formattedData .= "  * " . ($skill['name'] ?? 'غير محدد') . " (المستوى: {$level})\n";
            }
        } else {
            $formattedData .= "لا توجد مهارات مسجلة.\n";
        }
        $formattedData .= "\n";

        // التعليم
        $formattedData .= "--- التعليم والمؤهلات ---\n";
        if (!empty($profileData['education'])) {
            foreach ($profileData['education'] as $edu) {
                $formattedData .= "  * الدرجة: " . ($edu['degree'] ?? 'غير محدد') . "\n";
                $formattedData .= "  * التخصص: " . ($edu['field_of_study'] ?? 'غير محدد') . "\n";
                $formattedData .= "  * المؤسسة: " . ($edu['institution'] ?? 'غير محدد') . "\n";
                $formattedData .= "  * الفترة: " . ($edu['start_date'] ?? 'غير محدد') . " - " . ($edu['end_date'] ?? 'غير محدد') . "\n";
                $formattedData .= "--- \n";
            }
        } else {
            $formattedData .= "لا توجد مؤهلات تعليمية مسجلة.\n";
        }
        $formattedData .= "\n";

        return $formattedData;
    }

    /**
     * Format available jobs for AI (from AiController)
     */
    private function formatAvailableJobs(array $jobsData): string
    {
        $formattedData = "--- قائمة الوظائف المتاحة ---\n";
        if (empty($jobsData)) {
            return $formattedData . "لا توجد وظائف متاحة حالياً في قاعدة البيانات.\n";
        }

        foreach ($jobsData as $job) {
            $formattedData .= "## الوظيفة رقم " . ($job['id'] ?? 'غير محدد') . "\n";
            $formattedData .= "* المسمى الوظيفي: " . ($job['title'] ?? 'غير محدد') . "\n";
            $formattedData .= "* الوصف: " . ($job['description'] ?? 'لا يوجد وصف') . "\n";
            $formattedData .= "* المتطلبات: " . ($job['requirements'] ?? 'لا توجد متطلبات محددة') . "\n";
            $formattedData .= "* الموقع: " . ($job['location'] ?? 'غير محدد') . "\n";
            $formattedData .= "----------------------\n";
        }
        return $formattedData;
    }

    /**
     * Process AI recommendations (job IDs) and return full job data in order
     */
    protected function processAIRecommendations($aiRecommendations, $jobs)
    {
        $recommendations = [];
        
        foreach ($aiRecommendations as $index => $jobId) {
            $job = $jobs->firstWhere('id', $jobId);
            
            if ($job) {
                $recommendations[] = [
                    'job_id' => $job->id,
                    'match_score' => 100 - ($index * 10), // Decreasing score based on AI ranking
                    'job' => $job,
                    'reasoning' => 'موصى به من قبل الذكاء الاصطناعي بناءً على تحليل شامل لملفك الشخصي ومتطلبات الوظيفة',
                    'pros' => ['تطابق عالي مع مهاراتك', 'موصى به من قبل الذكاء الاصطناعي'],
                    'cons' => [],
                    'advice' => 'هذه الوظيفة هي الأنسب لك بناءً على تحليل الذكاء الاصطناعي'
                ];
            }
        }
        
        return array_slice($recommendations, 0, 5); // Return only top 5
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
            $matchScore = $this->calculateMatchScore($employeeSkills, $jobSkills);

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
     * Match score calculation (fallback if AI fails)
     */
    protected function calculateMatchScore($employeeSkills, $jobSkills)
    {
        if (empty($employeeSkills) || empty($jobSkills)) {
            return 0;
        }

        $employeeSkillNames = array_map('strtolower', $employeeSkills);
        $jobSkillNames = array_map('strtolower', $jobSkills);

        $matchingSkills = array_intersect($employeeSkillNames, $jobSkillNames);
        $matchPercentage = (count($matchingSkills) / count($jobSkillNames)) * 100;

        return round($matchPercentage);
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
            
            $coverLetter = $this->generateAICoverLetter($profile, $job);

            if (!$coverLetter) {
                return response()->json([
                    'success' => false,
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
     * Generate AI cover letter using same logic as AiController
     */
    private function generateAICoverLetter($profile, $job)
    {
        try {
            $formattedProfile = $this->formatProfileData($profile->toArray());
            $formattedJob = $this->formatJobData($job->toArray());

            $systemPrompt = "أنت كاتب محترف لرسائل التغطية (Cover Letters). مهمتك هي كتابة رسالة تغطية قوية ومقنعة باللغة العربية. يجب أن تكون الرسالة موجهة نحو الوظيفة المحددة وتبرز بشكل خاص الخبرات والمهارات الأكثر صلة الموجودة في ملف المرشح. يجب أن تكون الرسالة موجزة واحترافية (بحد أقصى 350 كلمة). الرد يجب أن يكون رسالة التغطية فقط، بدون أي مقدمات أو خاتمات إضافية.";
            
            $userPrompt = "إليك بيانات المرشح وبيانات الوظيفة. قم بكتابة رسالة التغطية بناءً عليها.\n\n"
                          . $formattedProfile . "\n"
                          . $formattedJob;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI Cover Letter Generator'
            ])->timeout(120)
              ->withOptions(['connect_timeout' => 120])
              ->post($this->apiEndpoint, [ 
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
            ]);

            if ($response->successful()) {
                return json_decode($response->body(), true)['choices'][0]['message']['content'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('AI Cover Letter Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format job data for AI (from AiController)
     */
    private function formatJobData(array $jobData): string
    {
        $formattedData = "--- تفاصيل الوظيفة المقدم لها ---\n";
        $formattedData .= "- المسمى الوظيفي: " . ($jobData['title'] ?? 'غير محدد') . "\n";
        $formattedData .= "- وصف الوظيفة: " . ($jobData['description'] ?? 'لا يوجد وصف') . "\n";
        $formattedData .= "- متطلبات الوظيفة: " . ($jobData['requirements'] ?? 'لا توجد متطلبات') . "\n";
        $formattedData .= "- الشركة: " . ($jobData['company_name'] ?? 'غير محدد') . "\n";
        $formattedData .= "- الموقع: " . ($jobData['location'] ?? 'غير محدد') . "\n";
        return $formattedData;
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

            $analysis = $this->analyzeCVText($request->cv_text);

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

    /**
     * Analyze CV and extract skills using AI
     */
    private function analyzeCVText($cvText)
    {
        try {
            $prompt = <<<PROMPT
أنت خبير في تحليل السير الذاتية. قم بتحليل السيرة الذاتية التالية واستخرج المعلومات المهمة.

نص السيرة الذاتية:
{$cvText}

المطلوب منك:
1. استخراج جميع المهارات التقنية والشخصية
2. تحديد سنوات الخبرة
3. استخراج المسمى الوظيفي الحالي أو المطلوب
4. تلخيص الخبرات العملية

أرجع النتيجة بصيغة JSON التالية فقط:
{
  "skills": ["skill1", "skill2", "..."],
  "years_of_experience": 5,
  "title": "Job Title",
  "summary": "ملخص مختصر عن المرشح"
}
PROMPT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI CV Analyzer'
            ])->timeout(120)
              ->withOptions(['connect_timeout' => 120])
              ->post($this->apiEndpoint, [ 
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'أنت خبير في تحليل السير الذاتية. قم بتحليل السيرة الذاتية واستخراج المعلومات المهمة بصيغة JSON فقط.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            if ($response->successful()) {
                $content = json_decode($response->body(), true)['choices'][0]['message']['content'];
                $data = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('AI CV Analysis Error: ' . $e->getMessage());
            return null;
        }
    }
}
