<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminSCRController;

Route::get('/admin/login', function () {
    return view('admin.login');
})->middleware('guest')->name('admin.login');

/*
|--------------------------------------------------------------------------
| 一般ユーザー側
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])->name('attendance.breakIn');
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('attendance.breakOut');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');

    Route::post('/stamp_correction_request/create/{attendance}', [StampCorrectionRequestController::class, 'store'])
        ->name('scr.store');
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'list'])
        ->name('scr.list');
});

/*
|--------------------------------------------------------------------------
| 管理者側（/admin）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified'])
    ->group(function () {

        // ===== 勤怠一覧 =====
        Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])
            ->name('attendance.list');

        // ✅ スタッフ別勤怠一覧（/admin/attendance/staff/{id}）
        // ★これを /attendance/{id} より上に置く
        Route::get('/attendance/staff/{user}', [AdminStaffController::class, 'monthly'])
            ->name('attendance.staff');

        // ✅ 勤怠詳細（/admin/attendance/{id}）
        // ★数字だけに制限して衝突防止
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
            ->whereNumber('id')
            ->name('attendance.show');

        Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
            ->whereNumber('id')
            ->name('attendance.update');

        // ===== スタッフ一覧 =====
        Route::get('/staff/list', [AdminStaffController::class, 'index'])
            ->name('staff.list');

        // （CSVは仕様書に無いけど、残すならこのままでOK）
        Route::get('/staff/{user}/attendance/csv', [AdminStaffController::class, 'csv'])
            ->name('staff.attendance.csv');

        // ===== 修正申請 =====
        Route::get('/stamp_correction_request/list', [AdminSCRController::class, 'index'])
            ->name('scr.list');
        Route::get('/stamp_correction_request/detail/{id}', [AdminSCRController::class, 'show'])
            ->name('scr.show');
        Route::post('/stamp_correction_request/approve/{id}', [AdminSCRController::class, 'approve'])
            ->name('scr.approve');
    });