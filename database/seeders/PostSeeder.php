<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Models\Job;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $jobs = Job::all();

        $posts = [
            [
                'user_id' => $users->where('role', 'employee')->first()->id,
                'content' => 'أنا متحمس جداً للبدء في وظيفتي الجديدة كمطور ويب!',
                'type' => 'achievement',
                'visibility' => 'public',
            ],
            [
                'user_id' => $users->where('role', 'company')->first()->id,
                'content' => 'نحن نبحث عن مطورين ذوي خبرة في Laravel و React',
                'type' => 'job_post',
                'job_id' => $jobs->first()->id,
                'visibility' => 'public',
            ],
            [
                'user_id' => $users->where('role', 'employee')->skip(1)->first()->id,
                'content' => 'شاركت في مؤتمر التكنولوجيا اليوم وكان رائعاً جداً',
                'type' => 'article',
                'visibility' => 'connections',
            ],
            [
                'user_id' => $users->where('role', 'employee')->skip(2)->first()->id,
                'content' => 'نصيحة للمطورين الجدد: لا تتوقفوا عن التعلم!',
                'type' => 'text',
                'visibility' => 'public',
            ],
        ];

        foreach ($posts as $post) {
            Post::create($post);
        }
    }
}
