<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function test(Request $request)  {
        return response()->json($request);
    }
    
    // User registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:employee,company',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البيانات',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = User::create([
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
                'role' => $request->get('role')
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل المستخدم بنجاح',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // User login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البيانات',
                'errors' => $validator->errors()
            ], 400);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات الدخول غير صحيحة'
                ], 401);
            }

            $user = auth()->user();
            $token = JWTAuth::claims(['role' => $user->role])->fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 200);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إنشاء التوكن',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get authenticated user
    public function getUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user
                ]
            ], 200);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التوكن غير صالح',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    public function getOneUser(Request $request)
    {
        try {
            if (! JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول'
                ], 401);
            }

            $oneUser = User::find($request->id);
            
            if (!$oneUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم المطلوب غير موجود'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $oneUser
                ]
            ], 200);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التوكن غير صالح',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    // User logout
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ], 200);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تسجيل الخروج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete User
    public function delete($id)
    {
        try {
            $user = User::findOrFail($id);
            $userData = $user->toArray();
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح',
                'data' => [
                    'deleted_user' => $userData
                ]
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