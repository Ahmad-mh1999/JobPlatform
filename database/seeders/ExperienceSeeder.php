<?php

namespace Database\Seeders;

use App\Models\Experience;
use App\Models\EmployeeProfile;
use Illuminate\Database\Seeder;

class ExperienceSeeder extends Seeder
{
    public function run()
    {
        $employeeProfiles = EmployeeProfile::all();

        $experiences = [
            [
                'employee_profile_id' => $employeeProfiles[0]->id,
                'job_title' => 'مطور ويب كامل',
                'company_name' => 'شركة التقنية الرقمية',
                'location' => 'الرياض، المملكة العربية السعودية',
                'start_date' => '2017-07-01',
                'end_date' => '2020-12-31',
                'is_current' => false,
                'description' => 'تطوير تطبيقات ويب باستخدام Laravel وReact، العمل مع فريق من 5 مطورين',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[0]->id,
                'job_title' => 'مطور ويب كامل',
                'company_name' => 'شركة التطوير الحديث',
                'location' => 'الرياض، المملكة العربية السعودية',
                'start_date' => '2021-01-01',
                'end_date' => null,
                'is_current' => true,
                'description' => 'قيادة فريق التطوير، تطوير تطبيقات كبيرة الحجم',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[1]->id,
                'job_title' => 'مطورة frontend',
                'company_name' => 'شركة التصميم الرقمي',
                'location' => 'جدة، المملكة العربية السعودية',
                'start_date' => '2018-08-01',
                'end_date' => '2021-06-30',
                'is_current' => false,
                'description' => 'تطوير واجهات مستخدم تفاعلية باستخدام React وVue.js',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[1]->id,
                'job_title' => 'مطورة frontend الرئيسية',
                'company_name' => 'شركة الابتكار التقني',
                'location' => 'جدة، المملكة العربية السعودية',
                'start_date' => '2021-07-01',
                'end_date' => null,
                'is_current' => true,
                'description' => 'قيادة فريق frontend، تطوير مكتبات المكونات',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[2]->id,
                'job_title' => 'مهندس برمجيات',
                'company_name' => 'شركة النظم المتقدمة',
                'location' => 'الرياض، المملكة العربية السعودية',
                'start_date' => '2012-09-01',
                'end_date' => '2016-08-31',
                'is_current' => false,
                'description' => 'تطوير أنظمة مؤسسية كبيرة الحجم باستخدام Java وSpring',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[2]->id,
                'job_title' => 'مهندس برمجيات أول',
                'company_name' => 'شركة الحلول التقنية',
                'location' => 'الرياض، المملكة العربية السعودية',
                'start_date' => '2016-09-01',
                'end_date' => null,
                'is_current' => true,
                'description' => 'قيادة مشاريع التحول الرقمي، العمل مع تقنيات السحابة',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[3]->id,
                'job_title' => 'مطورة تطبيقات محمولة',
                'company_name' => 'شركة التطبيقات الذكية',
                'location' => 'الدمام، المملكة العربية السعودية',
                'start_date' => '2019-09-01',
                'end_date' => null,
                'is_current' => true,
                'description' => 'تطوير تطبيقات Android وiOS باستخدام Flutter',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[4]->id,
                'job_title' => 'محلل أنظمة',
                'company_name' => 'شركة الاستشارات التقنية',
                'location' => 'مكة المكرمة، المملكة العربية السعودية',
                'start_date' => '2014-07-01',
                'end_date' => '2018-06-30',
                'is_current' => false,
                'description' => 'تحليل وتصميم أنظمة معلومات للشركات',
                'job_type' => 'full-time',
            ],
            [
                'employee_profile_id' => $employeeProfiles[4]->id,
                'job_title' => 'محلل أنظمة أول',
                'company_name' => 'مؤسسة التقنية المتكاملة',
                'location' => 'مكة المكرمة، المملكة العربية السعودية',
                'start_date' => '2018-07-01',
                'end_date' => null,
                'is_current' => true,
                'description' => 'قيادة مشاريع التحليل والتصميم، تدريب المحللين الجدد',
                'job_type' => 'full-time',
            ],
        ];

        foreach ($experiences as $experience) {
            Experience::create($experience);
        }
    }
}
