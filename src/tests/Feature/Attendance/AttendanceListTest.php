<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID9】勤怠一覧情報取得：自分の勤怠情報が表示される
     */
    /** @test */
    public function user_can_see_own_attendances_on_list(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $other = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => null,
        ]);

        Attendance::create([
            'user_id' => $other->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(8, 0),
            'clock_out_at' => $date->copy()->setTime(17, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');
    }

    /**
     * 【ID9】勤怠一覧画面に遷移した際、現在の月が表示される
     */
    /** @test */
    public function current_month_is_displayed_when_open_list(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 5, 2, 9, 0, 0));

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026');
        $response->assertSee('5');

        Carbon::setTestNow();
    }

    /**
     * 【ID9】前月ボタンを押すと前月の勤怠情報が表示される
     */
    /** @test */
    public function previous_month_attendances_are_displayed(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $previousMonth = Carbon::create(2026, 4, 10, 9, 0, 0);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $previousMonth->toDateString(),
            'clock_in_at' => $previousMonth->copy()->setTime(9, 0),
            'clock_out_at' => $previousMonth->copy()->setTime(18, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-04');

        $response->assertStatus(200);
        $response->assertSee('2026');
        $response->assertSee('4');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 【ID9】翌月ボタンを押すと翌月の勤怠情報が表示される
     */
    /** @test */
    public function next_month_attendances_are_displayed(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $nextMonth = Carbon::create(2026, 6, 10, 9, 0, 0);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $nextMonth->toDateString(),
            'clock_in_at' => $nextMonth->copy()->setTime(9, 0),
            'clock_out_at' => $nextMonth->copy()->setTime(18, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-06');

        $response->assertStatus(200);
        $response->assertSee('2026');
        $response->assertSee('6');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 【ID9】詳細ボタンを押すと勤怠詳細画面に遷移する
     */
    /** @test */
    public function user_can_access_attendance_detail_from_list(): void
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

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
    }
}