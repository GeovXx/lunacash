<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class IsolationSecurityTest extends TestCase
{
    public function test_owner_can_access_own_transaction_and_another_user_cannot(): void
    {
        $userA = (new User)->forceFill(['id' => '11111111-1111-1111-1111-111111111111']);
        $userB = (new User)->forceFill(['id' => '22222222-2222-2222-2222-222222222222']);
        $transaction = (new Transaction)->forceFill(['user_id' => $userA->id]);

        $this->assertTrue(Gate::forUser($userA)->allows('view', $transaction));
        $this->assertFalse(Gate::forUser($userB)->allows('view', $transaction));
        $this->assertFalse(Gate::forUser($userB)->allows('update', $transaction));
        $this->assertFalse(Gate::forUser($userB)->allows('delete', $transaction));
    }

    public function test_user_scope_binds_the_authenticated_users_identifier(): void
    {
        $user = (new User)->forceFill(['id' => '22222222-2222-2222-2222-222222222222']);
        $query = Transaction::forUser($user);

        $this->assertStringContainsString('"user_id"', $query->toSql());
        $this->assertSame([$user->id], $query->getBindings());
    }

    public function test_global_category_is_readable_but_not_mutable_by_a_non_owner(): void
    {
        $user = (new User)->forceFill(['id' => '22222222-2222-2222-2222-222222222222']);
        $globalCategory = (new Category)->forceFill(['user_id' => null]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $globalCategory));
        $this->assertFalse(Gate::forUser($user)->allows('update', $globalCategory));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $globalCategory));
    }
}
