<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        try {
            \Log::info('Post creation request:', [
                'has_files' => $request->hasFile('images'),
                'all_data_keys' => array_keys($request->all()),
                'files_data' => $request->file('images'),
                'content_filled' => $request->filled('content'),
                'images_count' => $request->hasFile('images') ? count($request->file('images')) : 0
            ]);

            // Debug individual files
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    \Log::info("Image {$index}:", [
                        'exists' => $image ? 'yes' : 'no',
                        'valid' => $image ? ($image->isValid() ? 'yes' : 'no') : 'no',
                        'original_name' => $image ? $image->getClientOriginalName() : 'no-file',
                        'mime_type' => $image ? $image->getMimeType() : 'no-file',
                        'size' => $image ? $image->getSize() : 'no-file',
                        'error' => $image ? $image->getError() : 'no-file',
                        'upload_max_filesize' => ini_get('upload_max_filesize'),
                        'post_max_size' => ini_get('post_max_size'),
                        'max_execution_time' => ini_get('max_execution_time')
                    ]);
                }
            }

            // Check if we have content or images
            if (!$request->filled('content') && !$request->hasFile('images')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content or images are required'
                ], 422);
            }

            // Prepare validation rules dynamically
            $rules = [
                'content' => 'required_without:images|string|max:2000',
                'type' => 'nullable|string|in:text,job_post,article,achievement',
                'job_id' => 'nullable|exists:jobs,id',
                'video' => 'nullable|string|max:255',
                'visibility' => 'nullable|string|in:public,connections,private',
            ];

            // Only add image validation if images are actually uploaded
            if ($request->hasFile('images')) {
                $rules['images'] = 'nullable|array|max:5';
                $rules['images.*'] = 'sometimes|file|image|mimes:jpeg,png,jpg,gif,webp|max:1024';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                \Log::error('Post validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all()),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if we have content or images
            if (!$request->filled('content') && !$request->hasFile('images')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content or images are required'
                ], 422);
            }

            $data = $request->except(['images']);
            
            // Handle image uploads
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $image) {
                    if ($image && $image->isValid()) {
                        try {
                            $path = $image->store('posts', 'public');
                            $images[] = asset('storage/' . $path);
                        } catch (\Exception $e) {
                            \Log::error('Image upload failed:', ['error' => $e->getMessage()]);
                        }
                    }
                }
                $data['images'] = $images;
            }

            $data['user_id'] = Auth::id();
            $data['likes_count'] = 0;
            $data['comments_count'] = 0;
            $data['visibility'] = $data['visibility'] ?? 'public';

            $post = Post::create($data);

            // Load relationships
            $post->load(['user', 'job']);

            return response()->json([
                'success' => true,
                'data' => $post,
                'message' => 'Post created successfully'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating post: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post: ' . $e->getMessage()
            ], 500);
        }
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
        try {
            \Log::info('=== DELETE POST DEBUG ===');
            \Log::info('Delete post request:', ['post_id' => $id]);
            
            // Check if user is authenticated
            if (!Auth::check()) {
                \Log::error('User not authenticated for delete');
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول لحذف المنشور'
                ], 401);
            }
            
            $user = Auth::user();
            \Log::info('Authenticated user:', ['user_id' => $user->id, 'user_role' => $user->role]);
            
            // Find the post
            $post = Post::find($id);
            if (!$post) {
                \Log::error('Post not found:', ['post_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'المنشور غير موجود'
                ], 404);
            }
            
            \Log::info('Post found:', ['post_id' => $post->id, 'post_owner' => $post->user_id]);

            // Check permissions
            $canDelete = false;
            if ($user->role === 'admin') {
                $canDelete = true;
                \Log::info('Admin can delete post');
            } elseif ($post->user_id === $user->id) {
                $canDelete = true;
                \Log::info('Post owner can delete post');
            } else {
                \Log::warning('Unauthorized delete attempt:', [
                    'user_id' => $user->id, 
                    'user_role' => $user->role,
                    'post_id' => $id, 
                    'post_owner' => $post->user_id
                ]);
            }

            if (!$canDelete) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذا المنشور'
                ], 403);
            }

            // Delete the post
            $deleted = $post->delete();
            
            if ($deleted) {
                \Log::info('Post deleted successfully:', ['post_id' => $id]);
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف المنشور بنجاح'
                ]);
            } else {
                \Log::error('Failed to delete post:', ['post_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في حذف المنشور'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Exception in delete post: ' . $e->getMessage(), [
                'post_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الخادم: ' . $e->getMessage()
            ], 500);
        }
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
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $post = Post::findOrFail($id);

            $comment = Comment::create([
                'post_id' => $id,
                'user_id' => Auth::id(),
                'content' => $request->content,
            ]);

            $post->incrementComments();

            return response()->json([
                'success' => true,
                'data' => $comment->load('user'),
                'message' => 'Comment added successfully'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error adding comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get comments for a post
    public function getComments($id)
    {
        try {
            $post = Post::findOrFail($id);
            $comments = $post->comments()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $comments,
                'count' => $comments->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching comments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    // Save or unsave a post
    public function savePost($id)
    {
        $post = Post::findOrFail($id);
        $user = Auth::user();

        if ($user->savedPosts()->where('post_id', $id)->exists()) {
            $user->savedPosts()->detach($id);
            return response()->json(['message' => 'Post unsaved']);
        } else {
            $user->savedPosts()->attach($id);
            return response()->json(['message' => 'Post saved']);
        }
    }

    // Get saved posts for the authenticated user
    public function getSavedPosts()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Check if user has any saved posts first
            $savedCount = DB::table('user_saved_posts')
                ->where('user_id', $user->id)
                ->count();

            if ($savedCount === 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'data' => [],
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 0
                    ]
                ]);
            }

            // Direct query approach - most reliable
            $savedPosts = DB::table('posts')
                ->join('user_saved_posts', 'posts.id', '=', 'user_saved_posts.post_id')
                ->leftJoin('users', 'posts.user_id', '=', 'users.id')
                ->leftJoin('companies', 'posts.user_id', '=', 'companies.user_id')
                ->where('user_saved_posts.user_id', $user->id)
                ->select(
                    'posts.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'companies.company_name',
                    'companies.logo as company_logo'
                )
                ->orderBy('posts.created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $savedPosts
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getSavedPosts: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch saved posts: ' . $e->getMessage(),
                'debug_info' => [
                    'user_id' => Auth::id(),
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile()
                ]
            ], 500);
        }
    }
}
