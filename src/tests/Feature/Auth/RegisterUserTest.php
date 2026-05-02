<?php

namespace Tests\Feature\Auth;

use App\Models\User;
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
     * 【ID1】名前が未入力の場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function name_is_required_for_registration(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * 【ID1】メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function email_is_required_for_registration(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 【ID1】パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function password_must_be_at_least_8_characters_for_registration(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'user@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * 【ID1】パスワードと確認用パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function password_confirmation_must_match_for_registration(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * 【ID1】パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function password_is_required_for_registration(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 【ID1】フォームに内容が入力されていた場合、データが正常に保存される
     */
    /** @test */
    public function user_can_register_and_user_data_is_stored(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'user@example.com',
            'role' => User::ROLE_USER,
        ]);
    }
}