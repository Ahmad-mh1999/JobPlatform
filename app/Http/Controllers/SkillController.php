<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Skill;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SkillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Log::info('SkillController@index called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك الصلاحية لعرض المهارات'
                ], 403);
            }

            $skills = Skill::all();
            return response()->json([
                'success' => true,
                'data' => $skills
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المهارات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('SkillController@store called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك الصلاحية لإنشاء مهارات جديدة'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:skills,name',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $skill = Skill::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المهارة بنجاح',
                'data' => ['skill' => $skill]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المهارة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        Log::info('SkillController@show called', ['id' => $id]);
        try {
            $skill = Skill::find($id);

            if (!$skill) {
                return response()->json([
                    'success' => false,
                    'message' => 'المهارة غير موجودة'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['skill' => $skill]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المهارة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Log::info('SkillController@update called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك الصلاحية لتحديث المهارات'
                ], 403);
            }

            $skill = Skill::find($id);

            if (!$skill) {
                return response()->json([
                    'success' => false,
                    'message' => 'المهارة غير موجودة'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:skills,name,' . $id,
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $skill->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المهارة بنجاح',
                'data' => ['skill' => $skill]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المهارة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Log::info('SkillController@destroy called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك الصلاحية لحذف المهارات'
                ], 403);
            }

            $skill = Skill::find($id);

            if (!$skill) {
                return response()->json([
                    'success' => false,
                    'message' => 'المهارة غير موجودة'
                ], 404);
            }

            $skill->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المهارة بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المهارة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
