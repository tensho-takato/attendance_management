<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID11】勤怠詳細情報修正機能（一般ユーザー）
     * 仕様書：修正申請処理が実行される（＝申請レコードは作られる）
     *       ※承認前なので勤怠（Attendance）はこの時点では更新しない
     *
     * 対象URL（仕様書）：/stamp_correction_request/create/{attendance_id}
     * 期待：申請作成 → 申請一覧へ遷移（あなたの実装：scr.list?tab=pending）
     */
    /** @test */
    public function user_can_create_correction_request_and_attendance_is_not_updated(): void
    {
        // ✅ 仕様書：一般ユーザーでログインして操作する
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        // ✅ テスト日付（仕様書の「その日」の勤怠）
        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        // ✅ 事前に「その日の勤怠」が存在している前提（勤怠詳細の修正申請なので）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '元の備考',
        ]);

        // ✅ FormRequest（StampCorrectionRequestStoreRequest）の仕様に合わせたpayload
        // - work_year / work_md が必須
        // - work_date は withValidator() で合成されるので送らない
        $payload = [
            'work_year' => '2026',
            'work_md'   => '5/2',

            'clock_in_at'  => '09:30',
            'clock_out_at' => '18:30',

            'note' => '修正申請の備考',

            // breaks は array（仕様に合わせる）。勤務時間内で開始<終了にする
            'breaks' => [
                ['break_start_at' => '12:00', 'break_end_at' => '13:00'],
            ],
        ];

        $response = $this->actingAs($user)
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload);

        // ✅ 仕様：申請作成後は一覧へ（あなたの実装：scr.list かつ tab=pending）
        $response->assertStatus(302);
        $response->assertRedirect(route('scr.list', ['tab' => 'pending']));

        // ✅ 仕様：申請が作られている（DBにレコードが入る）
        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0, // 承認待ち
            'note' => '修正申請の備考',
        ]);

        // ✅ 仕様：承認前なので勤怠は更新されない
        $attendance->refresh();
        $this->assertSame('元の備考', $attendance->note);
    }

    /**
     * 【ID11】（仕様に入れている場合）二重申請防止
     * 仕様：承認待ち（status=0）が既にあるなら新規作成しない
     *
     * あなたのController実装：
     * - 承認待ちがある場合 → attendance.detail に戻す
     */
    /** @test */
    public function user_cannot_create_duplicate_pending_request_for_same_attendance(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        // ✅ 申請対象の勤怠
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        // ✅ すでに承認待ち申請がある状態を作る
        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0, // 承認待ち
            'requested_work_date' => $date->toDateString(),
            'requested_clock_in_at' => $date->copy()->setTime(9, 0),
            'requested_clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '既存申請',
        ]);

        $before = StampCorrectionRequest::where('attendance_id', $attendance->id)->count();

        // ✅ FormRequest仕様に合わせる（work_year/work_md）
        $payload = [
            'work_year' => '2026',
            'work_md'   => '5/2',

            'clock_in_at'  => '09:30',
            'clock_out_at' => '18:30',
            'note' => '二重申請',
            'breaks' => [],
        ];

        $response = $this->actingAs($user)
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload);

        // ✅ 仕様（あなたの実装）：二重申請は作らず勤怠詳細へ戻す
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));

        $after = StampCorrectionRequest::where('attendance_id', $attendance->id)->count();
        $this->assertSame($before, $after);
    }

    /**
     * 【ID11】他人の勤怠に対して申請できない
     * 仕様：不正アクセスは403（controller の abort_unless）
     *
     * 注意：
     * - 403を確認したいので、payload はバリデーションに通る形にしておく
     *   （バリデーション落ちると302になってしまう）
     */
    /** @test */
    public function user_cannot_create_request_for_other_users_attendance(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $other = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        // ✅ 他人の勤怠（申請対象）
        $attendance = Attendance::create([
            'user_id' => $other->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        // ✅ バリデーションを通すpayload（work_year/work_md）
        $payload = [
            'work_year' => '2026',
            'work_md'   => '5/2',

            'clock_in_at'  => '09:30',
            'clock_out_at' => '18:30',
            'note' => '不正',
            'breaks' => [],
        ];

        $this->actingAs($user)
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload)
            ->assertStatus(403);
    }
}

/*
【このファイルのテスト実行コマンド（毎回これ）】
docker compose exec php bash -lc "php artisan config:clear && php artisan test tests/Feature/Attendance/CorrectionRequestTest.php"
*/