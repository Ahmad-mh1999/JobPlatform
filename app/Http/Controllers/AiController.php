<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\EmployeeProfile;
use App\Models\Job; 

class AiController extends Controller
{
    private $apiEndpoint;
    private $apiKey;
    private $model = 'deepseek/deepseek-chat-v3.1:free';

    public function __construct()
    {
        $this->apiEndpoint = 'https://openrouter.ai/api/v1/chat/completions';
        // جلب المفتاح من ملف config/services.php
        $this->apiKey = config('services.openrouter.key');

      }

    /**
     * دالة مساعدة لجلب البروفايل الخاص بالمستخدم المسجل دخوله والتحقق من الدور.
     * @return array|\Illuminate\Http\JsonResponse
     */
    private function getAuthenticatedProfile()
    {
        // 1. التحقق من المصادقة
        $user = auth()->user();


        // 2. التحقق من الدور
        if ($user->role !== 'employee') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الخدمة متاحة للباحثين عن عمل فقط.'
            ], 403);
        }

        // 3. جلب البروفايل والعلاقات
        $profile = EmployeeProfile::with(['user', 'skills', 'education', 'experiences'])
            ->where('user_id', $user->id)
            ->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد ملف شخصي للموظف لهذا المستخدم.'
            ], 404);
        }

        return $profile->toArray();
    }

    /**
     * دالة مساعدة لتنسيق كامل بيانات البروفايل للنموذج.
     * @param array $profileData بيانات البروفايل كاملة (مع skills, experiences, education)
     * @return string
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
                $formattedData .= "  * الوصف والمسؤوليات (يجب إرجاعه بالتفصيل): " . ($exp['description'] ?? 'لا يوجد وصف') . "\n";
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
     * دالة مساعدة لتنسيق قائمة الوظائف المتاحة للنموذج.
     * @param array $jobsData قائمة بالوظائف المتاحة
     * @return string
     */
    private function formatAvailableJobs(array $jobsData): string
    {
        $formattedData = "--- قائمة الوظائف المتاحة ---\n";
        if (empty($jobsData)) {
            return $formattedData . "لا توجد وظائف متاحة حالياً في قاعدة البيانات.\n";
        }

        foreach ($jobsData as $job) {
            $companyName = isset($job['company']['company_name']) ? $job['company']['company_name'] : 'غير محدد';
            $skills = [];
            if (!empty($job['skills'])) {
                foreach ($job['skills'] as $skill) {
                    $skills[] = $skill['name'] ?? '';
                }
            }
            $skillsText = empty($skills) ? 'لا توجد مهارات محددة' : implode(', ', $skills);

            $formattedData .= "## Job ID: " . ($job['id'] ?? 'غير محدد') . "\n";
            $formattedData .= "* المسمى الوظيفي: " . ($job['title'] ?? 'غير محدد') . "\n";
            $formattedData .= "* الشركة: " . $companyName . "\n";
            $formattedData .= "* الموقع: " . ($job['location'] ?? 'غير محدد') . "\n";
            $formattedData .= "* نوع العمل: " . ($job['job_type'] ?? 'غير محدد') . "\n";
            $formattedData .= "* مستوى الخبرة: " . ($job['experience_level'] ?? 'غير محدد') . "\n";
            $formattedData .= "* المهارات المطلوبة: " . $skillsText . "\n";
            $formattedData .= "* الوصف: " . (isset($job['description']) ? mb_substr($job['description'], 0, 300) . '...' : 'لا يوجد وصف') . "\n";
            $formattedData .= "* المتطلبات: " . ($job['requirements'] ?? 'لا توجد متطلبات محددة') . "\n";
            $formattedData .= "----------------------\n";
        }
        return $formattedData;
    }

    /**
     * دالة مساعدة لتنسيق بيانات وظيفة واحدة للنموذج.
     * @param array $jobData بيانات الوظيفة
     * @return string
     */
    private function formatJobData(array $jobData): string
    {
        $formattedData = "--- تفاصيل الوظيفة المقدم لها ---\n";
        $formattedData .= "- المسمى الوظيفي: " . ($jobData['title'] ?? 'غير محدد') . "\n";
        $formattedData .= "- وصف الوظيفة: " . ($jobData['description'] ?? 'لا يوجد وصف') . "\n";
        $formattedData .= "- متطلبات الوظيفة: " . ($jobData['requirements'] ?? 'لا توجد متطلبات') . "\n";
        $companyName = isset($jobData['company']['company_name']) ? $jobData['company']['company_name'] : ($jobData['company_name'] ?? 'غير محدد');
        $formattedData .= "- الشركة: " . $companyName . "\n";
        $formattedData .= "- الموقع: " . ($jobData['location'] ?? 'غير محدد') . "\n";
        return $formattedData;
    }

    /**
     * دالة مساعدة لإنشاء الهيكل المطلوب لـ Roadmap JSON.
     * يستخدم لتضمين الهيكل في الـ System Prompt.
     * @return array
     */
    private function getRoadmapSchemaPlaceholder(): array
    {
        return [
            'title' => 'عنوان خارطة الطريق المُقترحة',
            'description' => 'ملخص قصير ومحفز حول هذه المهنة أو المجال.',
            'estimated_duration_months' => 6,
            'modules' => [
                [
                    'module_number' => 1,
                    'module_title' => 'المرحلة الأولى: الأساسيات',
                    'estimated_weeks' => 4,
                    'topics' => [
                        [
                            'topic_name' => 'اسم الموضوع',
                            'key_concepts' => ['المفهوم الأول', 'المفهوم الثاني'],
                            'suggested_resources' => ['رابط أو اسم كتاب أو دورة']
                        ]
                    ]
                ]
            ]
        ];
    }


    /**
     * استدعاء خدمة الذكاء الاصطناعي لاقتراح الوظائف من القائمة المتاحة.
     */
    public function suggestJobs(Request $request)
    {
        // 1. جلب البروفايل المصادق عليه
        $profileData = $this->getAuthenticatedProfile();
        if ($profileData instanceof \Illuminate\Http\JsonResponse) {
            return $profileData; // إرجاع خطأ المصادقة أو الدور
        }

        try {
            $jobsCollection = Job::with(['company:id,company_name', 'skills:id,name'])
                ->select('id', 'company_id', 'title', 'description', 'requirements', 'location', 'job_type', 'experience_level')
                ->take(50)
                ->get();
            if ($jobsCollection->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'لم يتم العثور على أي وظائف متاحة لاقتراحها.'], 404);
            }
            $jobs = $jobsCollection->toArray();
        } catch (\Throwable $e) {
            Log::error("Failed to fetch jobs: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء جلب قائمة الوظائف.'], 500);
        }

        $formattedProfile = $this->formatProfileData($profileData);
        $formattedJobs = $this->formatAvailableJobs($jobs);

        $systemPrompt = "أنت مساعد توظيف. حلّل بيانات المرشح والوظائف المتاحة، ثم أعِد ترتيب معرفات الوظائف من الأكثر ملاءمة إلى الأقل. يجب أن يكون الإخراج بصيغة JSON فقط بهذا الشكل:\n\n"
                      . "{\n"
                      . "  \"recommended_job_ids\": [1, 2, 3, 4, 5]\n"
                      . "}\n\n"
                      . "أعد فقط هذا JSON بدون أي نص إضافي.";

        $userPrompt = "إليك البيانات:\n" . $formattedProfile . "\n" . $formattedJobs;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI Job Search Service'
            ])->timeout(120) // مهلة الطلب الكلية: 120 ثانية
              ->withOptions(['connect_timeout' => 120]) // مهلة الاتصال الأولي: 120 ثانية
              ->post($this->apiEndpoint, [ 
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $response->throw(); // رمي استثناء إذا كان الكود غير 2xx

            $raw = json_decode($response->body(), true);
            $content = $raw['choices'][0]['message']['content'] ?? '{}';
            $parsed = is_string($content) ? json_decode($content, true) : (is_array($content) ? $content : []);
            $ids = [];
            if (is_array($parsed) && isset($parsed['recommended_job_ids']) && is_array($parsed['recommended_job_ids'])) {
                $ids = array_map('intval', $parsed['recommended_job_ids']);
            }

            $existingIds = array_column($jobs, 'id');
            $ids = array_values(array_filter($ids, function ($id) use ($existingIds) { return in_array($id, $existingIds, true); }));
            if (empty($ids)) {
                $ids = $existingIds;
            }

            $map = [];
            foreach ($jobs as $j) {
                $map[$j['id']] = $j;
            }
            $orderedJobs = [];
            foreach ($ids as $id) {
                if (isset($map[$id])) {
                    $orderedJobs[] = $map[$id];
                }
            }
            if (count($orderedJobs) < count($jobs)) {
                foreach ($jobs as $j) {
                    if (!in_array($j['id'], $ids, true)) {
                        $orderedJobs[] = $j;
                        $ids[] = $j['id'];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $orderedJobs,
                'jobs' => $orderedJobs,
                'recommendations' => $orderedJobs,
                'ordered_job_ids' => $ids,
                'total' => count($orderedJobs)
            ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::warning("AI request failed, using fallback: " . $e->getMessage());
            [$orderedJobs, $ids] = $this->fallbackOrderJobs($profileData, $jobs);
            return response()->json([
                'success' => true,
                'data' => $orderedJobs,
                'jobs' => $orderedJobs,
                'recommendations' => $orderedJobs,
                'ordered_job_ids' => $ids,
                'total' => count($orderedJobs),
                'fallback' => true
            ], 200);
        } catch (\Throwable $e) {
            Log::warning("Unexpected error in AI, using fallback: " . $e->getMessage());
            [$orderedJobs, $ids] = $this->fallbackOrderJobs($profileData, $jobs);
            return response()->json([
                'success' => true,
                'data' => $orderedJobs,
                'jobs' => $orderedJobs,
                'recommendations' => $orderedJobs,
                'ordered_job_ids' => $ids,
                'total' => count($orderedJobs),
                'fallback' => true
            ], 200);
        }
    }


    /**
     * استدعاء خدمة الذكاء الاصطناعي لتوليد رسالة تغطية.
     */
    public function generateCoverLetter(Request $request)
    {
        // 1. جلب البروفايل المصادق عليه
        $profileData = $this->getAuthenticatedProfile();
        if ($profileData instanceof \Illuminate\Http\JsonResponse) {
            return $profileData;
        }

        // 2. التحقق من مُعرف الوظيفة (job_id)
        $jobId = $request->input('job_id');
        if (empty($jobId)) {
            return response()->json([
                'success' => false,
                'message' => 'مُعرف الوظيفة (job_id) مطلوب لتوليد رسالة التغطية.'
            ], 400);
        }

        // 3. جلب بيانات الوظيفة من قاعدة البيانات
        try {
            $jobData = Job::with(['company:id,company_name', 'skills:id,name'])->find($jobId);
            if (!$jobData) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على الوظيفة المطلوبة باستخدام هذا المُعرف.'
                ], 404);
            }
            $jobData = $jobData->toArray(); // تحويل نموذج Eloquent إلى مصفوفة
        } catch (\Throwable $e) {
            Log::error("Failed to fetch job data: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل الوظيفة.'
            ], 500);
        }

        $formattedProfile = $this->formatProfileData($profileData);
        $formattedJob = $this->formatJobData($jobData);

        $systemPrompt = "أنت كاتب محترف لرسائل التغطية (Cover Letters). مهمتك هي كتابة رسالة تغطية قوية ومقنعة باللغة العربية. يجب أن تكون الرسالة موجهة نحو الوظيفة المحددة وتبرز بشكل خاص الخبرات والمهارات الأكثر صلة الموجودة في ملف المرشح. يجب أن تكون الرسالة موجزة واحترافية (بحد أقصى 350 كلمة). الرد يجب أن يكون رسالة التغطية فقط، بدون أي مقدمات أو خاتمات إضافية.";
        $userPrompt = "إليك بيانات المرشح وبيانات الوظيفة. قم بكتابة رسالة التغطية بناءً عليها.\n\n"
                      . $formattedProfile . "\n"
                      . $formattedJob;

        // استدعاء خدمة الذكاء الاصطناعي
        try {
            // استخدام نفس إعدادات المهلة 120 ثانية لحل مشكلة cURL السابقة
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI Cover Letter Generator'
            ])->timeout(120) // مهلة الطلب الكلية: 120 ثانية
              ->withOptions(['connect_timeout' => 120]) // مهلة الاتصال الأولي: 120 ثانية
              ->post($this->apiEndpoint, [ 
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
            ]);

            $response->throw();

            $resp = json_decode($response->body(), true);
            $content = $resp['choices'][0]['message']['content'] ?? '';
            return response()->json([
                'success' => true,
                'data' => $content,
                'cover_letter' => $content
            ], 200);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $fallback = $this->generateCoverLetterFallback($profileData, $jobData);
            return response()->json([
                'success' => true,
                'data' => $fallback,
                'cover_letter' => $fallback,
                'fallback' => true
            ], 200);
        } catch (\Throwable $e) {
            $fallback = $this->generateCoverLetterFallback($profileData, $jobData);
            return response()->json([
                'success' => true,
                'data' => $fallback,
                'cover_letter' => $fallback,
                'fallback' => true
            ], 200);
        }
    }

    private function generateCoverLetterFallback(array $profileData, array $jobData): string
    {
        $name = $profileData['user']['name'] ?? 'مرشح';
        $title = $profileData['title'] ?? '';
        $summary = $profileData['summary'] ?? ($profileData['bio'] ?? '');
        $years = $profileData['years_of_experience'] ?? '';
        $location = $profileData['location'] ?? '';
        $skills = [];
        if (!empty($profileData['skills'])) {
            foreach ($profileData['skills'] as $s) {
                if (!empty($s['name'])) {
                    $skills[] = $s['name'];
                }
            }
        }
        $skillsText = empty($skills) ? '' : implode('، ', $skills);
        $jobTitle = $jobData['title'] ?? '';
        $companyName = isset($jobData['company']['company_name']) ? $jobData['company']['company_name'] : ($jobData['company_name'] ?? '');
        $requirements = $jobData['requirements'] ?? '';
        $description = $jobData['description'] ?? '';
        $jobSkillNames = [];
        if (!empty($jobData['skills'])) {
            foreach ($jobData['skills'] as $js) {
                if (!empty($js['name'])) {
                    $jobSkillNames[] = $js['name'];
                }
            }
        }
        if (empty($jobSkillNames)) {
            $text = ($requirements ?? '') . ' ' . ($description ?? '');
            $parts = preg_split('/[\\,\\؛\\،\\|\\-\\n\\.]+/u', $text);
            foreach ($parts as $tok) {
                $t = trim($tok);
                if ($t !== '' && mb_strlen($t) > 2) {
                    $jobSkillNames[] = $t;
                }
            }
            $jobSkillNames = array_slice(array_values(array_unique($jobSkillNames)), 0, 10);
        }
        $employeeSkillsLower = array_map('mb_strtolower', $skills);
        $jobSkillsLower = array_map('mb_strtolower', $jobSkillNames);
        $matchingLower = array_values(array_intersect($employeeSkillsLower, $jobSkillsLower));
        $missingLower = array_values(array_diff($jobSkillsLower, $employeeSkillsLower));
        $matchedDisplay = [];
        foreach ($jobSkillNames as $n) {
            if (in_array(mb_strtolower($n), $matchingLower, true)) {
                $matchedDisplay[] = $n;
            }
        }
        $missingDisplay = [];
        foreach ($jobSkillNames as $n) {
            if (in_array(mb_strtolower($n), $missingLower, true)) {
                $missingDisplay[] = $n;
            }
        }
        $paragraphs = [];
        $paragraphs[] = "السادة/ {$companyName}\nتحية طيبة وبعد،";
        $introParts = [];
        if ($title !== '') {
            $introParts[] = "{$title}";
        }
        if ($years !== '') {
            $introParts[] = "بخبرة تمتد إلى {$years} سنة";
        }
        if ($location !== '') {
            $introParts[] = "من {$location}";
        }
        $intro = empty($introParts) ? "أتقدم بطلبي للتقديم على وظيفة {$jobTitle} لديكم." : "أنا {$name}، " . implode('، ', $introParts) . "، أتقدم بطلبي للتقديم على وظيفة {$jobTitle}.";
        $paragraphs[] = $intro;
        $matchLines = [];
        if (!empty($matchedDisplay)) {
            $matchLines[] = "تتطابق مهاراتي مع متطلبات هذه الوظيفة، لا سيما: " . implode('، ', array_slice($matchedDisplay, 0, 6)) . ".";
        } elseif ($skillsText !== '') {
            $matchLines[] = "أمتلك مجموعة من المهارات ذات الصلة مثل: {$skillsText}.";
        }
        if (!empty($jobSkillNames)) {
            $matchLines[] = "اطلعت على المتطلبات المذكورة ومن بينها: " . implode('، ', array_slice($jobSkillNames, 0, 6)) . ".";
        } elseif ($requirements !== '') {
            $matchLines[] = "كما أن متطلباتها تتوافق مع خبراتي العملية ومسؤولياتي السابقة.";
        }
        if (!empty($missingDisplay)) {
            $matchLines[] = "أدرك الحاجة لتعزيز مهارات: " . implode('، ', array_slice($missingDisplay, 0, 4)) . "، وقد بدأت بالفعل في تحسينها لضمان أداء عالي في دور {$jobTitle}.";
        }
        $paragraphs[] = implode(' ', $matchLines);
        if ($summary !== '') {
            $paragraphs[] = "خلاصة عني: {$summary}.";
        }
        if ($description !== '') {
            $paragraphs[] = "أرى أن طبيعة العمل لديكم، كما ورد في الوصف، تتماشى مع تطلعاتي المهنية وقدرتي على الإسهام بقيمة ملموسة ضمن فريقكم.";
        }
        $paragraphs[] = "أتطلع لفرصة مناقشة كيف يمكنني الإسهام في نجاح {$companyName}. شاكرين ومقدرين وقتكم.";
        $paragraphs[] = "مع خالص التحية،\n{$name}";
        return implode("\n\n", array_filter($paragraphs, function ($p) { return trim($p) !== ''; }));
    }

    private function fallbackOrderJobs(array $profileData, array $jobs): array
    {
        $employeeSkills = [];
        if (!empty($profileData['skills'])) {
            foreach ($profileData['skills'] as $s) {
                if (!empty($s['name'])) {
                    $employeeSkills[] = mb_strtolower($s['name']);
                }
            }
        }
        $scored = [];
        foreach ($jobs as $job) {
            $jobSkills = [];
            if (!empty($job['skills'])) {
                foreach ($job['skills'] as $js) {
                    if (!empty($js['name'])) {
                        $jobSkills[] = mb_strtolower($js['name']);
                    }
                }
            }
            $score = 0;
            if (!empty($employeeSkills) && !empty($jobSkills)) {
                $matching = array_intersect($employeeSkills, $jobSkills);
                $score = count($jobSkills) > 0 ? (count($matching) / count($jobSkills)) * 100 : 0;
            }
            $scored[] = ['job' => $job, 'score' => round($score)];
        }
        usort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        $orderedJobs = array_map(function ($entry) { return $entry['job']; }, $scored);
        $ids = array_map(function ($j) { return $j['id']; }, $orderedJobs);
        return [$orderedJobs, $ids];
    }
}
