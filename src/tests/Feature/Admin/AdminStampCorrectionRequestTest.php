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
     * 【ID15】管理者は修正申請一覧を確認できる
     */
    /** @test */
    public function admin_can_view_correction_request_list(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance($user);

        $request = $this->createCorrectionRequest($attendance, $user, [
            'note' => '修正申請テスト',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('修正申請テスト');
        $response->assertSee((string) $request->id);
    }

    /**
     * 【ID15】管理者は修正申請詳細を確認できる
     */
    /** @test */
    public function admin_can_view_correction_request_detail(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザーA',
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance($user);

        $request = $this->createCorrectionRequest($attendance, $user, [
            'requested_clock_in_at' => Carbon::parse('2026-05-02 10:00:00'),
            'requested_clock_out_at' => Carbon::parse('2026-05-02 19:00:00'),
            'note' => '詳細確認テスト',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/stamp_correction_request/detail/{$request->id}");

        $response->assertStatus(200);
        $response->assertSee('一般ユーザーA');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('詳細確認テスト');
    }

    /**
     * 【ID15】管理者は修正申請を承認でき、勤怠情報が更新される
     */
    /** @test */
    public function admin_can_approve_request_and_attendance_is_updated(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance($user);

        $request = $this->createCorrectionRequest($attendance, $user, [
            'requested_clock_in_at' => Carbon::parse('2026-05-02 10:00:00'),
            'requested_clock_out_at' => Carbon::parse('2026-05-02 19:00:00'),
            'note' => '承認テスト',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/stamp_correction_request/approve/{$request->id}");

        $response->assertStatus(302);

        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $request->id,
            'status' => 1,
            'approved_by' => $admin->id,
        ]);

        $attendance->refresh();

        $this->assertEquals('10:00:00', Carbon::parse($attendance->clock_in_at)->format('H:i:s'));
        $this->assertEquals('19:00:00', Carbon::parse($attendance->clock_out_at)->format('H:i:s'));
        $this->assertSame('承認テスト', $attendance->note);
    }

    /**
     * 【ID15】一般ユーザーは管理者の修正申請画面にアクセスできない
     */
    /** @test */
    public function normal_user_cannot_access_admin_correction_request_pages(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/stamp_correction_request/list')
            ->assertStatus(403);
    }

    private function createAttendance(User $user): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-02',
            'clock_in_at' => Carbon::parse('2026-05-02 09:00:00'),
            'clock_out_at' => Carbon::parse('2026-05-02 18:00:00'),
            'note' => '元の備考',
        ]);
    }

    private function createCorrectionRequest(Attendance $attendance, User $user, array $overrides = []): StampCorrectionRequest
    {
        return StampCorrectionRequest::create(array_merge([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0,
            'requested_work_date' => '2026-05-02',
            'requested_clock_in_at' => Carbon::parse('2026-05-02 09:30:00'),
            'requested_clock_out_at' => Carbon::parse('2026-05-02 18:30:00'),
            'note' => '修正申請の備考',
            'approved_by' => null,
            'approved_at' => null,
        ], $overrides));
    }
}