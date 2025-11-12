<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Get all users with pagination and filters
    public function index(Request $request)
    {
        try {
            $query = User::query();
            
            // Filter by role
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }
            
            // Search by name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $users = $query->paginate($request->get('per_page', 15));
            
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

    // Get single user
    public function show($id)
    {
        try {
            $user = User::with(['profile', 'company'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => ['user' => $user]
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update user
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Check if user can update (own profile or admin)
            if (auth()->id() !== $user->id && auth()->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا المستخدم'
                ], 403);
            }
            
            $validator = \Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,'.$id,
                'password' => 'sometimes|string|min:6',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            if ($request->has('password')) {
                $request->merge(['password' => \Hash::make($request->password)]);
            }
            
            $user->update($request->only(['name', 'email', 'password']));
            
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المستخدم بنجاح',
                'data' => ['user' => $user]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Check if admin
            if (auth()->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف المستخدمين'
                ], 403);
            }
            
            $userData = $user->toArray();
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح',
                'data' => ['deleted_user' => $userData]
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}