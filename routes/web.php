<?php

use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\DailyAttendanceController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\PlatformHomeController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TodayAttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/platform', [PlatformHomeController::class, 'index'])->name('platform.home');
        Route::post('/platform/companies', [PlatformHomeController::class, 'store'])->name('platform.companies.store');
    });

    Route::middleware('role:company_admin,hr,employee')->group(function () {
        Route::post('/profile/photo', [ProfilePhotoController::class, 'store'])->name('profile.photo.store');
    });

    Route::middleware('role:employee')->group(function () {
        Route::get('/punch', [TodayAttendanceController::class, 'index'])->name('attendance.today');
        Route::post('/punch/punches', [TodayAttendanceController::class, 'store'])->name('attendance.punches.store');
    });

    Route::middleware('role:company_admin,hr,super_admin')->group(function () {
        Route::get('/attendance', [DailyAttendanceController::class, 'page'])->name('attendance.page');
        Route::get('/reports', [AttendanceReportController::class, 'page'])->name('reports.page');
        Route::get('/branches', [BranchController::class, 'page'])->name('branches.page');
        Route::get('/schedules', [ScheduleController::class, 'page'])->name('schedules.page');
        Route::get('/employees', [EmployeeController::class, 'page'])->name('employees.page');

        Route::get('/admin/attendance', [DailyAttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::get('/admin/reports', [AttendanceReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/admin/branches', [BranchController::class, 'index'])->name('admin.branches.index');
        Route::get('/admin/schedules', [ScheduleController::class, 'index'])->name('admin.schedules.index');
        Route::get('/admin/employees', [EmployeeController::class, 'index'])->name('admin.employees.index');
    });

    Route::middleware('role:company_admin,hr')->group(function () {
        Route::post('/admin/branches', [BranchController::class, 'store'])->name('admin.branches.store');
        Route::put('/admin/branches/{branch}', [BranchController::class, 'update'])->name('admin.branches.update');
        Route::post('/admin/schedules', [ScheduleController::class, 'store'])->name('admin.schedules.store');
        Route::put('/admin/schedules/{schedule}', [ScheduleController::class, 'update'])->name('admin.schedules.update');
        Route::post('/admin/employees', [EmployeeController::class, 'store'])->name('admin.employees.store');
        Route::put('/admin/employees/{employeeProfile}', [EmployeeController::class, 'update'])->name('admin.employees.update');
        Route::post('/admin/employees/{employeeProfile}/schedule', [EmployeeController::class, 'assignSchedule'])
            ->name('admin.employees.schedule');
    });
});
