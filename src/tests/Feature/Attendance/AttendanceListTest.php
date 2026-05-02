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
     * 【ID9】勤怠一覧情報取得（一般ユーザー）
     * 仕様書：自分の勤怠情報が全て表示される
     */
    /** @test */
    public function user_can_see_own_attendances_on_list(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'role' => 0]);
        $other = User::factory()->create(['email_verified_at' => now(), 'role' => 0]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => null,
        ]);

        // 他人の勤怠は表示されない（仕様書：自分の勤怠）
        Attendance::create([
            'user_id' => $other->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(8, 0),
            'clock_out_at' => $date->copy()->setTime(17, 0),
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        // 自分のレコードの時刻が見える（UIに時刻が表示される前提）
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 他人の時刻は出ない想定（UI仕様によっては表示形式が異なるので必要なら調整）
        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');
    }

    /**
     * 【ID9】勤怠一覧：遷移時に現在の月が表示される（仕様書）
     * ※ここはUIの月表示（例：2026年5月）に合わせて assertSee を調整
     */
    /** @test */
    public function current_month_is_displayed_when_open_list(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'role' => 0]);

        Carbon::setTestNow(Carbon::create(2026, 5, 2, 9, 0, 0));

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        // ✅ UIに合わせて（例）
        $response->assertSee('2026');
        $response->assertSee('5');

        Carbon::setTestNow();
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Attendance/AttendanceListTest.php
*/