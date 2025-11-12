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
        $jobs = Job::all();
        $employeeProfiles = EmployeeProfile::all();

        $applications = [
            [
                'job_id' => $jobs[0]->id,
                'employee_profile_id' => $employeeProfiles[0]->id,
                'cover_letter' => 'أنا مهتم جداً بهذه الوظيفة وأمتلك الخبرة المطلوبة في Laravel وReact. لدي خبرة 5 سنوات في تطوير تطبيقات الويب.',
                'status' => 'pending',
            ],
            [
                'job_id' => $jobs[1]->id,
                'employee_profile_id' => $employeeProfiles[1]->id,
                'cover_letter' => 'أنا متخصصة في React.js ولدي خبرة في تطوير واجهات المستخدم الحديثة. أتطلع للانضمام إلى فريقكم.',
                'status' => 'reviewed',
            ],
            [
                'job_id' => $jobs[0]->id,
                'employee_profile_id' => $employeeProfiles[2]->id,
                'cover_letter' => 'مع خبرتي الواسعة في تطوير التطبيقات المؤسسية، أعتقد أنني سأكون إضافة قيمة لفريقكم.',
                'status' => 'shortlisted',
            ],
            [
                'job_id' => $jobs[3]->id,
                'employee_profile_id' => $employeeProfiles[3]->id,
                'cover_letter' => 'أنا متخصصة في تطوير التطبيقات المحمولة وأمتلك المهارات المطلوبة في Flutter.',
                'status' => 'interviewed',
            ],
            [
                'job_id' => $jobs[4]->id,
                'employee_profile_id' => $employeeProfiles[4]->id,
                'cover_letter' => 'خبرتي في تحليل الأنظمة وPython تجعلني مرشحاً مناسباً لهذه الوظيفة.',
                'status' => 'accepted',
            ],
            [
                'job_id' => $jobs[2]->id,
                'employee_profile_id' => $employeeProfiles[0]->id,
                'cover_letter' => 'لدي معرفة جيدة بـ DevOps وأدوات النشر الآلي.',
                'status' => 'pending',
            ],
            [
                'job_id' => $jobs[5]->id,
                'employee_profile_id' => $employeeProfiles[2]->id,
                'cover_letter' => 'أنا مطور Python ذو خبرة وأعمل على مشاريع الذكاء الاصطناعي.',
                'status' => 'pending',
            ],
        ];

        foreach ($applications as $application) {
            Application::create($application);
        }
    }
}
