<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    // Get all jobs with filters
    public function index(Request $request)
    {
        Log::info('JobController@index called', $request->all());
        try {
            $query = Job::with(['company', 'skills']);

            // Apply filters
            if ($request->has('location') && $request->location) {
                $query->where('location', 'like', '%' . $request->location . '%');
            }

            if ($request->has('job_type') && $request->job_type) {
                $query->where('job_type', $request->job_type);
            }

            if ($request->has('work_mode') && $request->work_mode) {
                $query->where('work_mode', $request->work_mode);
            }

            if ($request->has('experience_level') && $request->experience_level) {
                $query->where('experience_level', $request->experience_level);
            }

            if ($request->has('company_id') && $request->company_id) {
                $query->where('company_id', $request->company_id);
            }

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            $jobs = $query->where('status', 'published')
                         ->orderBy('created_at', 'desc')
                         ->paginate(10);

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

    // Create job
    public function store(Request $request)
    {
        Log::info('JobController@store called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'company' || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للشركات فقط'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'requirements' => 'nullable|string',
                'responsibilities' => 'nullable|string',
                'location' => 'required|string|max:255',
                'job_type' => 'required|in:full-time,part-time,contract,freelance,internship',
                'work_mode' => 'required|in:onsite,remote,hybrid',
                'experience_level' => 'required|in:entry,mid,senior,lead',
                'salary_min' => 'nullable|numeric|min:0',
                'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
                'salary_currency' => 'nullable|string|size:3',
                'salary_period' => 'required_with:salary_min|in:hour,day,month,year',
                'vacancies' => 'nullable|integer|min:1',
                'deadline' => 'nullable|date|after:today',
                'skills' => 'nullable|array',
                'skills.*.id' => 'exists:skills,id',
                'skills.*.is_required' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check if company exists for this user
            $company = \App\Models\Company::where('user_id', $user->id)->first();
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنشاء الشركة أولاً'
                ], 404);
            }

            $data = $request->except('skills');
            $data['company_id'] = $company->id;

            $job = Job::create($data);

            // Attach skills if provided
            if ($request->has('skills') && is_array($request->skills)) {
                foreach ($request->skills as $skillData) {
                    $job->skills()->attach($skillData['id'], [
                        'is_required' => $skillData['is_required'] ?? true
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الوظيفة بنجاح',
                'data' => ['job' => $job->load('skills')]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الوظيفة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get job by ID
    public function show($id)
    {
        Log::info('JobController@show called', ['id' => $id]);
        try {
            $job = Job::with(['company', 'skills', 'applications'])->find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'الوظيفة غير موجودة'
                ], 404);
            }

            // Increment view count
            $job->increment('views_count');

            return response()->json([
                'success' => true,
                'data' => ['job' => $job]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الوظيفة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update job
    public function update(Request $request, $id)
    {
        Log::info('JobController@update called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $company = \App\Models\Company::where('user_id', $user->id)->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة'
                ], 404);
            }

            $job = Job::where('id', $id)->where('company_id', $company->id)->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'الوظيفة غير موجودة أو لا تملك صلاحية التعديل'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'requirements' => 'nullable|string',
                'responsibilities' => 'nullable|string',
                'location' => 'sometimes|string|max:255',
                'job_type' => 'sometimes|in:full-time,part-time,contract,freelance,internship',
                'work_mode' => 'sometimes|in:onsite,remote,hybrid',
                'experience_level' => 'sometimes|in:entry,mid,senior,lead',
                'salary_min' => 'nullable|numeric|min:0',
                'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
                'salary_currency' => 'nullable|string|size:3',
                'salary_period' => 'nullable|in:hour,day,month,year',
                'vacancies' => 'nullable|integer|min:1',
                'deadline' => 'nullable|date|after:today',
                'status' => 'sometimes|in:draft,published,closed,filled',
                'skills' => 'nullable|array',
                'skills.*.id' => 'exists:skills,id',
                'skills.*.is_required' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $request->except('skills');
            $job->update($data);

            // Update skills if provided
            if ($request->has('skills') && is_array($request->skills)) {
                $job->skills()->detach(); // Remove existing skills
                foreach ($request->skills as $skillData) {
                    $job->skills()->attach($skillData['id'], [
                        'is_required' => $skillData['is_required'] ?? true
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الوظيفة بنجاح',
                'data' => ['job' => $job->load('skills')]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الوظيفة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete job
    public function destroy($id)
    {
        Log::info('JobController@destroy called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $company = \App\Models\Company::where('user_id', $user->id)->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة'
                ], 404);
            }

            $job = Job::where('id', $id)->where('company_id', $company->id)->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'الوظيفة غير موجودة أو لا تملك صلاحية الحذف'
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

    // Get jobs by company
    public function getByCompany()
    {
        Log::info('JobController@getByCompany called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'company') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للشركات فقط'
                ], 403);
            }

            $company = \App\Models\Company::where('user_id', $user->id)->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة'
                ], 404);
            }

            $jobs = Job::where('company_id', $company->id)
                      ->with('skills')
                      ->orderBy('created_at', 'desc')
                      ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $jobs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب وظائف الشركة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
