<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Jop;
use App\Models\Application;
use App\Models\Post;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // Get dashboard statistics
    public function dashboard()
    {
        Log::info('AdminController@dashboard called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $stats = [
                'users' => [
                    'total' => User::count(),
                    'employees' => User::where('role', 'employee')->count(),
                    'companies' => User::where('role', 'company')->count(),
                    'admins' => User::where('role', 'admin')->count(),
                ],
                'companies' => [
                    'total' => Company::count(),
                    'verified' => Company::where('is_verified', true)->count(),
                ],
                'jobs' => [
                    'total' => Jop::count(),
                    'published' => Jop::where('status', 'published')->count(),
                    'draft' => Jop::where('status', 'draft')->count(),
                    'closed' => Jop::where('status', 'closed')->count(),
                ],
                'applications' => [
                    'total' => Application::count(),
                    'pending' => Application::where('status', 'pending')->count(),
                    'accepted' => Application::where('status', 'accepted')->count(),
                ],
                'posts' => [
                    'total' => Post::count(),
                    'text' => Post::where('type', 'text')->count(),
                    'job_posts' => Post::where('type', 'job_post')->count(),
                ],
                'suggestions' => [
                    'total' => Suggestion::count(),
                    'pending' => Suggestion::where('status', 'pending')->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => ['stats' => $stats]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات لوحة التحكم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all users with pagination
    public function getUsers(Request $request)
    {
        Log::info('AdminController@getUsers called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $query = User::with(['employeeProfile', 'company']);

            if ($request->has('role') && $request->role) {
                $query->where('role', $request->role);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $users = $query->orderBy('created_at', 'desc')->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المستخدمين',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update user status
    public function updateUserStatus(Request $request, $id)
    {
        Log::info('AdminController@updateUserStatus called', ['user_id' => auth()->id(), 'target_user_id' => $id]);
        try {
            $admin = auth()->user();

            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $targetUser = User::find($id);

            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $targetUser->update(['is_active' => $request->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة المستخدم بنجاح',
                'data' => ['user' => $targetUser]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Verify company
    public function verifyCompany($id)
    {
        Log::info('AdminController@verifyCompany called', ['user_id' => auth()->id(), 'company_id' => $id]);
        try {
            $admin = auth()->user();

            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة'
                ], 404);
            }

            $company->update(['is_verified' => true]);

            return response()->json([
                'success' => true,
                'message' => 'تم توثيق الشركة بنجاح',
                'data' => ['company' => $company]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء توثيق الشركة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all jobs for admin
    public function getJobs(Request $request)
    {
        Log::info('AdminController@getJobs called', ['user_id' => auth()->id()]);
        try {
            $admin = auth()->user();

            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $query = Jop::with(['company', 'skills']);

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            $jobs = $query->orderBy('created_at', 'desc')->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $jobs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الوظائف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete job (admin)
    public function deleteJob($id)
    {
        Log::info('AdminController@deleteJob called', ['user_id' => auth()->id(), 'job_id' => $id]);
        try {
            $admin = auth()->user();

            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $job = Jop::find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'الوظيفة غير موجودة'
                ], 404);
            }

            $job->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الوظيفة بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الوظيفة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all posts for admin
    public function getPosts(Request $request)
    {
        Log::info('AdminController@getPosts called', ['user_id' => auth()->id()]);
        try {
            $admin = auth()->user();

            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $query = Post::with('user');

            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            $posts = $query->orderBy('created_at', 'desc')->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $posts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنشورات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete post (admin)
    public function deletePost($id)
    {
        Log::info('AdminController@deletePost called', ['user_id' => auth()->id(), 'post_id' => $id]);
        try {
            $admin = auth()->user();

            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            $post = Post::find($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'المنشور غير موجود'
                ], 404);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنشور بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنشور',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
