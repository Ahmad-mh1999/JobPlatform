<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@platform.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create company users
        $companies = [
            [
                'name' => 'شركة التقنية المتقدمة',
                'email' => 'company1@example.com',
                'password' => Hash::make('password'),
                'role' => 'company',
                'is_active' => true,
            ],
            [
                'name' => 'شركة البرمجيات الحديثة',
                'email' => 'company2@example.com',
                'password' => Hash::make('password'),
                'role' => 'company',
                'is_active' => true,
            ],
            [
                'name' => 'مؤسسة التطوير الرقمي',
                'email' => 'company3@example.com',
                'password' => Hash::make('password'),
                'role' => 'company',
                'is_active' => true,
            ],
        ];

        foreach ($companies as $company) {
            User::create($company);
        }

        // Create employee users
        $employees = [
            [
                'name' => 'أحمد محمد',
                'email' => 'employee1@example.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ],
            [
                'name' => 'فاطمة علي',
                'email' => 'employee2@example.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ],
            [
                'name' => 'محمد حسن',
                'email' => 'employee3@example.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ],
            [
                'name' => 'سارة أحمد',
                'email' => 'employee4@example.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ],
            [
                'name' => 'علي محمود',
                'email' => 'employee5@example.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ],
        ];

        foreach ($employees as $employee) {
            User::create($employee);
        }
    }
}
