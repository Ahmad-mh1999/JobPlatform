<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeProfileSeeder extends Seeder
{
    public function run()
    {
        $employeeUsers = User::where('role', 'employee')->get();

        $profiles = [
            [
                'user_id' => $employeeUsers[0]->id,
                'title' => 'مطور ويب كامل',
                'bio' => 'مطور ويب ذو خبرة في PHP وLaravel مع شغف للتعلم المستمر',
                'summary' => 'مطور ويب متخصص في Laravel وReact مع 5 سنوات خبرة',
                'location' => 'الرياض، المملكة العربية السعودية',
                'linkedin_url' => 'https://linkedin.com/in/ahmed-mohamed',
                'github_url' => 'https://github.com/ahmedmohamed',
                'portfolio_url' => 'https://ahmedmohamed.dev',
                'years_of_experience' => 5,
                'expected_salary' => 12000,
                'is_available' => true,
                'languages' => json_encode(['العربية' => 'الأم', 'الإنجليزية' => 'جيد']),
            ],
            [
                'user_id' => $employeeUsers[1]->id,
                'title' => 'مطورة Frontend',
                'bio' => 'مطورة frontend متخصصة في React وVue.js مع خبرة في تصميم واجهات المستخدم',
                'summary' => 'مطورة frontend متخصصة في React وVue.js مع 4 سنوات خبرة',
                'location' => 'جدة، المملكة العربية السعودية',
                'linkedin_url' => 'https://linkedin.com/in/fatma-ali',
                'github_url' => 'https://github.com/fatmaali',
                'portfolio_url' => 'https://fatmaali.design',
                'years_of_experience' => 4,
                'expected_salary' => 10000,
                'is_available' => true,
                'languages' => json_encode(['العربية' => 'الأم', 'الإنجليزية' => 'جيد جداً']),
            ],
            [
                'user_id' => $employeeUsers[2]->id,
                'title' => 'مهندس برمجيات',
                'bio' => 'مهندس برمجيات كامل مع خبرة في تطوير التطبيقات المؤسسية',
                'summary' => 'مهندس برمجيات متخصص في الأنظمة المؤسسية مع 8 سنوات خبرة',
                'location' => 'الدمام، المملكة العربية السعودية',
                'linkedin_url' => 'https://linkedin.com/in/mohamed-hassan',
                'github_url' => 'https://github.com/mohamedhassan',
                'years_of_experience' => 8,
                'expected_salary' => 18000,
                'is_available' => true,
                'languages' => json_encode(['العربية' => 'الأم', 'الإنجليزية' => 'ممتاز']),
            ],
            [
                'user_id' => $employeeUsers[3]->id,
                'title' => 'مطورة تطبيقات محمولة',
                'bio' => 'مطورة mobile applications متخصصة في Flutter وReact Native',
                'summary' => 'مطورة تطبيقات محمولة متخصصة في Flutter مع 3 سنوات خبرة',
                'location' => 'الرياض، المملكة العربية السعودية',
                'linkedin_url' => 'https://linkedin.com/in/sara-ahmed',
                'github_url' => 'https://github.com/saraahmed',
                'years_of_experience' => 3,
                'expected_salary' => 8000,
                'is_available' => true,
                'languages' => json_encode(['العربية' => 'الأم', 'الإنجليزية' => 'جيد']),
            ],
            [
                'user_id' => $employeeUsers[4]->id,
                'title' => 'محلل أنظمة',
                'bio' => 'محلل أنظمة ومطور backend مع خبرة في Python وNode.js',
                'summary' => 'محلل أنظمة متخصص في تحليل وتصميم الأنظمة مع 6 سنوات خبرة',
                'location' => 'مكة المكرمة، المملكة العربية السعودية',
                'linkedin_url' => 'https://linkedin.com/in/ali-mahmoud',
                'github_url' => 'https://github.com/alimahmoud',
                'years_of_experience' => 6,
                'expected_salary' => 13000,
                'is_available' => true,
                'languages' => json_encode(['العربية' => 'الأم', 'الإنجليزية' => 'جيد جداً']),
            ],
        ];

        foreach ($profiles as $profile) {
            EmployeeProfile::create($profile);
        }
    }
}
