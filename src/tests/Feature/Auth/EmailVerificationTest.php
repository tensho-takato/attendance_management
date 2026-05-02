<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID16】新規会員登録後、メール認証誘導画面へ遷移する
     */
    /** @test */
    public function after_register_user_is_redirected_to_email_verify(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'verify_user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');

        $this->assertAuthenticated();
    }

    /**
     * 【ID16】未認証ユーザーは認証が必要な画面へアクセスできない
     */
    /** @test */
    public function unverified_user_is_redirected_to_email_verify_when_accessing_verified_pages(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => null,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertRedirect('/email/verify');
    }

    /**
     * 【ID16】認証済みユーザーは勤怠ページへアクセスできる
     */
    /** @test */
    public function verified_user_can_access_attendance_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
    }
}