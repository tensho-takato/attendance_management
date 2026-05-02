<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID14】管理者がスタッフ一覧画面で全一般ユーザーの氏名・メールアドレスを確認できる
     */
    /** @test */
    public function admin_can_view_staff_list_and_see_users_name_and_email(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => '一般ユーザーA',
            'email' => 'userA@example.com',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => '一般ユーザーB',
            'email' => 'userB@example.com',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => '管理者2',
            'email' => 'admin2@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);

        $response->assertSee('一般ユーザーA');
        $response->assertSee('userA@example.com');
        $response->assertSee('一般ユーザーB');
        $response->assertSee('userB@example.com');

        $response->assertDontSee('管理者2');
        $response->assertDontSee('admin2@example.com');
    }

    /**
     * 【ID14】一般ユーザーはスタッフ一覧画面にアクセスできない
     */
    /** @test */
    public function normal_user_cannot_access_staff_list(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin/staff/list');

        $response->assertStatus(403);
    }
}