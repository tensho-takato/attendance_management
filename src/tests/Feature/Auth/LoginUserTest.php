<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    /** @test */
    public function verified_user_can_login_and_redirect_to_attendance(): void
    {
        $user = User::create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_USER,
        ]);

        // ✅ ここがポイント（fillable無視）
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'login_type' => 'user',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/attendance');
    }

    /** @test */
    public function unverified_user_redirects_to_email_verify_after_login(): void
    {
        $user = User::create([
            'name' => '未認証ユーザー',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_USER,
            // email_verified_at は入れない（未認証）
        ]);

        $response = $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
            'login_type' => 'user',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/email/verify');
    }
}