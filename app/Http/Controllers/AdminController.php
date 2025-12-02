<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Job;
use App\Models\Application;
use App\Models\Post;
use App\Models\Suggestion;
use App\Models\EmployeeProfile;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\Comment;
use App\Models\Like;
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

            // 1. التحقق من صلاحيات المدير
            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للمدراء فقط'
                ], 403);
            }

            // 2. جلب البيانات الكاملة من جميع الجداول
            // ملاحظة: جلب جميع البيانات (get()) قد يكون غير فعال للمشاريع الكبيرة.
            // يمكنك التبديل إلى الخيار البديل (أحدث 10 سجلات) أدناه لتحسين الأداء.

            $fullData = [
                // جدول المستخدمين (Users)
                'users' => [
                    'total_count' => User::count(),
                    'all' => User::all(), // جلب جميع المستخدمين
                    'employees' => User::where('role', 'employee')->get(),
                    'companies_users' => User::where('role', 'company')->get(),
                    'admins' => User::where('role', 'admin')->get(),
                ],

                // جدول الشركات (Companies)
                'companies' => [
                    'total_count' => Company::count(),
                    'all' => Company::all(), // جلب جميع الشركات
                    'verified' => Company::where('is_verified', true)->get(),
                ],

                // جدول الوظائف (Jobs) - هنا نستخدم Eager Loading لتحميل بيانات الشركة
                'jobs' => [
                    'total_count' => Job::count(),
                    'all' => Job::with('company')->get(), // جلب جميع الوظائف مع بيانات الشركة
                    'published' => Job::where('status', 'published')->with('company')->get(),
                    'draft' => Job::where('status', 'draft')->with('company')->get(),
                    'closed' => Job::where('status', 'closed')->with('company')->get(),
                ],

                // جدول طلبات التوظيف (Applications) - تحميل بيانات الوظيفة والمتقدم
                'applications' => [
                    'total_count' => Application::count(),
                    'all' => Application::with(['job', 'employeeProfile'])->get(), // جلب جميع الطلبات مع بيانات الوظيفة والمستخدم
                    'pending' => Application::where('status', 'pending')->with(['job', 'employeeProfile'])->get(),
                    'accepted' => Application::where('status', 'accepted')->with(['job', 'employeeProfile'])->get(),
                ],

                // جدول المنشورات (Posts) - تحميل بيانات المستخدم الذي أنشأ المنشور
                'posts' => [
                    'total_count' => Post::count(),
                    'all' => Post::with('user')->get(), // جلب جميع المنشورات مع بيانات المستخدم
                    'text' => Post::where('type', 'text')->with('user')->get(),
                    'job_posts' => Post::where('type', 'job_post')->with('user')->get(),
                ],

            ];

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات الكاملة بنجاح.',
                'data' => $fullData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching admin dashboard data: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات لوحة التحكم الكاملة',
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

            $query = Job::with(['company', 'skills']);

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
