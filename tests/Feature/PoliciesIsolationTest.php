<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\FinancialGoal;
use App\Models\FinancialObligation;
use App\Models\InstallmentPlan;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoliciesIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function assertPolicies(User $owner, User $stranger, $model, bool $canCreate = true)
    {
        $this->assertTrue($owner->can('view', $model), "Owner cannot view " . class_basename($model));
        $this->assertTrue($owner->can('update', $model), "Owner cannot update " . class_basename($model));
        $this->assertTrue($owner->can('delete', $model), "Owner cannot delete " . class_basename($model));

        $this->assertFalse($stranger->can('view', $model), "Stranger can view " . class_basename($model));
        $this->assertFalse($stranger->can('update', $model), "Stranger can update " . class_basename($model));
        $this->assertFalse($stranger->can('delete', $model), "Stranger can delete " . class_basename($model));
    }

    public function test_account_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $this->assertPolicies($userA, $userB, $account);
    }

    public function test_category_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $category = tap(Category::factory()->make(['is_default' => false]), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $this->assertPolicies($userA, $userB, $category);

        $globalCategory = tap(Category::factory()->make(['is_default' => true]), fn($m) => $m->forceFill(['user_id' => null])->save());
        $this->assertTrue($userA->can('view', $globalCategory));
        $this->assertFalse($userA->can('update', $globalCategory));
        $this->assertFalse($userA->can('delete', $globalCategory));
    }

    public function test_credit_card_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $card = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $this->assertPolicies($userA, $userB, $card);
    }

    public function test_credit_card_invoice_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $card = tap(CreditCard::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $invoice = tap(CreditCardInvoice::factory()->make(['credit_card_id' => $card->id]), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $this->assertPolicies($userA, $userB, $invoice);
    }

    public function test_financial_goal_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $goal = new FinancialGoal([
            'name' => 'Test Goal',
            'target_amount' => 1000,
            'target_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'currency' => 'BRL',
        ]);
        $goal->user_id = $userA->id;
        $goal->save();

        $this->assertPolicies($userA, $userB, $goal);
    }

    public function test_financial_obligation_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $category = tap(Category::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        
        // Payable
        $obligation = new FinancialObligation([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'payable',
            'title' => 'Test Payable',
            'amount' => 100,
            'currency' => 'BRL',
            'due_date' => now()->toDateString(),
            'status' => 'open'
        ]);
        $obligation->user_id = $userA->id;
        $obligation->save();

        $this->assertPolicies($userA, $userB, $obligation);
    }

    public function test_installment_plan_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $category = tap(Category::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        
        $plan = new InstallmentPlan([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'payable',
            'title' => 'Test Plan',
            'total_amount' => 1000,
            'installments_count' => 10,
            'first_due_date' => now()->toDateString(),
            'frequency' => 'monthly',
            'status' => 'active'
        ]);
        $plan->user_id = $userA->id;
        $plan->save();

        $this->assertPolicies($userA, $userB, $plan);
    }

    public function test_transaction_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $category = tap(Category::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        
        $transaction = tap(Transaction::factory()->make([
            'account_id' => $account->id,
            'category_id' => $category->id,
        ]), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $this->assertPolicies($userA, $userB, $transaction);
    }

    public function test_transfer_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $account1 = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $account2 = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        
        $transfer = new Transfer([
            'from_account_id' => $account1->id,
            'to_account_id' => $account2->id,
            'amount' => 100,
            'currency' => 'BRL',
            'transfer_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $transfer->user_id = $userA->id;
        $transfer->save();

        $this->assertPolicies($userA, $userB, $transfer);
    }

    public function test_budget_policy_isolation()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $budget = new Budget([
            'name' => 'Test Budget',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'currency' => 'BRL',
            'target_amount' => 1000,
        ]);
        $budget->user_id = $userA->id;
        $budget->save();

        $this->assertPolicies($userA, $userB, $budget);
    }

    public function test_user_scope_binds_the_authenticated_users_identifier()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountA = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $accountB1 = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());
        $accountB2 = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());

        $this->actingAs($userA);
        $this->assertEquals(1, Account::forUser()->count());
        $this->assertEquals($accountA->id, Account::forUser()->first()->id);

        $this->actingAs($userB);
        $this->assertEquals(2, Account::forUser()->count());
    }
}
