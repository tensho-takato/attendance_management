<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID3】管理者ログイン画面が表示される
     */
    /** @test */
    public function admin_login_screen_can_be_rendered(): void
    {
        $this->get('/admin/login')
            ->assertStatus(200);
    }

    /**
     * 【ID3】メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function email_is_required_for_admin_login(): void
    {
        $response = $this->from('/admin/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
            'login_type' => 'admin',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertGuest();
    }

    /**
     * 【ID3】パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function password_is_required_for_admin_login(): void
    {
        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
            'login_type' => 'admin',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertGuest();
    }

    /**
     * 【ID3】登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    /** @test */
    public function admin_cannot_login_with_invalid_credentials(): void
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $admin->forceFill(['email_verified_at' => now()])->save();

        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
            'login_type' => 'admin',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }

    /**
     * 【ID3】認証済みの管理者はログイン後 /admin/attendance/list に遷移する
     */
    /** @test */
    public function verified_admin_can_login_and_redirect_to_admin_attendance_list(): void
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $admin->forceFill(['email_verified_at' => now()])->save();

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'login_type' => 'admin',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/attendance/list');
    }
}