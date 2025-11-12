<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AiController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('test', [AuthController::class, 'test']);


Route::middleware([JwtMiddleware::class])->group(function () {
    Route::get('getUser',[AuthController::class,'getUser']);
    Route::get('getOneUser/{id}',[AuthController::class,'getOneUser']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::delete('delete/{id}', [AuthController::class, 'delete']);
});

Route::middleware([JwtMiddleware::class])->group(function () {
    // Profile routes
    Route::get('/profiles/{userId}', [ProfileController::class, 'show']);
    Route::post('/profiles', [ProfileController::class, 'createOrUpdate']);
    Route::get('profiles/me', [ProfileController::class, 'me']);
    Route::post('/profiles/skills', [ProfileController::class, 'addSkill']);
    Route::delete('/profiles/skills/{skillId}', [ProfileController::class, 'removeSkill']);
    Route::post('/profiles/education', [ProfileController::class, 'addEducation']);
    Route::put('/profiles/education/{id}', [ProfileController::class, 'updateEducation']);
    Route::delete('/profiles/education/{id}', [ProfileController::class, 'deleteEducation']);
    Route::post('/profiles/experiences', [ProfileController::class, 'addExperience']);
    Route::put('/profiles/experiences/{id}', [ProfileController::class, 'updateExperience']);
    Route::delete('/profiles/experiences/{id}', [ProfileController::class, 'deleteExperience']);

    // Company routes
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);

    // Job routes
    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
    Route::get('/jobs/company/my', [JobController::class, 'getByCompany']);

    // Application routes
    Route::get('/applications/job/{jobId}', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::put('/applications/{id}', [ApplicationController::class, 'update']);
    Route::delete('/applications/{id}', [ApplicationController::class, 'destroy']);
    Route::get('/applications/my', [ApplicationController::class, 'getMyApplications']);

    // Suggestion routes
    Route::get('/suggestions', [SuggestionController::class, 'index']);
    Route::post('/suggestions', [SuggestionController::class, 'store']);
    Route::get('/suggestions/{id}', [SuggestionController::class, 'show']);
    Route::put('/suggestions/{id}', [SuggestionController::class, 'update']);
    Route::delete('/suggestions/{id}', [SuggestionController::class, 'destroy']);
    Route::get('/suggestions/my', [SuggestionController::class, 'getMySuggestions']);
});

Route::middleware(['jwt', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Admin routes
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/users', [AdminController::class, 'getUsers']);
    Route::put('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
    Route::put('/admin/companies/{id}/verify', [AdminController::class, 'verifyCompany']);
    Route::get('/admin/jobs', [AdminController::class, 'getJobs']);
    Route::delete('/admin/jobs/{id}', [AdminController::class, 'deleteJob']);
    Route::get('/admin/posts', [AdminController::class, 'getPosts']);
    Route::delete('/admin/posts/{id}', [AdminController::class, 'deletePost']);
    Route::get('admin/skills',[SkillController::class, 'index']);
    Route::post('admin/skills',[SkillController::class, 'store']);
    Route::get('admin/skills/{id}',[SkillController::class, 'show']);
    Route::put('admin/skills/{id}',[SkillController::class, 'update']);
    Route::delete('admin/skills/{id}',[SkillController::class, 'destroy']);
});

// AI Suggestions Routes
Route::middleware([JwtMiddleware::class])->group(function () {
    Route::get('suggestions/jobs', [SuggestionController::class, 'getRecommendedJobs']);
    Route::post('suggestions/cover-letter/{jobId}', [SuggestionController::class, 'generateCoverLetter']);
    Route::post('suggestions/analyze-cv', [SuggestionController::class, 'analyzeCv']);
});
Route::middleware([JwtMiddleware::class])->group(function () {
    Route::post('/ai/suggest-jobs', [AiController::class, 'suggestJobs']);
    Route::post('/ai/generate-cover-letter', [AiController::class, 'generateCoverLetter']);
    Route::post('/ai/generate-cv', [AiController::class, 'generateCv']);
});