<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID4】日時取得機能
     * 仕様書：「現在の日時」が画面に表示される
     * - 日付（例）YYYY年M月D日(ddd)
     * - 時刻（例）HH:mm
     *
     * ※表示形式は AttendanceController@index のUIに合わせる
     */
    /** @test */
    public function datetime_is_displayed_in_ui_format(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        // テストが日付ブレしないよう固定
        $fixed = Carbon::create(2026, 5, 2, 9, 15, 0);
        Carbon::setTestNow($fixed);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // ✅ ここはUI表示に合わせて
        $expectedDate = $fixed->isoFormat('YYYY年M月D日(ddd)');
        $expectedTime = $fixed->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);

        Carbon::setTestNow();
    }

    /**
     * 【ID5】ステータス確認（勤務外）
     * 仕様書：勤怠が無い日 → 「勤務外」
     */
    /** @test */
    public function status_is_working_outside_when_no_attendance_today(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 5, 2, 9, 0, 0));

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    /**
     * 【ID5】ステータス確認（出勤中）
     * 仕様書：出勤済・退勤未 → 「出勤中」
     */
    /** @test */
    public function status_is_working_when_clocked_in_and_not_clocked_out(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $today = Carbon::create(2026, 5, 2, 9, 0, 0);
        Carbon::setTestNow($today);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in_at' => $today->copy()->setTime(9, 0, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /**
     * 【ID5】ステータス確認（休憩中）
     * 仕様書：休憩開始済・休憩終了未 → 「休憩中」
     */
    /** @test */
    public function status_is_on_break_when_break_started_and_not_ended(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $today = Carbon::create(2026, 5, 2, 10, 0, 0);
        Carbon::setTestNow($today);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in_at' => $today->copy()->setTime(9, 0, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        // ✅ あなたのモデル名は AttendanceBreak
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $today->copy()->setTime(10, 0, 0),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    /**
     * 【ID5】ステータス確認（退勤済）
     * 仕様書：退勤済 → 「退勤済」
     */
    /** @test */
    public function status_is_clocked_out_when_clocked_out(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $today = Carbon::create(2026, 5, 2, 18, 0, 0);
        Carbon::setTestNow($today);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in_at' => $today->copy()->setTime(9, 0, 0),
            'clock_out_at' => $today->copy()->setTime(18, 0, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Attendance/ClockStatusTest.php
*/