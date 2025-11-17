<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::all();
        $users = User::all();

        if ($posts->isEmpty() || $users->isEmpty()) {
            return;
        }

        $comments = [
            'ممتاز! شكراً للمشاركة',
            'معلومات مفيدة جداً',
            'أتفق معك في هذا الرأي',
            'خبرة رائعة، شكراً لك',
            'أحب هذا المنشور',
            'مبادرة رائعة',
            'دعم كامل للفكرة',
            'مشاركة ممتازة',
            'أضفت قيمة كبيرة',
            'شكراً للمعلومات',
        ];

        foreach ($posts as $post) {
            $numComments = rand(0, 5); // 0-5 comments per post

            for ($i = 0; $i < $numComments; $i++) {
                Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $users->random()->id,
                    'content' => $comments[array_rand($comments)],
                ]);

                // Update post comments count
                $post->incrementComments();
            }
        }
    }
}
