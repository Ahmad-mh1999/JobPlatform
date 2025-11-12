<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Company;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    public function run()
    {
        $companies = Company::all();
        $skills = Skill::all();

        $jobs = [
            [
                'company_id' => $companies[0]->id,
                'title' => 'مطور ويب كامل - Full Stack Developer',
                'description' => 'نبحث عن مطور ويب كامل ذو خبرة في تطوير تطبيقات الويب باستخدام Laravel وReact',
                'requirements' => 'خبرة لا تقل عن 3 سنوات في تطوير الويب، إتقان Laravel وReact، معرفة بقواعد البيانات',
                'responsibilities' => 'تطوير وصيانة تطبيقات الويب، العمل مع فريق التطوير، متابعة أحدث التقنيات',
                'location' => 'الرياض، المملكة العربية السعودية',
                'job_type' => 'full-time',
                'work_mode' => 'hybrid',
                'experience_level' => 'mid',
                'salary_min' => 8000,
                'salary_max' => 15000,
                'salary_currency' => 'SAR',
                'salary_period' => 'month',
                'vacancies' => 2,
                'deadline' => now()->addDays(30),
                'status' => 'published',
                'skills' => [1, 2, 11, 12, 21], // PHP, JavaScript, Laravel, React, Git
            ],
            [
                'company_id' => $companies[1]->id,
                'title' => 'مطور frontend - Frontend Developer',
                'description' => 'مطلوب مطور frontend متخصص في React.js للعمل على مشاريع ويب حديثة',
                'requirements' => 'خبرة في React.js، TypeScript، CSS3، HTML5، معرفة بأدوات البناء',
                'responsibilities' => 'تطوير واجهات المستخدم، تحسين الأداء، العمل مع فريق التصميم',
                'location' => 'جدة، المملكة العربية السعودية',
                'job_type' => 'full-time',
                'work_mode' => 'remote',
                'experience_level' => 'mid',
                'salary_min' => 7000,
                'salary_max' => 12000,
                'salary_currency' => 'SAR',
                'salary_period' => 'month',
                'vacancies' => 1,
                'deadline' => now()->addDays(20),
                'status' => 'published',
                'skills' => [2, 7, 12, 21], // JavaScript, TypeScript, React, Git
            ],
            [
                'company_id' => $companies[0]->id,
                'title' => 'مهندس DevOps - DevOps Engineer',
                'description' => 'نبحث عن مهندس DevOps لإدارة البنية التحتية والنشر الآلي',
                'requirements' => 'خبرة في Docker، Kubernetes، AWS، CI/CD، Linux',
                'responsibilities' => 'إدارة البنية التحتية، إعداد خطوط النشر، مراقبة الأنظمة',
                'location' => 'الرياض، المملكة العربية السعودية',
                'job_type' => 'full-time',
                'work_mode' => 'onsite',
                'experience_level' => 'senior',
                'salary_min' => 12000,
                'salary_max' => 20000,
                'salary_currency' => 'SAR',
                'salary_period' => 'month',
                'vacancies' => 1,
                'deadline' => now()->addDays(45),
                'status' => 'published',
                'skills' => [21, 22, 23, 24, 25], // Git, Docker, Kubernetes, AWS, Linux
            ],
            [
                'company_id' => $companies[2]->id,
                'title' => 'مطور تطبيقات محمولة - Mobile App Developer',
                'description' => 'مطلوب مطور تطبيقات محمولة باستخدام Flutter للعمل على مشاريع مبتكرة',
                'requirements' => 'خبرة في Flutter، Dart، Firebase، معرفة بأنماط التصميم',
                'responsibilities' => 'تطوير تطبيقات Android وiOS، اختبار التطبيقات، نشر على المتاجر',
                'location' => 'الدمام، المملكة العربية السعودية',
                'job_type' => 'full-time',
                'work_mode' => 'hybrid',
                'experience_level' => 'mid',
                'salary_min' => 6000,
                'salary_max' => 10000,
                'salary_currency' => 'SAR',
                'salary_period' => 'month',
                'vacancies' => 1,
                'deadline' => now()->addDays(25),
                'status' => 'published',
                'skills' => [2, 26], // JavaScript, Node.js
            ],
            [
                'company_id' => $companies[1]->id,
                'title' => 'محلل أنظمة - System Analyst',
                'description' => 'مطلوب محلل أنظمة لتحليل وتصميم الأنظمة ومتطلبات المستخدمين',
                'requirements' => 'خبرة في تحليل الأنظمة، UML، قواعد البيانات، Python أو Node.js',
                'responsibilities' => 'تحليل المتطلبات، تصميم الأنظمة، كتابة التوثيق الفني',
                'location' => 'جدة، المملكة العربية السعودية',
                'job_type' => 'full-time',
                'work_mode' => 'onsite',
                'experience_level' => 'mid',
                'salary_min' => 9000,
                'salary_max' => 16000,
                'salary_currency' => 'SAR',
                'salary_period' => 'month',
                'vacancies' => 1,
                'deadline' => now()->addDays(35),
                'status' => 'published',
                'skills' => [3, 26, 31, 32], // Python, Node.js, REST API, GraphQL
            ],
            [
                'company_id' => $companies[0]->id,
                'title' => 'مطور Python - Python Developer',
                'description' => 'مطلوب مطور Python للعمل على مشاريع الذكاء الاصطناعي والتعلم الآلي',
                'requirements' => 'خبرة في Python، Django، Machine Learning، SQL',
                'responsibilities' => 'تطوير تطبيقات Python، العمل على نماذج الذكاء الاصطناعي',
                'location' => 'الرياض، المملكة العربية السعودية',
                'job_type' => 'full-time',
                'work_mode' => 'remote',
                'experience_level' => 'senior',
                'salary_min' => 10000,
                'salary_max' => 18000,
                'salary_currency' => 'SAR',
                'salary_period' => 'month',
                'vacancies' => 1,
                'deadline' => now()->addDays(40),
                'status' => 'published',
                'skills' => [3, 13, 21], // Python, Django, Git
            ],
        ];

        foreach ($jobs as $jobData) {
            $skillsArray = $jobData['skills'];
            unset($jobData['skills']);

            $job = Job::create($jobData);

            // Attach skills
            foreach ($skillsArray as $skillId) {
                $job->skills()->attach($skillId, ['is_required' => true]);
            }
        }
    }
}
