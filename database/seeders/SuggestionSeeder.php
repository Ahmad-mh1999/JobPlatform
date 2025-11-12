<?php

namespace Database\Seeders;

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuggestionSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        $suggestions = [
            [
                'user_id' => $users[1]->id, // employee
                'content' => 'أقترح إضافة ميزة البحث المتقدم في الوظائف مع فلترة حسب الراتب والموقع',
                'type' => 'feature',
                'priority' => 'high',
                'status' => 'pending',
            ],
            [
                'user_id' => $users[2]->id, // employee
                'content' => 'يجب تحسين واجهة المستخدم للتطبيقات المحمولة، خاصة في صفحة التقديم للوظائف',
                'type' => 'ui/ux',
                'priority' => 'medium',
                'status' => 'reviewed',
            ],
            [
                'user_id' => $users[3]->id, // company
                'content' => 'أقترح إضافة نظام تقييم للمرشحين من قبل الشركات',
                'type' => 'feature',
                'priority' => 'medium',
                'status' => 'implemented',
            ],
            [
                'user_id' => $users[4]->id, // employee
                'content' => 'مشكلة في تحميل السيرة الذاتية، يجب دعم المزيد من صيغ الملفات',
                'type' => 'bug',
                'priority' => 'high',
                'status' => 'pending',
            ],
            [
                'user_id' => $users[5]->id, // employee
                'content' => 'أقترح إضافة نظام إشعارات فورية عند نشر وظائف جديدة في التخصصات المفضلة',
                'type' => 'feature',
                'priority' => 'high',
                'status' => 'reviewed',
            ],
            [
                'user_id' => $users[6]->id, // company
                'content' => 'يجب إضافة تقارير إحصائية مفصلة للشركات حول المتقدمين',
                'type' => 'feature',
                'priority' => 'low',
                'status' => 'pending',
            ],
            [
                'user_id' => $users[7]->id, // employee
                'content' => 'تحسين أداء التطبيق في تحميل قوائم الوظائف الكبيرة',
                'type' => 'performance',
                'priority' => 'medium',
                'status' => 'implemented',
            ],
        ];

        foreach ($suggestions as $suggestion) {
            Suggestion::create($suggestion);
        }
    }
}
