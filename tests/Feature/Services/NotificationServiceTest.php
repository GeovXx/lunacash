<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\FinancialObligation;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService;
    }

    public function test_zero_notifications()
    {
        $user = User::factory()->create();

        $this->assertEquals(0, $this->service->getUnreadPersistentCount($user));
        $this->assertFalse($this->service->hasEphemeralAlerts($user));
        $this->assertEmpty($this->service->getNotifications($user));
    }

    public function test_persistent_notification_creation_and_deduplication()
    {
        $user = User::factory()->create();

        // Create first time
        $notif1 = $this->service->createPersistent($user, 'info', 'Test 1', ['key' => 'value']);
        $this->assertNotNull($notif1);
        $this->assertEquals(1, $this->service->getUnreadPersistentCount($user));

        // Attempt exact duplicate (deduplication should catch it via signature in data)
        $notif2 = $this->service->createPersistent($user, 'info', 'Test 1', ['key' => 'value']);
        $this->assertEquals($notif1->id, $notif2->id); // Returned the existing one
        $this->assertEquals(1, $this->service->getUnreadPersistentCount($user));

        // Create with different data
        $notif3 = $this->service->createPersistent($user, 'info', 'Test 1', ['key' => 'different']);
        $this->assertNotEquals($notif1->id, $notif3->id);
        $this->assertEquals(2, $this->service->getUnreadPersistentCount($user));
    }

    public function test_isolation_between_users()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->service->createPersistent($userA, 'info', 'For User A', []);

        $this->assertEquals(1, $this->service->getUnreadPersistentCount($userA));
        $this->assertEquals(0, $this->service->getUnreadPersistentCount($userB));

        $notificationsB = $this->service->getNotifications($userB);
        $this->assertEmpty($notificationsB);
    }

    public function test_mark_as_read_with_tenant_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $notif = $this->service->createPersistent($userA, 'info', 'Test', []);

        // User B tries to read User A's notification
        $this->assertFalse($this->service->markAsRead($notif->id, $userB));
        $this->assertEquals(1, $this->service->getUnreadPersistentCount($userA));

        // User A reads it
        $this->assertTrue($this->service->markAsRead($notif->id, $userA));
        $this->assertEquals(0, $this->service->getUnreadPersistentCount($userA));

        // Verify read_at
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_ephemeral_overdue_obligations()
    {
        $user = User::factory()->create();
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $user->id]), fn($m) => $m->save());

        $obId = Str::uuid()->toString();
        DB::table('financial_obligations')->insert([
            'id' => $obId,
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

        $this->assertTrue($this->service->hasEphemeralAlerts($user));

        $alerts = $this->service->getNotifications($user);
        $this->assertCount(1, $alerts);

        $alert = $alerts[0];
        $this->assertTrue($alert['is_ephemeral']);
        $this->assertEquals('overdue', $alert['type']);
        $this->assertEquals($obId, $alert['data']['obligation_id']);

        // Assert no persistent records were created
        $this->assertEquals(0, Notification::count());

        // Validate NO FINANCIAL MUTATIONS
        $ob = FinancialObligation::find($obId);
        $this->assertEquals(100, $ob->amount);
        $this->assertEquals('open', $ob->status);
    }

    public function test_ephemeral_due_soon_obligations()
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
            'due_date' => Carbon::tomorrow(),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $alerts = $this->service->getNotifications($user);
        $this->assertCount(1, $alerts);
        $this->assertEquals('due_soon', $alerts[0]['type']);
    }

    public function test_ephemeral_paid_or_cancelled_obligations_are_ignored()
    {
        $user = User::factory()->create();
        $account = tap(Account::factory()->make()->forceFill(['user_id' => $user->id]), fn($m) => $m->save());

        DB::table('financial_obligations')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'account_id' => $account->id,
            'direction' => 'payable',
            'title' => 'Test 1',
            'amount' => 100,
            'due_date' => Carbon::yesterday(),
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('financial_obligations')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'account_id' => $account->id,
            'direction' => 'payable',
            'title' => 'Test 2',
            'amount' => 100,
            'due_date' => Carbon::tomorrow(),
            'status' => 'cancelled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($this->service->hasEphemeralAlerts($user));
    }

    public function test_persistent_limit()
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->service->createPersistent($user, 'info', "Test {$i}", ['i' => $i]);
        }

        $this->assertEquals(20, $this->service->getUnreadPersistentCount($user));

        // Ensure we only fetch the limit
        $notifications = $this->service->getNotifications($user, 10);
        $this->assertCount(10, $notifications);
    }
}
