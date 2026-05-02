<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID2】ログイン画面（一般ユーザー）
     */
    /** @test */
    public function login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertStatus(200);
    }

    /**
     * 【ID2】メールアドレスが未入力の場合、バリデーションエラーになる
     */
    /** @test */
    public function email_is_required_for_user_login(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
            'login_type' => 'user',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * 【ID2】パスワードが未入力の場合、バリデーションエラーになる
     */
    /** @test */
    public function password_is_required_for_user_login(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => '',
            'login_type' => 'user',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    /**
     * 【ID2】登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_USER,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
            'login_type' => 'user',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }

    /**
     * 【ID2】認証済みの一般ユーザーはログイン後 /attendance に遷移する
     */
    /** @test */
    public function verified_user_can_login_and_redirect_to_attendance(): void
    {
        $user = User::create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_USER,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'login_type' => 'user',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/attendance');
    }

    /**
     * 【ID2】未認証の一般ユーザーはログイン後 /email/verify に遷移する
     */
    /** @test */
    public function unverified_user_redirects_to_email_verify_after_login(): void
    {
        $user = User::create([
            'name' => '未認証ユーザー',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_USER,
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