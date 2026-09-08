<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_hrd_can_login(): void
    {
        $hrd = User::factory()->create([
            'email' => 'hrd@example.com',
            'role' => 'hrd',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'hrd@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($hrd);
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_hrd_cannot_access_admin_dashboard(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd']);

        $response = $this->actingAs($hrd)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_hrd_can_access_hrd_dashboard(): void
    {
        $hrd = User::factory()->create(['role' => 'hrd']);

        $response = $this->actingAs($hrd)->get('/hrd/dashboard');
        $response->assertStatus(200);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
