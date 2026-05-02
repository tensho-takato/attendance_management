<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStampCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID15】勤怠情報修正機能（管理者）
     * 仕様書（要点）：
     * - 管理者は修正申請一覧を見れる（承認待ち/承認済み）
     * - 管理者は修正申請の詳細を見れる
     * - 管理者は申請を承認できる（承認すると勤怠情報が更新される）
     *
     * ここでは以下をテストする：
     * 1) 一覧が表示できる
     * 2) 詳細が表示できる
     * 3) 承認で「申請が承認済み」になり「勤怠が更新」される
     */

    /** @test */
    public function admin_can_view_correction_request_list(): void
    {
        // ✅ 管理者（認証済）
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        // ✅ 一般ユーザー（申請者）
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        // ✅ 勤怠（申請対象）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_at' => Carbon::today()->setTime(9, 0, 0),
            'clock_out_at' => Carbon::today()->setTime(18, 0, 0),
            'note' => null,
        ]);

        // ✅ 修正申請（承認待ち status=0 前提）
        $scr = StampCorrectionRequest::create([
            // ⚠️ ここはあなたのテーブル定義に合わせて
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0, // 承認待ち

            // 申請内容（あなたのERD/実装に合わせて）
            'requested_work_date' => $attendance->work_date,
            'requested_clock_in_at' => Carbon::today()->setTime(10, 0, 0),
            'requested_clock_out_at' => Carbon::today()->setTime(19, 0, 0),
            'note' => '修正申請テスト',

            'approved_by' => null,
            'approved_at' => null,
        ]);

        // ✅ 仕様書どおり：修正申請一覧（管理者）
        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);

        // ✅ 一覧に申請が出ていること（表示項目は画面に依存するので最低限の文言チェック）
        $response->assertSee('修正申請テスト');
        $response->assertSee((string)$scr->id);
    }

    /** @test */
    public function admin_can_view_correction_request_detail(): void
    {
        // ✅ 管理者
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        // ✅ 一般ユーザー
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_at' => Carbon::today()->setTime(9, 0, 0),
            'clock_out_at' => Carbon::today()->setTime(18, 0, 0),
            'note' => null,
        ]);

        $scr = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0,

            'requested_work_date' => $attendance->work_date,
            'requested_clock_in_at' => Carbon::today()->setTime(10, 0, 0),
            'requested_clock_out_at' => Carbon::today()->setTime(19, 0, 0),
            'note' => '詳細確認テスト',

            'approved_by' => null,
            'approved_at' => null,
        ]);

        // ✅ 仕様書どおり：詳細（管理者）
        $response = $this->actingAs($admin)->get("/admin/stamp_correction_request/detail/{$scr->id}");

        $response->assertStatus(200);

        // ✅ 詳細に申請内容が表示される想定
        $response->assertSee('詳細確認テスト');
    }

    /** @test */
    public function admin_can_approve_request_and_attendance_is_updated(): void
    {
        // ✅ 管理者
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        // ✅ 一般ユーザー
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $date = Carbon::today()->toDateString();

        // ✅ 元の勤怠
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in_at' => Carbon::parse("$date 09:00:00"),
            'clock_out_at' => Carbon::parse("$date 18:00:00"),
            'note' => null,
        ]);

        // ✅ 申請（承認後に勤怠がこの値に更新される想定）
        $scr = StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0, // 承認待ち

            'requested_work_date' => $date,
            'requested_clock_in_at' => Carbon::parse("$date 10:00:00"),
            'requested_clock_out_at' => Carbon::parse("$date 19:00:00"),
            'note' => '承認テスト',

            'approved_by' => null,
            'approved_at' => null,
        ]);

        // ✅ 仕様書どおり：承認処理（POST）
        $response = $this->actingAs($admin)->post("/admin/stamp_correction_request/approve/{$scr->id}");

        // ✅ 画面遷移は実装次第（一覧へ戻す/詳細へ戻す等）
        // とりあえず「302(リダイレクト)」を期待（あなたの実装に合わせて assertRedirect を確定させる）
        $response->assertStatus(302);

        // ✅ 申請が承認済みになっていること
        // ※ status=1 を「承認済み」にしてる前提（あなたの定義が違えばここを変更）
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $scr->id,
            'status' => 1,
            'approved_by' => $admin->id,
        ]);

        // ✅ 勤怠が更新されていること（申請内容で上書きされる想定）
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            // DBが datetime の場合は厳密一致がズレることがあるので、
            // もし落ちたら ->assertDatabaseHas を "clock_in_at" だけ別で確認する等に調整
        ]);

        // ここは “文字列一致” で見に行く（mysqlの保存形式に合わせる）
        $attendance->refresh();
        $this->assertEquals('10:00:00', Carbon::parse($attendance->clock_in_at)->format('H:i:s'));
        $this->assertEquals('19:00:00', Carbon::parse($attendance->clock_out_at)->format('H:i:s'));
    }

    /**
     * 【ID15】アクセス制御：一般ユーザーは管理者機能を触れない
     */
    /** @test */
    public function normal_user_cannot_access_admin_correction_request_pages(): void
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        // 一覧
        $this->actingAs($user)->get('/admin/stamp_correction_request/list')
            ->assertStatus(403);
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Admin/AdminStampCorrectionRequestTest.php
*/