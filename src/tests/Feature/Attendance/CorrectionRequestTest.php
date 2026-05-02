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
     * 【ID11】出勤時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    /** @test */
    public function error_is_displayed_when_clock_in_is_after_clock_out(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $attendance = $this->createAttendance($user);

        $payload = $this->validPayload([
            'clock_in_at' => '19:00',
            'clock_out_at' => '18:00',
        ]);

        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendance->id}")
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload);

        $response->assertRedirect("/attendance/detail/{$attendance->id}");
        $this->assertSessionHasErrorMessage('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 【ID11】休憩開始時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    /** @test */
    public function error_is_displayed_when_break_start_is_after_clock_out(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $attendance = $this->createAttendance($user);

        $payload = $this->validPayload([
            'breaks' => [
                ['break_start_at' => '19:00', 'break_end_at' => '19:30'],
            ],
        ]);

        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendance->id}")
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload);

        $response->assertRedirect("/attendance/detail/{$attendance->id}");
        $this->assertSessionHasErrorMessage('休憩時間が勤務時間外です');
    }

    /**
     * 【ID11】休憩終了時間が退勤時間より後の場合、エラーメッセージが表示される
     */
    /** @test */
    public function error_is_displayed_when_break_end_is_after_clock_out(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $attendance = $this->createAttendance($user);

        $payload = $this->validPayload([
            'breaks' => [
                ['break_start_at' => '17:00', 'break_end_at' => '19:00'],
            ],
        ]);

        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendance->id}")
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload);

        $response->assertRedirect("/attendance/detail/{$attendance->id}");
        $this->assertSessionHasErrorMessage('休憩時間が勤務時間外です');
    }

    /**
     * 【ID11】備考欄が未入力の場合、エラーメッセージが表示される
     */
    /** @test */
    public function note_is_required_for_correction_request(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $attendance = $this->createAttendance($user);

        $payload = $this->validPayload([
            'note' => '',
        ]);

        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendance->id}")
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload);

        $response->assertRedirect("/attendance/detail/{$attendance->id}");
        $this->assertSessionHasErrorMessage('備考を記入してください');
    }

    /**
     * 【ID11】修正申請処理が実行される
     */
    /** @test */
    public function user_can_create_correction_request_and_attendance_is_not_updated(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '元の備考',
        ]);

        $payload = $this->validPayload([
            'clock_in_at' => '09:30',
            'clock_out_at' => '18:30',
            'note' => '修正申請の備考',
        ]);

        $response = $this->actingAs($user)
            ->post("/stamp_correction_request/create/{$attendance->id}", $payload);

        $response->assertStatus(302);
        $response->assertRedirect(route('scr.list', ['tab' => 'pending']));

        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0,
            'note' => '修正申請の備考',
        ]);

        $attendance->refresh();

        $this->assertSame('元の備考', $attendance->note);
    }

    /**
     * 【ID11】承認待ち申請が既にある場合、二重申請できない
     */
    /** @test */
    public function user_cannot_create_duplicate_pending_request_for_same_attendance(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $date = Carbon::create(2026, 5, 2, 9, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 0,
            'requested_work_date' => $date->toDateString(),
            'requested_clock_in_at' => $date->copy()->setTime(9, 0),
            'requested_clock_out_at' => $date->copy()->setTime(18, 0),
            'note' => '既存申請',
        ]);

        $before = StampCorrectionRequest::where('attendance_id', $attendance->id)->count();

        $response = $this->actingAs($user)
            ->post("/stamp_correction_request/create/{$attendance->id}", $this->validPayload([
                'note' => '二重申請',
            ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));

        $after = StampCorrectionRequest::where('attendance_id', $attendance->id)->count();

        $this->assertSame($before, $after);
    }

    /**
     * 【ID11】他人の勤怠に対して申請できない
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

        $attendance = Attendance::create([
            'user_id' => $other->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => null,
            'note' => null,
        ]);

        $this->actingAs($user)
            ->post("/stamp_correction_request/create/{$attendance->id}", $this->validPayload([
                'note' => '不正',
            ]))
            ->assertStatus(403);
    }

    private function createAttendance(User $user): Attendance
    {
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
            'note' => '修正申請の備考',
            'breaks' => [
                ['break_start_at' => '12:00', 'break_end_at' => '13:00'],
            ],
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