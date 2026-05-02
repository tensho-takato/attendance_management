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

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in_at' => $now,
        ]);

        $page = $this->actingAs($user)->get('/attendance');

        $page->assertStatus(200);
        $page->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /**
     * 【ID6】出勤は一日一回のみできる
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

        $before = Attendance::where('user_id', $user->id)
            ->where('work_date', $today->toDateString())
            ->count();

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $response->assertStatus(302);

        $after = Attendance::where('user_id', $user->id)
            ->where('work_date', $today->toDateString())
            ->count();

        $this->assertSame($before, $after);

        Carbon::setTestNow();
    }

    /**
     * 【ID7】休憩入ボタンが正しく機能する
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

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today->toDateString())
            ->first();

        $this->assertNotNull($attendance);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => $today,
        ]);

        $page = $this->actingAs($user)->get('/attendance');

        $page->assertStatus(200);
        $page->assertSee('休憩中');

        Carbon::setTestNow();
    }

    /**
     * 【ID7】休憩戻ボタンが正しく機能する
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
     * 【ID8】退勤ボタンが正しく機能する
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