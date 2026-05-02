<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID12】管理者で勤怠一覧画面を開ける
     */
    /** @test */
    public function admin_can_open_admin_attendance_list(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertStatus(200);
    }

    /**
     * 【ID12】初期表示で当日の日付が表示される
     */
    /** @test */
    public function today_is_displayed_when_date_is_not_specified(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $today = Carbon::create(2026, 5, 2, 9, 0, 0);
        Carbon::setTestNow($today);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026年5月2日');

        Carbon::setTestNow();
    }

    /**
     * 【ID12】前日ボタンで前日の日付が表示される
     */
    /** @test */
    public function previous_day_attendance_list_is_displayed(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-05-01');

        $response->assertStatus(200);
        $response->assertSee('2026年5月1日');
    }

    /**
     * 【ID12】翌日ボタンで翌日の日付が表示される
     */
    /** @test */
    public function next_day_attendance_list_is_displayed(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-05-03');

        $response->assertStatus(200);
        $response->assertSee('2026年5月3日');
    }

    /**
     * 【ID12】前日・翌日のリンクが存在する
     */
    /** @test */
    public function previous_and_next_links_exist(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $today = Carbon::create(2026, 5, 2, 9, 0, 0);
        Carbon::setTestNow($today);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('date=2026-05-01');
        $response->assertSee('date=2026-05-03');

        Carbon::setTestNow();
    }

    /**
     * 【ID12】その日に勤務した一般ユーザーの勤怠情報が表示される
     */
    /** @test */
    public function admin_can_see_all_general_users_rows_and_attendance_if_exists(): void
    {
        $admin = User::factory()->create([
            'name' => '管理者',
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $userA = User::factory()->create([
            'name' => 'ユーザーA',
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $userB = User::factory()->create([
            'name' => 'ユーザーB',
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        Attendance::create([
            'user_id' => $userA->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-05-02');

        $response->assertStatus(200);

        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');
        $response->assertDontSee('管理者');

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}