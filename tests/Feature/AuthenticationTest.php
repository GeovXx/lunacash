<?php

namespace Tests\Feature;

use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_guest_can_view_the_register_page(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_a_user_can_register(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Ana Teste')
            ->set('email', 'ana@example.com')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->call('register')
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
    }

    public function test_a_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_cannot_login_with_incorrect_credentials(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_away_from_protected_route(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/home')->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/home');
    }

    public function test_a_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_login_rate_limiting(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('authenticate');
        }

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors('email');
    }
}
