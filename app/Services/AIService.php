<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        // استخدام v1 بدلاً من v1beta
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent';
    }

    /**
     * Send prompt to Gemini AI
     */
    public function generateContent($prompt)
    {
        try {
            $response = Http::timeout(30)->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get job recommendations based on employee skills and profile
     */
    public function getJobRecommendations($employeeProfile, $availableJobs)
    {
        $prompt = $this->buildJobRecommendationPrompt($employeeProfile, $availableJobs);
        $response = $this->generateContent($prompt);

        if ($response) {
            return $this->parseJobRecommendations($response);
        }

        return [];
    }

    /**
     * Build prompt for job recommendations
     */
    protected function buildJobRecommendationPrompt($employeeProfile, $availableJobs)
    {
        $skills = $employeeProfile->skills->pluck('name')->toArray();
        $skillsText = implode(', ', $skills);
        
        $jobsText = '';
        foreach ($availableJobs as $index => $job) {
            $requiredSkills = $job->skills->pluck('name')->toArray();
            $jobsText .= "\n" . ($index + 1) . ". Job ID: {$job->id}\n";
            $jobsText .= "   Title: {$job->title}\n";
            $jobsText .= "   Company: {$job->company->company_name}\n";
            $jobsText .= "   Location: {$job->location}\n";
            $jobsText .= "   Type: {$job->job_type}\n";
            $jobsText .= "   Experience Level: {$job->experience_level}\n";
            $jobsText .= "   Required Skills: " . implode(', ', $requiredSkills) . "\n";
            $jobsText .= "   Description: " . substr($job->description, 0, 200) . "...\n";
        }

        return <<<PROMPT
أنت خبير توظيف ومستشار مهني. لديك معلومات عن موظف يبحث عن عمل، وقائمة بالوظائف المتاحة.

معلومات الموظف:
- الاسم: {$employeeProfile->user->name}
- المسمى الوظيفي: {$employeeProfile->title}
- سنوات الخبرة: {$employeeProfile->years_of_experience}
- الموقع: {$employeeProfile->location}
- المهارات: {$skillsText}
- الراتب المتوقع: {$employeeProfile->expected_salary}
- الملخص: {$employeeProfile->summary}

الوظائف المتاحة:
{$jobsText}

المطلوب منك:
1. قم بتحليل مهارات الموظف وخبراته
2. قارنها بمتطلبات كل وظيفة
3. أعط تقييم من 0 إلى 100 لكل وظيفة (مدى التوافق)
4. رتب الوظائف من الأنسب إلى الأقل مناسبة
5. اشرح لماذا كل وظيفة مناسبة أو غير مناسبة

أرجع النتيجة بصيغة JSON التالية فقط (بدون أي نص إضافي):
{
  "recommendations": [
    {
      "job_id": 1,
      "match_score": 95,
      "reasoning": "السبب بالعربية",
      "pros": ["ميزة 1", "ميزة 2"],
      "cons": ["عيب 1", "عيب 2"],
      "advice": "نصيحة للمتقدم"
    }
  ]
}
PROMPT;
    }

    /**
     * Parse AI response to extract job recommendations
     */
    protected function parseJobRecommendations($response)
    {
        try {
            // Remove markdown code blocks if present
            $response = preg_replace('/```json\s*|\s*```/', '', $response);
            $response = trim($response);
            
            $data = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['recommendations'])) {
                return $data['recommendations'];
            }

            Log::warning('Failed to parse AI recommendations', ['response' => $response]);
            return [];

        } catch (\Exception $e) {
            Log::error('Error parsing AI recommendations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Analyze CV and extract skills
     */
    public function analyzeCVText($cvText)
    {
        $prompt = <<<PROMPT
أنت خبير في تحليل السير الذاتية. قم بتحليل السيرة الذاتية التالية واستخراج المعلومات المهمة.

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

        $response = $this->generateContent($prompt);

        if ($response) {
            try {
                $response = preg_replace('/```json\s*|\s*```/', '', $response);
                $response = trim($response);
                return json_decode($response, true);
            } catch (\Exception $e) {
                Log::error('Error parsing CV analysis: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Generate cover letter based on job and profile
     */
    public function generateCoverLetter($employeeProfile, $job)
    {
        $skills = $employeeProfile->skills->pluck('name')->toArray();
        $skillsText = implode(', ', $skills);

        $prompt = <<<PROMPT
أنت كاتب محترف متخصص في كتابة خطابات التقديم.

معلومات المتقدم:
- الاسم: {$employeeProfile->user->name}
- المسمى الوظيفي: {$employeeProfile->title}
- سنوات الخبرة: {$employeeProfile->years_of_experience}
- المهارات: {$skillsText}
- الملخص: {$employeeProfile->summary}

معلومات الوظيفة:
- العنوان: {$job->title}
- الشركة: {$job->company->company_name}
- الوصف: {$job->description}
- المتطلبات: {$job->requirements}

اكتب خطاب تقديم احترافي باللغة العربية (لا يزيد عن 300 كلمة) يبرز:
1. مناسبة المتقدم للوظيفة
2. كيف تتطابق مهاراته مع متطلبات الوظيفة
3. حماسه للانضمام للشركة

اكتب الخطاب مباشرة بدون أي عناوين أو تنسيقات إضافية.
PROMPT;

        return $this->generateContent($prompt);
    }

    /**
     * Match score calculation (fallback if AI fails)
     */
    public function calculateMatchScore($employeeSkills, $jobSkills)
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
}