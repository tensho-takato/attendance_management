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
     * 【ID12】勤怠一覧情報取得（管理者）
     * 仕様書：管理者で /admin/attendance/list を開ける
     */
    /** @test */
    public function admin_can_open_admin_attendance_list(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertStatus(200);
    }

    /**
     * 【ID12】初期表示：当日（今日）の日付が表示される
     * - dateクエリなし → controllerが今日を採用する仕様
     */
    /** @test */
    public function today_is_displayed_when_date_is_not_specified(): void
    {
        $admin = User::factory()->create(['role' => 1, 'email_verified_at' => now()]);

        $today = Carbon::create(2026, 5, 2, 9, 0, 0);
        Carbon::setTestNow($today);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        $response->assertStatus(200);

        // Blade: {{ $currentDate->format('Y年n月j日') }} が出る想定
        $response->assertSee('2026年5月2日');

        Carbon::setTestNow();
    }

    /**
     * 【ID12】前日/翌日ボタンで日付が変わる
     * - Bladeのリンクが route('admin.attendance.list', ['date' => ...]) で生成される想定
     */
    /** @test */
    public function prev_and_next_links_exist(): void
    {
        $admin = User::factory()->create(['role' => 1, 'email_verified_at' => now()]);

        $today = Carbon::create(2026, 5, 2, 9, 0, 0);
        Carbon::setTestNow($today);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        $response->assertStatus(200);

        // 前日・翌日のクエリが含まれるリンクが存在すること（HTMLのhref確認）
        $response->assertSee('date=2026-05-01');
        $response->assertSee('date=2026-05-03');

        Carbon::setTestNow();
    }

    /**
     * 【ID12】その日に なされた全ユーザーの勤怠情報が確認できる
     * - 一般ユーザーAは勤怠あり → 出勤/退勤が表示
     * - 一般ユーザーBは勤怠なし → 名前だけ表示（勤怠は空欄）
     */
    /** @test */
    public function admin_can_see_all_users_rows_and_attendance_if_exists(): void
    {
        $admin = User::factory()->create(['role' => 1, 'email_verified_at' => now()]);

        $userA = User::factory()->create(['name' => 'ユーザーA', 'role' => 0]);
        $userB = User::factory()->create(['name' => 'ユーザーB', 'role' => 0]);

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

        // 全ユーザーの名前が表示される
        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');

        // 勤怠があるユーザーは時刻が表示される（表示形式がHH:MMならこれでOK）
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 勤怠がないユーザーの空欄まではHTML構造依存なので、ここでは「名前が出る」までに留める
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Admin/AdminAttendanceListTest.php
*/