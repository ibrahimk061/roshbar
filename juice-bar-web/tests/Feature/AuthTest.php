<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/pos')->assertRedirect('/login');
        $this->get('/admin/products')->assertRedirect('/login');
        $this->get('/orders')->assertRedirect('/login');
        $this->postJson('/orders', [])->assertUnauthorized();
        $this->get('/admin/login')->assertRedirect('/login');
    }

    public function test_staff_can_sign_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'cash@roshbar.com', 'password' => 'secret-pass', 'role' => 'cashier']);

        $this->post('/login', ['email' => 'cash@roshbar.com', 'password' => 'secret-pass'])->assertRedirect('/pos');
        $this->assertAuthenticatedAs($user);
        $this->get('/login')->assertRedirect('/pos');

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_and_inactive_users_are_rejected(): void
    {
        User::factory()->create(['email' => 'a@roshbar.com', 'password' => 'secret-pass']);
        User::factory()->create(['email' => 'off@roshbar.com', 'password' => 'secret-pass', 'is_active' => false]);

        $this->post('/login', ['email' => 'a@roshbar.com', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'off@roshbar.com', 'password' => 'secret-pass'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_blocked_after_five_failures(): void
    {
        User::factory()->create(['email' => 'a@roshbar.com', 'password' => 'secret-pass']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@roshbar.com', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'a@roshbar.com', 'password' => 'secret-pass'])
            ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 15 minute(s).']);
        $this->assertGuest();
    }

    public function test_cashier_cannot_open_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cashier']));
        $this->get('/pos')->assertOk();
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
    }

    public function test_only_owner_manages_staff(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']));
        $this->get('/admin/users')->assertForbidden();

        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->get('/admin/users')->assertOk();
        $this->post('/admin/users', ['name' => 'New Cashier', 'email' => 'nc@roshbar.com', 'role' => 'cashier', 'password' => 'longpassword'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'nc@roshbar.com', 'role' => 'cashier']);
    }

    public function test_last_owner_cannot_be_demoted(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)
            ->put("/admin/users/{$owner->id}", ['name' => $owner->name, 'email' => $owner->email, 'role' => 'cashier', 'is_active' => '1'])
            ->assertSessionHasErrors('role');
        $this->assertSame('owner', $owner->fresh()->role);
    }
}
