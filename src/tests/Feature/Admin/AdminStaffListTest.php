<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【ID14】ユーザー情報取得機能（管理者）
     * 仕様書：管理者がスタッフ一覧画面で「全一般ユーザーの氏名・メールアドレス」を確認できる
     *
     * 期待挙動：
     * - /admin/staff/list が 200
     * - 一般ユーザー（role=0）の name / email が表示される
     * - 管理者ユーザー（role=1）が一覧に混ざらない（仕様どおりに絞っている場合）
     */
    /** @test */
    public function admin_can_view_staff_list_and_see_users_name_and_email(): void
    {
        // ✅ 管理者（ログインできるように認証済）
        $admin = User::factory()->create([
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        // ✅ 一般ユーザー（一覧に出る想定）
        $userA = User::factory()->create([
            'role' => 0,
            'name' => '一般ユーザーA',
            'email' => 'userA@example.com',
            'email_verified_at' => now(),
        ]);

        $userB = User::factory()->create([
            'role' => 0,
            'name' => '一般ユーザーB',
            'email' => 'userB@example.com',
            'email_verified_at' => now(),
        ]);

        // ✅ 一覧に出ない想定の管理者（フィルタしているなら）
        $admin2 = User::factory()->create([
            'role' => 1,
            'name' => '管理者2',
            'email' => 'admin2@example.com',
            'email_verified_at' => now(),
        ]);

        // ✅ 仕様書どおり：/admin/staff/list
        $response = $this->actingAs($admin)->get('/admin/staff/list');
        $response->assertStatus(200);

        // ✅ 仕様書どおり：一般ユーザーの「氏名」「メール」が表示される
        $response->assertSee('一般ユーザーA');
        $response->assertSee('userA@example.com');
        $response->assertSee('一般ユーザーB');
        $response->assertSee('userB@example.com');

        /**
         * ✅（任意だけど強い）管理者は一覧に出ないことを確認
         * ※あなたの StaffController@index が role=ROLE_USER のみ取得してる前提
         */
        $response->assertDontSee('管理者2');
        $response->assertDontSee('admin2@example.com');
    }

    /**
     * 【ID14】アクセス制御（管理者以外は見れない）
     * 仕様書：管理者機能は管理者のみ
     *
     * ※あなたの方針は「admin middlewareは作らず、Controllerで abort_unless(isAdmin)」
     * なので、一般ユーザーでアクセスすると 403 になるのが自然
     */
    /** @test */
    public function normal_user_cannot_access_staff_list(): void
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin/staff/list');

        // ✅ controller の abort_unless(...) が効いていれば 403
        $response->assertStatus(403);
    }
}

/*
【このファイルのテスト実行コマンド】
php artisan config:clear
php artisan test tests/Feature/Admin/AdminStaffListTest.php
*/