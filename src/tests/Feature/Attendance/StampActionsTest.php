<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampActionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID6】出勤機能：出勤ボタンが正しく機能する
     * - /attendance/clock-in を叩くと勤怠が作られ、clock_in_at が入る
     * - 画面上のステータスが「出勤中」になる（UI文言に依存）
     */
    /** @test */
    public function user_can_clock_in_once_and_status_becomes_working(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $now = Carbon::create(2026, 5, 2, 9, 0, 0);
        Carbon::setTestNow($now);

        $response = $this->actingAs($user)->post('/attendance/clock-in');
        $response->assertStatus(302);

        // DBに勤怠が作成されている（work_dateは今日）
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
        ]);

        // 画面に反映（仕様書：出勤ステータス）
        $page = $this->actingAs($user)->get('/attendance');
        $page->assertStatus(200);
        $page->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /**
     * 【ID6】出勤は一日一回のみできる
     * - 既に今日の勤怠がある状態で /clock-in しても増えない想定
     *
     * ※実装によっては「ボタン非表示」だけの場合もあるので、
     *   ここは DBレコード数が増えないことを確認しておくのが安全。
     */
    /** @test */
    public function user_cannot_clock_in_twice_in_a_day(): void
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
            'clock_in_at' => $today->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $before = Attendance::where('user_id', $user->id)->where('work_date', $today->toDateString())->count();

        $response = $this->actingAs($user)->post('/attendance/clock-in');
        $response->assertStatus(302);

        $after = Attendance::where('user_id', $user->id)->where('work_date', $today->toDateString())->count();

        $this->assertSame($before, $after);

        Carbon::setTestNow();
    }

    /**
     * 【ID7】休憩機能：休憩入ボタンが正しく機能する
     * - /attendance/break-in で breaks が作られ break_start_at が入る
     * - ステータスが「休憩中」になる
     */
    /** @test */
    public function user_can_break_in_and_status_becomes_on_break(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $today = Carbon::create(2026, 5, 2, 10, 0, 0);
        Carbon::setTestNow($today);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in_at' => $today->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-in');
        $response->assertStatus(302);

        $attendance = Attendance::where('user_id', $user->id)->where('work_date', $today->toDateString())->first();
        $this->assertNotNull($attendance);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
        ]);

        $page = $this->actingAs($user)->get('/attendance');
        $page->assertStatus(200);
        $page->assertSee('休憩中');

        Carbon::setTestNow();
    }

    /**
     * 【ID7】休憩戻ボタンが正しく機能する
     * - /attendance/break-out で break_end_at が入る
     * - ステータスが「出勤中」へ戻る
     */
    /** @test */
    public function user_can_break_out_and_status_returns_to_working(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $today = Carbon::create(2026, 5, 2, 11, 0, 0);
        Carbon::setTestNow($today);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in_at' => $today->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $today->copy()->setTime(10, 0),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance/break-out');
        $response->assertStatus(302);

        $break->refresh();
        $this->assertNotNull($break->break_end_at);

        $page = $this->actingAs($user)->get('/attendance');
        $page->assertStatus(200);
        $page->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /**
     * 【ID8】退勤機能：退勤ボタンが正しく機能する
     * - /attendance/clock-out で clock_out_at が入る
     * - ステータスが「退勤済」になる
     */
    /** @test */
    public function user_can_clock_out_and_status_becomes_clocked_out(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $today = Carbon::create(2026, 5, 2, 18, 0, 0);
        Carbon::setTestNow($today);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in_at' => $today->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $response = $this->actingAs($user)->post('/attendance/clock-out');
        $response->assertStatus(302);

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out_at);

        $page = $this->actingAs($user)->get('/attendance');
        $page->assertStatus(200);
        $page->assertSee('退勤済');

        Carbon::setTestNow();
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Attendance/StampActionsTest.php
*/