<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeAccountController;
use App\Http\Controllers\EmployeeInfoController;
use App\Http\Controllers\EmployeeWorkLogController;
use App\Http\Controllers\ServiceScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (no auth required)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum token required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::prefix('auth')->group(function () {
        Route::get('me',              [AuthController::class, 'me']);
        Route::post('logout',         [AuthController::class, 'logout']);
        Route::post('logout-all',     [AuthController::class, 'logoutAll']);
        Route::post('change-password',[AuthController::class, 'changePassword']);
    });

    // --- Employee Accounts (admin-level) ---
    Route::apiResource('employee-accounts', EmployeeAccountController::class);

    // --- Employee Info ---
    Route::apiResource('employee-infos', EmployeeInfoController::class);

    // --- Clients ---
    Route::apiResource('clients', ClientController::class);

    // --- Branches (nested under clients for creation, flat for read/update/delete) ---
    Route::get('clients/{client}/branches', [BranchController::class, 'byClient']);
    Route::apiResource('branches', BranchController::class);

    // --- Service Schedules ---
    Route::get('service-schedules/by-employee/{employeeId}', [ServiceScheduleController::class, 'byEmployee']);
    Route::get('service-schedules/by-branch/{branchId}',    [ServiceScheduleController::class, 'byBranch']);
    Route::patch('service-schedules/{id}/status',           [ServiceScheduleController::class, 'updateStatus']);
    Route::apiResource('service-schedules', ServiceScheduleController::class);

    // --- Employee Work Logs ---
    Route::get('work-logs/by-employee/{employeeId}',        [EmployeeWorkLogController::class, 'byEmployee']);
    Route::get('work-logs/by-schedule/{scheduleId}',        [EmployeeWorkLogController::class, 'bySchedule']);
    Route::patch('work-logs/{id}/approve',                  [EmployeeWorkLogController::class, 'approve']);
    Route::patch('work-logs/{id}/reject',                   [EmployeeWorkLogController::class, 'reject']);
    Route::apiResource('work-logs', EmployeeWorkLogController::class);
});
