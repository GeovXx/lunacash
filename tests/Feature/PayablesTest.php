<?php

namespace Tests\Feature;

use App\Livewire\Payables\PayableForm;
use App\Livewire\Payables\PayablesList;
use App\Livewire\Payables\PayablePayModal;
use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialObligation;
use App\Models\InstallmentPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_payable_with_no_immediate_transaction_and_no_balance_change()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make([
            'status' => 'active',
        ]), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(PayableForm::class)
            ->call('openPayableForm')
            ->set('title', 'Internet')
            ->set('amount', '100.00')
            ->set('due_date', '2026-08-20')
            ->set('account_id', $account->id)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_obligations', [
            'user_id' => $user->id,
            'title' => 'Internet',
            'direction' => 'payable', // direction forçado
            'amount' => '100.00',
            'status' => 'open',
        ]);

        // Não criar Transaction na criação
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_payable_requires_expense_category()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'income']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(PayableForm::class)
            ->call('openPayableForm')
            ->set('title', 'Internet')
            ->set('amount', '100.00')
            ->set('due_date', '2026-08-20')
            ->set('account_id', $account->id)
            ->set('category_id', $category->id) // income category
            ->call('save')
            ->assertHasErrors(['general']);
    }

    public function test_payable_cannot_be_created_with_another_users_account()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountB = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());
        $categoryA = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        Livewire::actingAs($userA)
            ->test(PayableForm::class)
            ->call('openPayableForm')
            ->set('title', 'Internet')
            ->set('amount', '100.00')
            ->set('due_date', '2026-08-20')
            ->set('account_id', $accountB->id)
            ->set('category_id', $categoryA->id)
            ->call('save')
            ->assertHasErrors(['general']);
    }

    public function test_user_can_pay_payable_creating_transaction_and_updating_status()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $obligation = new FinancialObligation([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'payable',
            'title' => 'Luz',
            'amount' => '50.00',
            'due_date' => '2026-08-20',
            'status' => 'open'
        ]);
        $obligation->user_id = $user->id;
        $obligation->save();

        Livewire::actingAs($user)
            ->test(PayablePayModal::class)
            ->call('openPayablePayModal', ['obligationId' => $obligation->id])
            ->set('account_id', $account->id)
            ->set('transaction_date', '2026-08-15')
            ->call('pay')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_obligations', [
            'id' => $obligation->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'financial_obligation_id' => $obligation->id,
            'amount' => '50.00',
            'type' => 'expense',
        ]);
    }

    public function test_cannot_pay_already_paid_obligation()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $obligation = new FinancialObligation([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'payable',
            'title' => 'Luz',
            'amount' => '50.00',
            'due_date' => '2026-08-20',
            'status' => 'paid'
        ]);
        $obligation->user_id = $user->id;
        $obligation->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(PayablePayModal::class)
            ->call('openPayablePayModal', ['obligationId' => $obligation->id]);
    }

    public function test_payable_tied_to_installment_plan_is_protected_from_financial_edits()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

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
        $plan->user_id = $user->id;
        $plan->save();

        $obligation = new FinancialObligation([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'installment_plan_id' => $plan->id,
            'direction' => 'payable',
            'title' => 'Luz',
            'amount' => '100.00',
            'due_date' => '2026-08-20',
            'status' => 'open'
        ]);
        $obligation->user_id = $user->id;
        $obligation->save();

        Livewire::actingAs($user)
            ->test(PayableForm::class)
            ->call('openPayableForm', ['obligationId' => $obligation->id])
            ->set('amount', '200.00') // attempt to edit amount
            ->set('due_date', '2026-09-20') // attempt to edit date
            ->set('notes', 'Added notes')
            ->call('save')
            ->assertHasNoErrors();

        // Financial fields should be untouched, only notes
        $this->assertDatabaseHas('financial_obligations', [
            'id' => $obligation->id,
            'amount' => 100,
            'notes' => 'Added notes'
        ]);
    }

    public function test_cannot_pay_another_users_payable()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountB = tap(Account::factory()->make(), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());
        $categoryB = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $userB->id])->save());

        $obligationB = new FinancialObligation([
            'account_id' => $accountB->id,
            'category_id' => $categoryB->id,
            'direction' => 'payable',
            'title' => 'User B Payable',
            'amount' => '50.00',
            'due_date' => '2026-08-20',
            'status' => 'open'
        ]);
        $obligationB->user_id = $userB->id;
        $obligationB->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userA)
            ->test(PayablePayModal::class)
            ->call('openPayablePayModal', ['obligationId' => $obligationB->id]);
    }
}
