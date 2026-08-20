<?php

namespace Tests\Feature\Livewire;

use App\Livewire\NotificationBell;
use App\Models\Account;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_mounts_and_loads_counts()
    {
        $user = User::factory()->create();

        $service = app(NotificationService::class);
        $service->createPersistent($user, 'info', 'Test', []);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 1)
            ->assertSet('hasEphemeral', false)
            ->assertSet('isOpen', false);
    }

    public function test_it_shows_ephemeral_indicator()
    {
        $user = User::factory()->create();
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $user->id]), fn($m) => $m->save());

        DB::table('financial_obligations')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'account_id' => $account->id,
            'direction' => 'payable',
            'title' => 'Test',
            'amount' => 100,
            'due_date' => Carbon::yesterday(),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 0)
            ->assertSet('hasEphemeral', true);
    }

    public function test_toggle_dropdown_loads_notifications()
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $service->createPersistent($user, 'info', 'Message Here', []);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertCount('notifications', 0) // Closed initially
            ->call('toggleDropdown')
            ->assertSet('isOpen', true)
            ->assertCount('notifications', 1)
            ->assertSee('Message Here');
    }

    public function test_mark_as_read()
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $notif = $service->createPersistent($user, 'info', 'Read Me', []);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 1)
            ->call('markAsRead', $notif->id)
            ->assertSet('unreadCount', 0);
    }

    public function test_mark_all_as_read()
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $service->createPersistent($user, 'info', 'Read Me 1', ['key' => 1]);
        $service->createPersistent($user, 'info', 'Read Me 2', ['key' => 2]);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 2)
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0);
    }
}
