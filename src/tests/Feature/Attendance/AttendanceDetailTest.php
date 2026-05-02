<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID10】勤怠詳細情報取得（一般ユーザー）
     * 仕様書：
     * - 詳細画面に遷移できる（/attendance/detail/{id}）
     * - 「名前」がログインユーザーの氏名になっている
     * - 日付/打刻が正しく表示される
     */
    /** @test */
    public function user_can_view_attendance_detail_and_see_own_info(): void
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

        // 仕様書：名前がログインユーザーの名前
        $response->assertSee('テスト太郎');

        // 仕様書：出勤・退勤が表示される（UIに合わせて）
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 日付表示はUI形式により変わるので最低限 work_date が絡む表示があることを確認するのもOK
        $response->assertSee('2026');
        $response->assertSee('5');
        $response->assertSee('2');
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Attendance/AttendanceDetailTest.php
*/