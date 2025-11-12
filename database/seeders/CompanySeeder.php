<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run()
    {
        $companyUsers = User::where('role', 'company')->get();

        $companies = [
            [
                'user_id' => $companyUsers[0]->id,
                'company_name' => 'شركة التقنية المتقدمة',
                'category' => 'تطوير البرمجيات',
                'website' => 'https://tech-advanced.com',
                'location' => 'الرياض، المملكة العربية السعودية',
                'description' => 'شركة متخصصة في تطوير البرمجيات والحلول التقنية المتقدمة',
                'founded_year' => 2018,
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/company/tech-advanced',
                    'twitter' => 'https://twitter.com/techadvanced'
                ]),
                'is_verified' => true,
            ],
            [
                'user_id' => $companyUsers[1]->id,
                'company_name' => 'شركة البرمجيات الحديثة',
                'category' => 'خدمات تقنية المعلومات',
                'website' => 'https://modern-software.com',
                'location' => 'جدة، المملكة العربية السعودية',
                'description' => 'نوفر حلول برمجية متكاملة للشركات والمؤسسات',
                'founded_year' => 2015,
                'social_links' => json_encode([
                    'linkedin' => 'https://linkedin.com/company/modern-software',
                    'facebook' => 'https://facebook.com/modernsoftware'
                ]),
                'is_verified' => true,
            ],
            [
                'user_id' => $companyUsers[2]->id,
                'company_name' => 'مؤسسة التطوير الرقمي',
                'category' => 'التسويق الرقمي والتطوير',
                'website' => 'https://digital-dev.com',
                'location' => 'الدمام، المملكة العربية السعودية',
                'description' => 'متخصصون في التطوير الرقمي والتسويق الإلكتروني',
                'founded_year' => 2020,
                'social_links' => json_encode([
                    'instagram' => 'https://instagram.com/digitaldev',
                    'youtube' => 'https://youtube.com/digitaldev'
                ]),
                'is_verified' => false,
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
