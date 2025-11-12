<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\EmployeeProfile;
use Illuminate\Database\Seeder;

class EducationSeeder extends Seeder
{
    public function run()
    {
        $employeeProfiles = EmployeeProfile::all();

        $educations = [
            [
                'employee_profile_id' => $employeeProfiles[0]->id,
                'institution' => 'جامعة الملك سعود',
                'degree' => 'بكالوريوس هندسة الحاسب الآلي',
                'field_of_study' => 'هندسة الحاسب الآلي',
                'start_date' => '2013-09-01',
                'end_date' => '2017-06-30',
                'grade' => 3.5,
                'description' => 'تخرجت بتقدير جيد جداً مع مشروع تخرج في تطوير تطبيقات الويب',
            ],
            [
                'employee_profile_id' => $employeeProfiles[1]->id,
                'institution' => 'جامعة الملك عبدالعزيز',
                'degree' => 'بكالوريوس علوم الحاسب',
                'field_of_study' => 'علوم الحاسب',
                'start_date' => '2014-09-01',
                'end_date' => '2018-06-30',
                'grade' => 4.0,
                'description' => 'تخصصت في علوم الحاسب مع التركيز على تطوير الويب والواجهات',
            ],
            [
                'employee_profile_id' => $employeeProfiles[2]->id,
                'institution' => 'جامعة الملك فهد للبترول والمعادن',
                'degree' => 'ماجستير هندسة البرمجيات',
                'field_of_study' => 'هندسة البرمجيات',
                'start_date' => '2008-09-01',
                'end_date' => '2012-06-30',
                'grade' => 4.0,
                'description' => 'حصلت على الماجستير في هندسة البرمجيات مع بحث في الأنظمة الموزعة',
            ],
            [
                'employee_profile_id' => $employeeProfiles[3]->id,
                'institution' => 'جامعة الأميرة نورة',
                'degree' => 'بكالوريوس تقنية المعلومات',
                'field_of_study' => 'تقنية المعلومات',
                'start_date' => '2015-09-01',
                'end_date' => '2019-06-30',
                'grade' => 3.5,
                'description' => 'تخرجت في تقنية المعلومات مع التركيز على تطوير التطبيقات المحمولة',
            ],
            [
                'employee_profile_id' => $employeeProfiles[4]->id,
                'institution' => 'جامعة أم القرى',
                'degree' => 'بكالوريوس نظم المعلومات',
                'field_of_study' => 'نظم المعلومات',
                'start_date' => '2010-09-01',
                'end_date' => '2014-06-30',
                'grade' => 3.5,
                'description' => 'تخرجت في نظم المعلومات مع خبرة في تحليل وتصميم الأنظمة',
            ],
            // Additional education records
            [
                'employee_profile_id' => $employeeProfiles[0]->id,
                'institution' => 'أكاديمية Udacity',
                'degree' => 'دبلوم',
                'field_of_study' => 'تطوير الويب الكامل',
                'start_date' => '2018-01-01',
                'end_date' => '2018-06-30',
                'grade' => 4.0,
                'description' => 'دبلوم متخصص في تطوير الويب الكامل مع مشاريع عملية',
            ],
            [
                'employee_profile_id' => $employeeProfiles[2]->id,
                'institution' => 'جامعة هارفارد',
                'degree' => 'دكتوراه',
                'field_of_study' => 'علوم الحاسب الآلي',
                'start_date' => '2013-09-01',
                'end_date' => '2018-06-30',
                'grade' => 4.0,
                'description' => 'دكتوراه في علوم الحاسب مع بحث في الذكاء الاصطناعي',
            ],
        ];

        foreach ($educations as $education) {
            Education::create($education);
        }
    }
}
