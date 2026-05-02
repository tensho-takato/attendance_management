<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID1】会員登録画面（一般ユーザー）
     * 仕様書：/register が表示できる
     */
    /** @test */
    public function register_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertStatus(200);
    }

    /**
     * 【ID1】会員登録（一般ユーザー）
     * 仕様書：登録直後は未認証 → /email/verify に飛ぶ
     *
     * ※Fortifyの RegisterResponse / LoginResponse の仕様に合わせる
     */
    /** @test */
    public function user_can_register_and_is_redirected_to_email_verify_when_unverified(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 仕様書どおり：未認証ユーザーは /email/verify
        $response->assertRedirect('/email/verify');

        // 登録後はログイン状態になっている（Fortifyの標準動作）
        $this->assertAuthenticated();
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test --filter=RegisterUserTest
*/