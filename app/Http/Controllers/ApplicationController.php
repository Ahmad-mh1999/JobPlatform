<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ApplicationController extends Controller
{
    // Get all applications for a job (company only)
    public function index($jobId)
    {
        Log::info('ApplicationController@index called', ['user_id' => auth()->id(), 'jobId' => $jobId]);
        try {
            $user = auth()->user();

            if ($user->role == 'employee') {
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

            $job = Job::where('id', $jobId)->where('company_id', $company->id)->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'الوظيفة غير موجودة أو لا تملك صلاحية الوصول'
                ], 404);
            }

            $applications = Application::where('job_id', $jobId)
                                     ->with(['employeeProfile.user', 'job'])
                                     ->orderBy('created_at', 'desc')
                                     ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $applications
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلبات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Apply for a job
    public function store(Request $request)
    {
        Log::info('ApplicationController@store called', ['user_id' => auth()->id(), 'job_id' => $request->job_id]);
        try {
            $user = auth()->user();

            if ($user->role !== 'employee') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للموظفين فقط'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'job_id' => 'required|exists:jobs,id',
                'cover_letter' => 'nullable|string',
                'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check if employee profile exists
            $employeeProfile = EmployeeProfile::where('user_id', $user->id)->first();

            if (!$employeeProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إنشاء البروفايل أولاً'
                ], 404);
            }

            // Check if job exists and is published
            $job = Job::where('id', $request->job_id)->where('status', 'published')->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'الوظيفة غير متاحة للتقديم'
                ], 404);
            }

            // Check if already applied
            $existingApplication = Application::where('job_id', $request->job_id)
                                            ->where('employee_profile_id', $employeeProfile->id)
                                            ->first();

            if ($existingApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'لقد تقدمت لهذه الوظيفة مسبقاً'
                ], 409);
            }

            $data = [
                'job_id' => $request->job_id,
                'employee_profile_id' => $employeeProfile->id,
                'cover_letter' => $request->cover_letter,
            ];

            // Handle CV file upload
            if ($request->hasFile('cv_file')) {
                $file = $request->file('cv_file');
                $fileName = time() . '_cv_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('cvs', $fileName, 'public');
                $data['cv_file'] = $filePath;
            }

            $application = Application::create($data);

            return response()->json([
                'success' => true,
                'message' => 'تم التقديم للوظيفة بنجاح',
                'data' => ['application' => $application->load(['employeeProfile.user', 'job.company'])]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التقديم للوظيفة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get application by ID
    public function show($id)
    {
        Log::info('ApplicationController@show called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $application = Application::with(['employeeProfile.user', 'job.company'])->find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود'
                ], 404);
            }

            // Check permissions
            $hasPermission = false;

            if ($user->role === 'employee' && $application->employeeProfile->user_id === $user->id) {
                $hasPermission = true;
            } elseif ($user->role === 'company') {
                $company = \App\Models\Company::where('user_id', $user->id)->first();
                if ($company && $application->job->company_id === $company->id) {
                    $hasPermission = true;
                }
            } elseif ($user->role === 'admin') {
                $hasPermission = true;
            }

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك صلاحية الوصول لهذا الطلب'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => ['application' => $application]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الطلب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update application status (company only)
    public function update(Request $request, $id)
    {
        Log::info('ApplicationController@update called', ['user_id' => auth()->id(), 'id' => $id]);
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

            $application = Application::where('id', $id)
                                    ->whereHas('job', function($query) use ($company) {
                                        $query->where('company_id', $company->id);
                                    })
                                    ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود أو لا تملك صلاحية التعديل'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,reviewed,shortlisted,interviewed,accepted,rejected',
                'notes' => 'nullable|string',
                'match_score' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $application->update($request->only(['status', 'notes', 'match_score']));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب بنجاح',
                'data' => ['application' => $application]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الطلب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete application
    public function destroy($id)
    {
        Log::info('ApplicationController@destroy called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $application = Application::find($id);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب غير موجود'
                ], 404);
            }

            // Check permissions
            $hasPermission = false;

            if ($user->role === 'employee' && $application->employeeProfile->user_id === $user->id) {
                $hasPermission = true;
            } elseif ($user->role === 'company') {
                $company = \App\Models\Company::where('user_id', $user->id)->first();
                if ($company && $application->job->company_id === $company->id) {
                    $hasPermission = true;
                }
            } elseif ($user->role === 'admin') {
                $hasPermission = true;
            }

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك صلاحية حذف هذا الطلب'
                ], 403);
            }

            // Delete CV file if exists
            if ($application->cv_file) {
                Storage::disk('public')->delete($application->cv_file);
            }

            $application->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الطلب بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الطلب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get my applications (employee only)
    public function getMyApplications()
    {
        Log::info('ApplicationController@getMyApplications called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'employee') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الخدمة متاحة للموظفين فقط'
                ], 403);
            }

            $employeeProfile = EmployeeProfile::where('user_id', $user->id)->first();

            if (!$employeeProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'البروفايل غير موجود'
                ], 404);
            }

            $applications = Application::where('employee_profile_id', $employeeProfile->id)
                                     ->with(['job.company'])
                                     ->orderBy('created_at', 'desc')
                                     ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $applications
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب طلباتك',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
