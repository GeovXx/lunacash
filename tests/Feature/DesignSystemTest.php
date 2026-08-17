<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_the_design_system_page(): void
    {
        $this->get('/design-system')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_the_design_system_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/design-system')->assertOk();
    }

    public function test_welcome_page_renders_successfully(): void
    {
        $this->get('/')->assertOk();
    }
}
