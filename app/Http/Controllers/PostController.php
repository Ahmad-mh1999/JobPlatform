<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    // List posts
    public function index(Request $request)
    {
        $query = Post::with(['user', 'job', 'comments.user', 'likes'])->recent();

        // Filter by type if provided
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by visibility
        if ($request->has('visibility')) {
            $query->where('visibility', $request->visibility);
        } else {
            $query->public();
        }

        $posts = $query->paginate(10);

        return response()->json($posts);
    }

    // Create a new post
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'url',
            'video' => 'nullable|url',
            'type' => 'nullable|in:text,job_post,article,achievement',
            'job_id' => 'nullable|exists:jobs,id',
            'visibility' => 'nullable|in:public,connections,private',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = Post::create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'images' => $request->images,
            'video' => $request->video,
            'type' => $request->type ?? 'text',
            'job_id' => $request->job_id,
            'visibility' => $request->visibility ?? 'public',
        ]);

        return response()->json($post->load(['user', 'job']), 201);
    }

    // Show a single post
    public function show($id)
    {
        $post = Post::with(['user', 'job', 'comments.user', 'likes.user'])->findOrFail($id);

        return response()->json($post);
    }

    // Update a post
    public function update(Request $request, $id)
    {
        $post = Post::where('user_id', Auth::id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|required|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'url',
            'video' => 'nullable|url',
            'visibility' => 'nullable|in:public,connections,private',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post->update($request->only(['content', 'images', 'video', 'visibility']));

        return response()->json($post->load(['user', 'job']));
    }

    // Delete a post
    public function destroy($id)
    {
        $user = Auth::user();
        $post = Post::findOrFail($id);

        // Check if user is admin or post owner
        if ($user->role !== 'admin' && $post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بحذف هذا المنشور'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المنشور بنجاح'
        ]);
    }

    // Like or unlike a post
    public function like($id)
    {
        $post = Post::findOrFail($id);
        $userId = Auth::id();

        $existingLike = Like::where('user_id', $userId)
            ->where('likeable_id', $id)
            ->where('likeable_type', Post::class)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $post->decrementLikes();
            return response()->json(['message' => 'Post unliked']);
        } else {
            Like::create([
                'user_id' => $userId,
                'likeable_id' => $id,
                'likeable_type' => Post::class,
            ]);
            $post->incrementLikes();
            return response()->json(['message' => 'Post liked']);
        }
    }

    // Add a comment to a post
    public function addComment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = Post::findOrFail($id);

        $comment = Comment::create([
            'post_id' => $id,
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        $post->incrementComments();

        return response()->json($comment->load('user'), 201);
    }

    // Get comments for a post
    public function getComments($id)
    {
        $post = Post::findOrFail($id);
        $comments = $post->comments()->with('user')->orderBy('created_at', 'desc')->get();

        return response()->json($comments);
    }
}
