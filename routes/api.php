<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CourseController;
use App\Http\Controllers\API\LeadController;
use App\Http\Controllers\API\AdmissionProfileController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\SchoolController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/chat', [ChatController::class, 'chat']);
//Route::get('/course-list',[CourseController::class,'getCourse']);
// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::prefix('auth')->group(function () {
    // Leads
    Route::apiResource('leads', LeadController::class);
//    Route::patch('/leads/{id}/status', [LeadController::class, 'updateStatus']);

    // Courses
//    Route::apiResource('courses', CourseController::class);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::put('/me/password', [AuthController::class, 'changePassword']);
    });
});


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    //course
    Route::get('/course-list',[CourseController::class,'getCourse']);
    Route::post('/course-create',[CourseController::class,'store']);
    // Chuẩn hoá endpoint courses (REST-style)
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::match(['put', 'patch'], '/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

    //lead
    Route::get('/leads-list',[LeadController::class,'getLead']);
    // lead-create dùng group tích hợp riêng (sanctum OR integration token)
    Route::post('/leads/{id}/status',[LeadController::class,'updateStatus']);
    Route::post('/leads/{id}/assign',[LeadController::class,'assign']);
    Route::get('/leads/{id}/show',[LeadController::class,'show']);
    Route::post('/leads-import',[LeadController::class,'import']);
    Route::post('/leads/{leadId}/admission-profile',[AdmissionProfileController::class,'createFromLead']);
    // admission-profiles/from-lead dùng group tích hợp riêng (sanctum OR integration token)
    Route::get('/admission-profiles', [AdmissionProfileController::class, 'index']);
    Route::get('/admission-profiles/{id}', [AdmissionProfileController::class, 'show']);
    Route::match(['put', 'post'], '/admission-profiles/{id}', [AdmissionProfileController::class, 'update']);
    Route::post('/admission-profiles/{id}/verify', [AdmissionProfileController::class, 'verify']);
    //task
    Route::get('/tasks-list', [TaskController::class, 'index']);
    Route::get('/tasks-stats', [TaskController::class, 'stats']);
    Route::get('/tasks-pending-count', [TaskController::class, 'pendingCount']);
    Route::post('/tasks-create',[TaskController::class,'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::post('/tasks/{id}/done', [TaskController::class, 'markDone']);
    // user management - only admin can CRUD
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('settings', [SettingController::class, 'index']);
        Route::post('settings', [SettingController::class, 'update']);
        Route::post('settings/test-mail', [SettingController::class, 'testMail']);
        Route::get('leads/distribute/preview', [LeadController::class, 'distributePreview']);
        Route::post('leads/distribute/execute', [LeadController::class, 'distributeExecute']);
    });

    // Super admin only - quản lý trường và admin từng trường
    Route::middleware('super_admin')->prefix('super-admin')->group(function () {
        Route::get('schools', [SchoolController::class, 'index']);
        Route::post('schools', [SchoolController::class, 'store']);
        Route::put('schools/{id}', [SchoolController::class, 'update']);
        Route::delete('schools/{id}', [SchoolController::class, 'destroy']);
        Route::post('schools/{schoolId}/admins', [SchoolController::class, 'createAdminForSchool']);
    });

    // List schools (Bearer token) - phục vụ tích hợp
    // /schools dùng group tích hợp riêng (sanctum OR integration token)
});

// Integration endpoints: cho phép dùng Sanctum token hoặc integration bearer token (không cần login)
Route::middleware('sanctum.or.integration')->group(function () {
    Route::post('/lead-create', [LeadController::class, 'store']);
    Route::post('/admission-profiles/from-lead', [AdmissionProfileController::class, 'createFromLeadBody']);
    // Danh sách trường phục vụ tích hợp (không bắt buộc super_admin)
    Route::get('/schools', [SchoolController::class, 'listForIntegration']);
});

// External API (X-API-KEY) - path khác để tránh trùng với CRM (auth), tránh 401 khi user đã login
Route::middleware('static.api.key')->prefix('external')->group(function () {
    Route::get('/admission-profiles', [AdmissionProfileController::class, 'index']);
    Route::post('/admission-profiles/{leadId}', [AdmissionProfileController::class, 'create']);
});