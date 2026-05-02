<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID10】勤怠詳細情報取得：名前がログインユーザーの氏名になっている
     */
    /** @test */
    public function user_name_is_displayed_on_attendance_detail(): void
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
    }

    /**
     * 【ID10】勤怠詳細情報取得：日付が選択した勤怠の日付になっている
     */
    /** @test */
    public function attendance_date_is_displayed_on_attendance_detail(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('2026');
        $response->assertSee('5');
        $response->assertSee('2');
    }

    /**
     * 【ID10】勤怠詳細情報取得：出勤・退勤時刻が表示される
     */
    /** @test */
    public function clock_in_and_clock_out_times_are_displayed_on_attendance_detail(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 【ID10】勤怠詳細情報取得：休憩時刻が表示される
     */
    /** @test */
    public function break_times_are_displayed_on_attendance_detail(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $date->copy()->setTime(12, 0),
            'break_end_at' => $date->copy()->setTime(13, 0),
        ]);

        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}