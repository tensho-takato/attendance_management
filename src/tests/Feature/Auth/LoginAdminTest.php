<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_login_screen_can_be_rendered(): void
    {
        $this->get('/admin/login')->assertStatus(200);
    }

    /** @test */
    public function verified_admin_can_login_and_redirect_to_admin_attendance_list(): void
    {
        // 管理者ユーザー作成（この時点ではemail_verified_atがfillableじゃないと入らない）
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        // ✅ fillableに関係なく強制的に認証済みにする（最重要）
        $admin->forceFill(['email_verified_at' => now()])->save();

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'login_type' => 'admin', // authenticateUsing 判定用
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/attendance/list');
    }
}