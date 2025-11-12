<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    // Get employee profile
    public function show($userId)
    {
        Log::info('ProfileController@show called', ['userId' => $userId]);
        try {
            $profile = EmployeeProfile::with(['user', 'skills', 'education', 'experiences'])
                ->find($userId);

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'البروفايل غير موجود'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $profile,
                    'completeness' => $profile->profile_completeness
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البروفايل',
                'error' => $e->getMessage()
            ], 500);
        }
       
    }

    // Create or update employee profile
    public function createOrUpdate(Request $request)
    {
        Log::info('ProfileController@createOrUpdate called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'employee') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للموظفين فقط'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'nullable|string|max:255',
                'bio' => 'nullable|string',
                'summary' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'linkedin_url' => 'nullable|url',
                'github_url' => 'nullable|url',
                'portfolio_url' => 'nullable|url',
                'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
                'years_of_experience' => 'nullable|integer|min:0',
                'expected_salary' => 'nullable|numeric|min:0',
                'is_available' => 'nullable|boolean',
                'languages' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $request->except('cv_file');

            // Handle CV file upload
            if ($request->hasFile('cv_file')) {
                $file = $request->file('cv_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('cvs', $fileName, 'public');
                $data['cv_file'] = $filePath;

                // Delete old CV if exists
                $oldProfile = EmployeeProfile::where('user_id', $user->id)->first();
                if ($oldProfile && $oldProfile->cv_file) {
                    Storage::disk('public')->delete($oldProfile->cv_file);
                }
            }

            $profile = EmployeeProfile::updateOrCreate(
                ['user_id' => $user->id],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البروفايل بنجاح',
                'data' => ['profile' => $profile]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ البروفايل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get authenticated user profile
    public function me()
    {
        Log::info('ProfileController@me called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'employee') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للموظفين فقط'
                ], 403);
            }

            $profile = EmployeeProfile::with(['skills', 'education', 'experiences'])
                ->where('user_id', $user->id)
                ->first();

            if (!$profile) {
                // Create empty profile
                $profile = EmployeeProfile::create(['user_id' => $user->id]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $profile,
                    'completeness' => $profile->profile_completeness
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البروفايل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add skill to profile
    public function addSkill(Request $request)
    {
        Log::info('ProfileController@addSkill called', ['user_id' => auth()->id(), 'skill_id' => $request->skill_id]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنشاء البروفايل أولاً'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'skill_id' => 'required|exists:skills,id',
                'level' => 'required|in:beginner,intermediate,advanced,expert',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check if skill already exists
            if ($profile->skills()->where('skill_id', $request->skill_id)->exists()) {
                // Update level
                $profile->skills()->updateExistingPivot($request->skill_id, [
                    'level' => $request->level
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث مستوى المهارة بنجاح'
                ], 200);
            }

            $profile->skills()->attach($request->skill_id, [
                'level' => $request->level
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المهارة بنجاح',
                'data' => ['skills' => $profile->skills]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المهارة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Remove skill from profile
    public function removeSkill($skillId)
    {
        Log::info('ProfileController@removeSkill called', ['user_id' => auth()->id(), 'skillId' => $skillId]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'البروفايل غير موجود'
                ], 404);
            }

            $profile->skills()->detach($skillId);

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

    // Add education
    public function addEducation(Request $request)
    {
        Log::info('ProfileController@addEducation called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنشاء البروفايل أولاً'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'degree' => 'required|string|max:255',
                'field_of_study' => 'required|string|max:255',
                'institution' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'is_current' => 'boolean',
                'description' => 'nullable|string',
                'grade' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $education = $profile->education()->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة التعليم بنجاح',
                'data' => ['education' => $education]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة التعليم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update education
    public function updateEducation(Request $request, $id)
    {
        Log::info('ProfileController@updateEducation called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            $education = Education::where('id', $id)
                ->where('employee_profile_id', $profile->id)
                ->first();

            if (!$education) {
                return response()->json([
                    'success' => false,
                    'message' => 'التعليم غير موجود'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'degree' => 'sometimes|string|max:255',
                'field_of_study' => 'sometimes|string|max:255',
                'institution' => 'sometimes|string|max:255',
                'location' => 'nullable|string|max:255',
                'start_date' => 'sometimes|date',
                'end_date' => 'nullable|date|after:start_date',
                'is_current' => 'boolean',
                'description' => 'nullable|string',
                'grade' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $education->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التعليم بنجاح',
                'data' => ['education' => $education]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث التعليم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete education
    public function deleteEducation($id)
    {
        Log::info('ProfileController@deleteEducation called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            $education = Education::where('id', $id)
                ->where('employee_profile_id', $profile->id)
                ->first();

            if (!$education) {
                return response()->json([
                    'success' => false,
                    'message' => 'التعليم غير موجود'
                ], 404);
            }

            $education->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التعليم بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التعليم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add experience
    public function addExperience(Request $request)
    {
        Log::info('ProfileController@addExperience called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنشاء البروفايل أولاً'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'job_title' => 'required|string|max:255',
                'company_name' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'is_current' => 'boolean',
                'description' => 'nullable|string',
                'achievements' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $experience = $profile->experiences()->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الخبرة بنجاح',
                'data' => ['experience' => $experience]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الخبرة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update experience
    public function updateExperience(Request $request, $id)
    {
        Log::info('ProfileController@updateExperience called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            $experience = Experience::where('id', $id)
                ->where('employee_profile_id', $profile->id)
                ->first();

            if (!$experience) {
                return response()->json([
                    'success' => false,
                    'message' => 'الخبرة غير موجودة'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'job_title' => 'sometimes|string|max:255',
                'company_name' => 'sometimes|string|max:255',
                'location' => 'nullable|string|max:255',
                'start_date' => 'sometimes|date',
                'end_date' => 'nullable|date|after:start_date',
                'is_current' => 'boolean',
                'description' => 'nullable|string',
                'achievements' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $experience->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الخبرة بنجاح',
                'data' => ['experience' => $experience]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الخبرة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete experience
    public function deleteExperience($id)
    {
        Log::info('ProfileController@deleteExperience called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $profile = EmployeeProfile::where('user_id', $user->id)->first();

            $experience = Experience::where('id', $id)
                ->where('employee_profile_id', $profile->id)
                ->first();

            if (!$experience) {
                return response()->json([
                    'success' => false,
                    'message' => 'الخبرة غير موجودة'
                ], 404);
            }

            $experience->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الخبرة بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الخبرة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}