<?php

namespace Database\Seeders;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LikeSeeder extends Seeder
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

        foreach ($posts as $post) {
            $numLikes = rand(0, 10); // 0-10 likes per post
            $likedUsers = $users->random(min($numLikes, $users->count()));

            foreach ($likedUsers as $user) {
                Like::create([
                    'user_id' => $user->id,
                    'likeable_id' => $post->id,
                    'likeable_type' => Post::class,
                ]);

                // Update post likes count
                $post->incrementLikes();
            }
        }
    }
}
