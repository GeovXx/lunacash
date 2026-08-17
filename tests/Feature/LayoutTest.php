<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_page_renders_the_sidebar_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/home')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Design System')
            ->assertSee(route('logout'), false);
    }

    public function test_authenticated_page_shows_the_current_user_in_the_topbar(): void
    {
        $user = User::factory()->create(['name' => 'Ana Souza']);

        $this->actingAs($user)->get('/home')
            ->assertOk()
            ->assertSee('Ana Souza');
    }

    public function test_logout_still_works_from_the_new_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')
            ->assertRedirect();

        $this->assertGuest();
    }
}
