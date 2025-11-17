<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\EmployeeProfile;
use App\Models\Job; // يجب التأكد من وجود هذا النموذج في نظامك

class AiController extends Controller
{
    private $apiEndpoint;
    private $apiKey;
    private $model = 'deepseek/deepseek-chat-v3.1:free'; // يجب التأكد من ضبط إعدادات الخصوصية في OpenRouter

    public function __construct()
    {
        $this->apiEndpoint = 'https://openrouter.ai/api/v1/chat/completions';
        // جلب المفتاح من ملف config/services.php
        $this->apiKey = config('services.openrouter.key');

        // لا نستخدم middleware هنا، بل نتحقق داخل الدالة getAuthenticatedProfile
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
                // **ملاحظة: هذا الوصف يجب أن يكون مفصلاً ليتجنب الذكاء الاصطناعي اختصاره**
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
        $formattedData .= "- الشركة: " . ($jobData['company_name'] ?? 'غير محدد') . "\n";
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

        // 2. جلب الوظائف المتاحة
        try {
            $jobs = Job::select('id', 'title', 'description', 'requirements', 'location', 'company_name')->take(20)->get()->toArray();
            if (empty($jobs)) {
                return response()->json(['success' => false, 'message' => 'لم يتم العثور على أي وظائف متاحة لاقتراحها.'], 404);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to fetch jobs: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء جلب قائمة الوظائف.'], 500);
        }

        $formattedProfile = $this->formatProfileData($profileData);
        $formattedJobs = $this->formatAvailableJobs($jobs);

        $systemPrompt = "أنت مساعد متخصص في التوظيف. مهمتك هي تحليل ملف المرشح (السيرة الذاتية والخبرات والمهارات) ومقارنته بقائمة الوظائف المتاحة. يجب عليك اختيار **أفضل 5 وظائف** من القائمة المقدمة والتي تتطابق مع مؤهلات المرشح. قم بترتيب الوظائف الخمس المختارة من الأكثر ملاءمة إلى الأقل ملاءمة. يجب أن يكون الرد النهائي بصيغة JSON فقط يحتوي على مصفوفة باسم 'suggested_jobs'. لكل وظيفة، ضع 'id' الخاص بها، 'title' و 'matching_reason' (توضيح سبب التوافق).\n\n"
                      . "مثال على الرد:\n"
                      . "```json\n"
                      . "{\n"
                      . "  \"suggested_jobs\": [\n"
                      . "    {\n"
                      . "      \"id\": 101,\n"
                      . "      \"title\": \"مطور ويب React / Laravel\",\n"
                      . "      \"matching_reason\": \"المرشح لديه 5 سنوات خبرة في Laravel و React وهي المتطلبات الأساسية للوظيفة.\"\n"
                      . "    },\n"
                      . "    // ... 4 وظائف أخرى\n"
                      . "  ]\n"
                      . "}\n"
                      . "```\n"
                      . "لا تقم بإضافة أي نصوص إضافية قبل أو بعد كود JSON.";

        $userPrompt = "إليك البيانات:\n" . $formattedProfile . "\n" . $formattedJobs;

        // استدعاء خدمة الذكاء الاصطناعي
        try {
            // استخدام نفس إعدادات المهلة 120 ثانية لحل مشكلة cURL السابقة
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI Job Search Service' // عنوان توضيحي لـ OpenRouter
            ])->timeout(120) // مهلة الطلب الكلية: 120 ثانية
              ->withOptions(['connect_timeout' => 120]) // مهلة الاتصال الأولي: 120 ثانية
              ->post($this->apiEndpoint, [ 
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                // ضبط الاستجابة لتكون JSON
                'response_format' => ['type' => 'json_object'],
            ]);

            $response->throw(); // رمي استثناء إذا كان الكود غير 2xx

            // يتم تمرير الرد كـ JSON string إلى الواجهة الأمامية
            return response()->json([
                'success' => true,
                'data' => json_decode($response->body(), true)['choices'][0]['message']['content']
            ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorMessage = "فشل الاتصال بخدمة الذكاء الاصطناعي. HTTP request returned status code " . $e->response->status() . ":\n" . $e->response->body();
            Log::error($errorMessage);
            return response()->json(['success' => false, 'message' => $errorMessage], $e->response->status());
        } catch (\Throwable $e) {
            Log::error("An unexpected error occurred in suggestJobs: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()], 500);
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
            $jobData = Job::find($jobId);
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

            return response()->json([
                'success' => true,
                'data' => json_decode($response->body(), true)['choices'][0]['message']['content']
            ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorMessage = "فشل الاتصال بخدمة الذكاء الاصطناعي. HTTP request returned status code " . $e->response->status() . ":\n" . $e->response->body();
            Log::error($errorMessage);
            return response()->json(['success' => false, 'message' => $errorMessage], $e->response->status());
        } catch (\Throwable $e) {
            Log::error("An unexpected error occurred in generateCoverLetter: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()], 500);
        }
    }


    /**
     * استدعاء خدمة الذكاء الاصطناعي لتوليد خارطة طريق لتعلم مهنة أو مجال معين.
     * يتطلب إدخال اسم المجال (field_name).
     */
    public function generateRoadmap(Request $request)
    {
        // هذه الخدمة لا تحتاج لملف شخصي، لكنها تحتاج لمصادقة المستخدم
        $user = auth()->user();

        // 1. التحقق من المدخلات
        $fieldName = $request->input('field_name');
        if (empty($fieldName)) {
            return response()->json([
                'success' => false,
                'message' => 'يجب توفير اسم المجال أو المهنة المطلوبة (field_name).'
            ], 400);
        }

        $schemaPlaceholder = $this->getRoadmapSchemaPlaceholder();

        // استخدام JSON_UNESCAPED_UNICODE لضمان عرض المحتوى العربي بشكل صحيح في الـ prompt
        $schemaJson = json_encode($schemaPlaceholder, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $systemPrompt = "أنت خبير توجيه مهني ومصمم مناهج تعليمية. مهمتك هي إنشاء خارطة طريق تعليمية مفصلة وواقعية (Roadmap) للمجال الذي سيتم تحديده من قبل المستخدم.
        
        **إرشادات التنفيذ:**
        1.  يجب أن تكون الاستجابة كائن JSON صالحاً (Valid JSON Object) فقط، بدون أي نصوص أو علامات Markdown (مثل ```json).
        2.  الرد يجب أن يتبع الهيكل الرئيسي: { title, description, estimated_duration_months, modules }.
        3.  يجب تقسيم الخارطة إلى 4 أو 5 مراحل منطقية (Modules).
        4.  لكل مرحلة، يجب أن تتضمن: module_number, module_title, estimated_weeks, و مصفوفة Topics.
        5.  لكل موضوع (Topic)، يجب أن يتضمن: topic_name, مصفوفة key_concepts, و مصفوفة suggested_resources (يجب أن تحتوي على أسماء موارد أو روابط حقيقية).
        
        الرد يجب أن يكون كائن JSON صالحاً باللغة العربية فقط.";

        $userPrompt = "يرجى توليد خارطة طريق كاملة لتعلم مهنة: " . $fieldName;

        // استدعاء خدمة الذكاء الاصطناعي
        try {
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Title' => 'AI Learning Roadmap Generator'
            ])->timeout(60) 
            //   ->withOptions(['connect_timeout' => 120]) 
              ->post($this->apiEndpoint, [ 
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                 // ضبط الاستجابة لتكون JSON
                'response_format' => ['type' => 'json_object'],
            ]);

            $response->throw();

            $decodedBody = json_decode($response->body(), true);
            $roadmapJsonString = $decodedBody['choices'][0]['message']['content'] ?? null;

            // التحقق من أن محتوى الـ JSON الذي ولده النموذج ليس فارغاً
            if (empty($roadmapJsonString)) {
                Log::error("AI Roadmap Generator returned empty content despite 200 OK status. Decoded Body: " . json_encode($decodedBody));
                return response()->json([
                    'success' => false,
                    'message' => 'نجح الاتصال بخدمة الذكاء الاصطناعي، لكن النموذج لم يتمكن من توليد خارطة الطريق المطلوبة. يرجى المحاولة باسم مجال مختلف أو التواصل مع الدعم الفني.',
                ], 500);
            }

            // الرد الآن مضمون أنه JSON (تم التحقق من عدم فراغه)
            return response()->json([
                'success' => true,
                'data' => $roadmapJsonString
            ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorMessage = "فشل الاتصال بخدمة الذكاء الاصطناعي. HTTP request returned status code " . $e->response->status() . ":\n" . $e->response->body();
            Log::error($errorMessage);
            return response()->json(['success' => false, 'message' => $errorMessage], $e->response->status());
        } catch (\Throwable $e) {
            Log::error("An unexpected error occurred in generateRoadmap: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()], 500);
        }
    }
}