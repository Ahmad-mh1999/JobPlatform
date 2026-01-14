<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Job;
use App\Models\EmployeeProfile;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run()
    {
        $jobs = Job::all()->values();
        $employeeProfiles = EmployeeProfile::all()->values();

        if ($jobs->isEmpty() || $employeeProfiles->isEmpty()) {
            return;
        }

        $applications = [
            [
                'job_index' => 0,
                'profile_index' => 0,
                'cover_letter' => 'أنا مهتم جداً بهذه الوظيفة وأمتلك الخبرة المطلوبة في Laravel وReact. لدي خبرة 5 سنوات في تطوير تطبيقات الويب.',
                'recommendation_message' => 'أرجو من حضرتكم مراجعة ملفي، وأتعهد بالالتزام والجودة وتسليم المهام في الوقت المحدد. أستطيع البدء فوراً والعمل ضمن فريق.',
                'status' => 'pending',
            ],
            [
                'job_index' => 1,
                'profile_index' => 1,
                'cover_letter' => 'أنا متخصصة في React.js ولدي خبرة في تطوير واجهات المستخدم الحديثة. أتطلع للانضمام إلى فريقكم.',
                'recommendation_message' => 'أمتلك خبرة قوية في بناء واجهات قابلة للتوسع وتحسين الأداء وتجربة المستخدم. يسعدني إجراء اختبار أو مقابلة تقنية في أي وقت.',
                'status' => 'reviewed',
                'notes' => 'تمت مراجعة السيرة الذاتية وتحديد نقاط قوة جيدة في الواجهة.',
                'match_score' => 78,
            ],
            [
                'job_index' => 0,
                'profile_index' => 2,
                'cover_letter' => 'مع خبرتي الواسعة في تطوير التطبيقات المؤسسية، أعتقد أنني سأكون إضافة قيمة لفريقكم.',
                'recommendation_message' => 'عملت على أنظمة كبيرة متعددة الخدمات، وأهتم بجودة الكود والاختبارات. أتطلع لمناقشة تفاصيل الدور وما يمكنني تقديمه.',
                'status' => 'shortlisted',
                'notes' => 'مرشح مناسب وتم إدراجه ضمن القائمة القصيرة.',
                'match_score' => 85,
            ],
            [
                'job_index' => 3,
                'profile_index' => 3,
                'cover_letter' => 'أنا متخصصة في تطوير التطبيقات المحمولة وأمتلك المهارات المطلوبة في Flutter.',
                'recommendation_message' => 'لدي خبرة في نشر تطبيقات على المتاجر وربطها بخدمات الخلفية. يسعدني عرض نماذج من أعمالي خلال المقابلة.',
                'status' => 'interviewed',
                'notes' => 'تمت المقابلة الأولية بخصوص Flutter وواجهات المستخدم.',
                'match_score' => 74,
            ],
            [
                'job_index' => 4,
                'profile_index' => 4,
                'cover_letter' => 'خبرتي في تحليل الأنظمة وPython تجعلني مرشحاً مناسباً لهذه الوظيفة.',
                'recommendation_message' => 'أجيد تحليل المتطلبات وتحويلها إلى وثائق واضحة ومخططات، وأستطيع التعاون مع فرق التطوير لضمان تسليم مطابق للاحتياج.',
                'status' => 'accepted',
                'notes' => 'تم القبول بعد مطابقة المتطلبات والخبرة.',
                'match_score' => 92,
            ],
            [
                'job_index' => 2,
                'profile_index' => 0,
                'cover_letter' => 'لدي معرفة جيدة بـ DevOps وأدوات النشر الآلي.',
                'recommendation_message' => 'عملت على CI/CD وتهيئة بيئات الإنتاج ومراقبة الخدمات. أتطلع لتطبيق أفضل الممارسات ضمن فريقكم.',
                'status' => 'pending',
                'match_score' => 66,
            ],
            [
                'job_index' => 5,
                'profile_index' => 2,
                'cover_letter' => 'أنا مطور Python ذو خبرة وأعمل على مشاريع الذكاء الاصطناعي.',
                'recommendation_message' => 'خبرتي تشمل بناء APIs ودمج نماذج تعلم الآلة، وتحسين الأداء. يسعدني مشاركة أمثلة من مشاريع سابقة عند الطلب.',
                'status' => 'rejected',
                'notes' => 'تم الرفض بسبب عدم تطابق بعض المتطلبات الأساسية.',
                'match_score' => 45,
            ],
        ];

        foreach ($applications as $seed) {
            $job = $jobs->get($seed['job_index']) ?? $jobs->first();
            $profile = $employeeProfiles->get($seed['profile_index']) ?? $employeeProfiles->first();

            $attributes = [
                'job_id' => $job->id,
                'employee_profile_id' => $profile->id,
            ];

            $values = [
                'job_seeker_id' => $profile->user_id,
                'cover_letter' => $seed['cover_letter'] ?? null,
                'recommendation_message' => $seed['recommendation_message'] ?? null,
                'status' => $seed['status'] ?? 'pending',
                'notes' => $seed['notes'] ?? null,
                'match_score' => $seed['match_score'] ?? null,
            ];

            Application::updateOrCreate($attributes, $values);
        }
    }
}
