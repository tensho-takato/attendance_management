<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID13】勤怠詳細情報取得・修正機能（管理者）
     * 仕様書：管理者が勤怠詳細ページを開ける（/admin/attendance/{id}）
     *
     * チェック観点（仕様書）：
     * - 詳細画面の内容が選択した勤怠情報と一致している
     */
    /** @test */
    public function admin_can_open_attendance_detail_page_and_see_selected_data(): void
    {
        // 管理者ユーザー（認証済）
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        // 勤怠を持つ一般ユーザー
        $user = User::factory()->create([
            'name' => '一般ユーザーA',
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        // 対象の勤怠（管理者が見る対象）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '元の備考',
        ]);

        // 休憩（必要なら）
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $date->copy()->setTime(12, 0),
            'break_end_at' => $date->copy()->setTime(13, 0),
        ]);

        // ✅ 仕様書どおり：/admin/attendance/{id}
        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");
        $response->assertStatus(200);

        /**
         * Bladeの表示に合わせて確認（最低限）
         * - 名前（readonly）に userName が入る想定
         * - 出勤/退勤の time input に 09:00 / 18:00 が入る想定
         * - 備考が表示される想定
         */
        $response->assertSee('一般ユーザーA');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('元の備考');
    }

    /**
     * 【ID13】勤怠情報修正（管理者）
     * 仕様書：不正な入力の場合、バリデーションメッセージが表示される
     *
     * 代表例：
     * - 出勤時間が退勤時間より後 → エラー
     * - 休憩開始が退勤時間より後 → エラー
     * - 休憩終了が退勤時間より後 → エラー
     * - 備考未入力 → エラー（あなたの実装で必須なら）
     *
     * ※あなたの AdminAttendanceController@update の validation 文言に合わせて assertSee を調整
     */
    /** @test */
    public function admin_update_shows_validation_error_when_times_are_invalid(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザーA',
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '元の備考',
        ]);

        // ✅ 仕様書例：出勤 > 退勤（不正）
        $payload = [
            // あなたのフォームは年・月日で送る
            'work_year' => '2026',
            'work_md' => '5/2',

            // 出勤が退勤より後（不正）
            'clock_in_at' => '19:00',
            'clock_out_at' => '18:00',

            // 休憩（空でもOKなら空で）
            'breaks' => [
                ['break_start_at' => '', 'break_end_at' => ''],
                ['break_start_at' => '', 'break_end_at' => ''],
            ],

            // 備考（必須なら入れる / 必須じゃないなら空テストも可）
            'note' => 'テスト備考',
        ];

        $response = $this->actingAs($admin)
            ->post("/admin/attendance/{$attendance->id}", $payload);

        // バリデーション失敗 → 通常はリダイレクトで戻る
        $response->assertStatus(302);

        // エラー文言（あなたの実装の日本語に合わせて変更）
        // 例： '出勤時間が不適切な値です'
        $response->assertSessionHasErrors();
    }

    /**
     * 【ID13】勤怠情報修正（管理者）
     * 仕様書：正しい入力なら更新され、詳細画面に戻る（/admin/attendance/{id}）
     */
    /** @test */
    public function admin_can_update_attendance_and_is_redirected_back_to_detail(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザーA',
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '元の備考',
        ]);

        // ✅ 正しい更新値
        $payload = [
            'work_year' => '2026',
            'work_md' => '5/2',

            'clock_in_at' => '09:30',
            'clock_out_at' => '18:30',

            'breaks' => [
                ['break_start_at' => '12:00', 'break_end_at' => '13:00'],
                ['break_start_at' => '', 'break_end_at' => ''],
            ],

            'note' => '更新後の備考',
        ];

        $response = $this->actingAs($admin)
            ->post("/admin/attendance/{$attendance->id}", $payload);

        // ✅ 仕様書どおり：更新後は詳細へ戻る（あなたが確認してた redirect()->route('admin.attendance.show', ...)）
        $response->assertStatus(302);
        $response->assertRedirect("/admin/attendance/{$attendance->id}");

        // DB更新確認（カラム名はあなたのテーブルに合わせて）
        $attendance->refresh();

        // ここは AttendanceController/AdminAttendanceController の実装で保存形式が datetime のはず
        $this->assertStringContainsString('09:30', (string) $attendance->clock_in_at);
        $this->assertStringContainsString('18:30', (string) $attendance->clock_out_at);
        $this->assertSame('更新後の備考', $attendance->note);
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Admin/AdminAttendanceDetailTest.php
*/