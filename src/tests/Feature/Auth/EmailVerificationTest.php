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
     * 【ID16】メール認証（登録直後）
     * 仕様書：新規会員登録後、メール認証誘導画面へ遷移する
     *
     * 期待挙動：
     * - POST /register → /email/verify にリダイレクト
     * - ただしログイン状態にはなっている（Fortifyの通常挙動）
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

        // ✅ 仕様書どおり
        $response->assertRedirect('/email/verify');

        // ✅ 登録直後はログイン済みになっている想定
        $this->assertAuthenticated();
    }

    /**
     * 【ID16】未認証ユーザーのアクセス制御
     * 仕様書：メール認証をしないでログインを試みた場合はメール認証誘導画面へ
     *
     * あなたのルート定義：/attendance は middleware(['auth','verified'])
     *
     * 期待挙動：
     * - 未認証ユーザーで /attendance へアクセス → /email/verify にリダイレクト
     */
    /** @test */
    public function unverified_user_is_redirected_to_email_verify_when_accessing_verified_pages(): void
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => null, // 未認証
            'password' => Hash::make('password123'),
        ]);

        // ✅ ログイン済みにして、verifiedページへアクセス
        $response = $this->actingAs($user)->get('/attendance');

        // ✅ verified ミドルウェアのデフォルト遷移
        $response->assertRedirect('/email/verify');
    }

    /**
     * 【ID16】認証済みユーザーは勤怠ページへアクセスできる
     * 期待挙動：
     * - 認証済みユーザーで /attendance → 200
     */
    /** @test */
    public function verified_user_can_access_attendance_page(): void
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(), // 認証済み
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Auth/EmailVerificationTest.php
*/