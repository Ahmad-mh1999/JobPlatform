<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    // Get all companies
    public function index()
    {
        Log::info('CompanyController@index called');
        try {
            $companies = Company::with('user')->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $companies
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الشركات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create company
    public function store(Request $request)
    {
        Log::info('CompanyController@store called', ['user_id' => auth()->id()]);
        try {
            $user = auth()->user();

            if ($user->role !== 'company' && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإنشاء شركة'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'category' => 'nullable|string|max:255',
                'website' => 'nullable|url',
                'location' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'founded_year' => 'nullable|integer|min:1800|max:' . (date('Y') + 1),
                'social_links' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $request->except(['logo', 'cover_image']);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $logoName = time() . '_logo_' . $logoFile->getClientOriginalName();
                $logoPath = $logoFile->storeAs('logos', $logoName, 'public');
                $data['logo'] = $logoPath;
            }

            // Handle cover image upload
            if ($request->hasFile('cover_image')) {
                $coverFile = $request->file('cover_image');
                $coverName = time() . '_cover_' . $coverFile->getClientOriginalName();
                $coverPath = $coverFile->storeAs('covers', $coverName, 'public');
                $data['cover_image'] = $coverPath;
            }

            $company = Company::create(array_merge($data, ['user_id' => $user->id]));

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الشركة بنجاح',
                'data' => ['company' => $company]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الشركة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get company by ID
    public function show($id)
    {
        Log::info('CompanyController@show called', ['id' => $id]);
        try {
            $company = Company::with(['user', 'jobs'])->find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['company' => $company]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الشركة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update company
    public function update(Request $request, $id)
    {
        Log::info('CompanyController@update called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة'
                ], 404);
            }

            // Check if user is admin or company owner
            if ($user->role !== 'admin' && $company->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك صلاحية التعديل على هذه الشركة'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'company_name' => 'sometimes|string|max:255',
                'category' => 'nullable|string|max:255',
                'website' => 'nullable|url',
                'location' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'founded_year' => 'nullable|integer|min:1800|max:' . (date('Y') + 1),
                'social_links' => 'nullable|json',
                'is_verified' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $request->except(['logo', 'cover_image']);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $logoName = time() . '_logo_' . $logoFile->getClientOriginalName();
                $logoPath = $logoFile->storeAs('logos', $logoName, 'public');
                $data['logo'] = $logoPath;

                // Delete old logo
                if ($company->logo) {
                    Storage::disk('public')->delete($company->logo);
                }
            }

            // Handle cover image upload
            if ($request->hasFile('cover_image')) {
                $coverFile = $request->file('cover_image');
                $coverName = time() . '_cover_' . $coverFile->getClientOriginalName();
                $coverPath = $coverFile->storeAs('covers', $coverName, 'public');
                $data['cover_image'] = $coverPath;

                // Delete old cover
                if ($company->cover_image) {
                    Storage::disk('public')->delete($company->cover_image);
                }
            }

            $company->update($data);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الشركة بنجاح',
                'data' => ['company' => $company]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الشركة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete company
    public function destroy($id)
    {
        Log::info('CompanyController@destroy called', ['user_id' => auth()->id(), 'id' => $id]);
        try {
            $user = auth()->user();
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة'
                ], 404);
            }

            // Check if user is admin or company owner
            if ($user->role !== 'admin' && $company->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'الشركة غير موجودة أو لا تملك صلاحية الحذف'
                ], 403);
            }
            

            // Delete associated files
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            if ($company->cover_image) {
                Storage::disk('public')->delete($company->cover_image);
            }

            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الشركة بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الشركة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
