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
     * 【ID13】勤怠詳細画面の内容が選択した勤怠情報と一致している
     */
    /** @test */
    public function admin_can_open_attendance_detail_page_and_see_selected_data(): void
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

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '元の備考',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $date->copy()->setTime(12, 0),
            'break_end_at' => $date->copy()->setTime(13, 0),
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('一般ユーザーA');
        $response->assertSee('2026');
        $response->assertSee('5');
        $response->assertSee('2');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('元の備考');
    }

    /**
     * 【ID13】出勤時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    /** @test */
    public function error_is_displayed_when_clock_in_is_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance();

        $payload = $this->validPayload([
            'clock_in_at' => '19:00',
            'clock_out_at' => '18:00',
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", $payload);

        $response->assertRedirect("/admin/attendance/{$attendance->id}");
        $this->assertSessionHasErrorMessage('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 【ID13】休憩開始時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    /** @test */
    public function error_is_displayed_when_break_start_is_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance();

        $payload = $this->validPayload([
            'breaks' => [
                ['break_start_at' => '19:00', 'break_end_at' => '19:30'],
            ],
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", $payload);

        $response->assertRedirect("/admin/attendance/{$attendance->id}");
        $this->assertSessionHasErrorMessage('休憩時間が勤務時間外です');
    }

    /**
     * 【ID13】休憩終了時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    /** @test */
    public function error_is_displayed_when_break_end_is_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance();

        $payload = $this->validPayload([
            'breaks' => [
                ['break_start_at' => '17:00', 'break_end_at' => '19:00'],
            ],
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", $payload);

        $response->assertRedirect("/admin/attendance/{$attendance->id}");
        $this->assertSessionHasErrorMessage('休憩時間が勤務時間外です');
    }

    /**
     * 【ID13】備考欄が未入力の場合、エラーメッセージが表示される
     */
    /** @test */
    public function note_is_required_when_admin_updates_attendance(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance();

        $payload = $this->validPayload([
            'note' => '',
        ]);

        $response = $this->actingAs($admin)
            ->from("/admin/attendance/{$attendance->id}")
            ->post("/admin/attendance/{$attendance->id}", $payload);

        $response->assertRedirect("/admin/attendance/{$attendance->id}");
        $this->assertSessionHasErrorMessage('備考を記入してください');
    }

    /**
     * 【ID13】正しい入力なら勤怠情報が更新される
     */
    /** @test */
    public function admin_can_update_attendance_and_is_redirected_back_to_detail(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $attendance = $this->createAttendance();

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 5, 2, 12, 0, 0),
            'break_end_at' => Carbon::create(2026, 5, 2, 13, 0, 0),
        ]);

        $payload = $this->validPayload([
            'clock_in_at' => '09:30',
            'clock_out_at' => '18:30',
            'breaks' => [
                ['break_start_at' => '12:15', 'break_end_at' => '13:15'],
            ],
            'note' => '更新後の備考',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/attendance/{$attendance->id}", $payload);

        $response->assertStatus(302);
        $response->assertRedirect("/admin/attendance/{$attendance->id}");

        $attendance->refresh();

        $this->assertStringContainsString('09:30', (string) $attendance->clock_in_at);
        $this->assertStringContainsString('18:30', (string) $attendance->clock_out_at);
        $this->assertSame('更新後の備考', $attendance->note);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-05-02 12:15:00',
            'break_end_at' => '2026-05-02 13:15:00',
        ]);
    }

    private function createAttendance(): Attendance
    {
        $user = User::factory()->create([
            'name' => '一般ユーザーA',
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '元の備考',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'work_year' => '2026',
            'work_md' => '5/2',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                ['break_start_at' => '12:00', 'break_end_at' => '13:00'],
            ],
            'note' => 'テスト備考',
        ], $overrides);
    }

    private function assertSessionHasErrorMessage(string $message): void
    {
        $errors = session('errors');

        $this->assertNotNull($errors);
        $this->assertTrue(
            collect($errors->all())->contains($message),
            "Failed asserting that validation errors contain: {$message}"
        );
    }
}