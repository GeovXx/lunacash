<?php

namespace Tests\Feature;

use App\Livewire\InstallmentPlanForm;
use App\Livewire\InstallmentPlans;
use App\Models\Account;
use App\Models\Category;
use App\Models\InstallmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InstallmentPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_installment_plan()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(['status' => 'active']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(InstallmentPlanForm::class)
            ->call('openModal')
            ->set('title', 'Smartphone')
            ->set('total_amount', '1200.00')
            ->set('installments_count', 12)
            ->set('first_due_date', '2026-08-20')
            ->set('account_id', $account->id)
            ->set('category_id', $category->id)
            ->set('direction', 'payable')
            ->set('frequency', 'monthly')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('installment_plans', [
            'user_id' => $user->id,
            'title' => 'Smartphone',
            'total_amount' => '1200.00',
            'installments_count' => 12,
        ]);
        
        $this->assertDatabaseCount('financial_obligations', 12);
        
        // Sum of installments should equal total amount
        $totalObligationsAmount = \App\Models\FinancialObligation::where('user_id', $user->id)->sum('amount');
        $this->assertEquals(1200.00, $totalObligationsAmount);
    }

    public function test_user_cannot_create_payable_plan_with_income_category()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(['status' => 'active']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'income']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        Livewire::actingAs($user)
            ->test(InstallmentPlanForm::class)
            ->call('openModal')
            ->set('title', 'Smartphone')
            ->set('total_amount', '1200.00')
            ->set('installments_count', 12)
            ->set('first_due_date', '2026-08-20')
            ->set('account_id', $account->id)
            ->set('category_id', $category->id)
            ->set('direction', 'payable')
            ->set('frequency', 'monthly')
            ->call('save')
            ->assertHasErrors(['category_id']);
    }

    public function test_user_can_view_plan()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(['status' => 'active']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $plan = new InstallmentPlan([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'payable',
            'title' => 'TV',
            'total_amount' => '2000.00',
            'installments_count' => 10,
            'first_due_date' => '2026-08-20',
            'frequency' => 'monthly',
            'status' => 'active',
        ]);
        $plan->forceFill(['user_id' => $user->id])->save();

        Livewire::actingAs($user)
            ->test(InstallmentPlans::class)
            ->call('viewPlan', $plan->id)
            ->assertSet('viewingPlanId', $plan->id);
    }

    public function test_user_cannot_view_another_users_plan()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountA = tap(Account::factory()->make(['status' => 'active']), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());
        $categoryA = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $userA->id])->save());

        $plan = new InstallmentPlan([
            'account_id' => $accountA->id,
            'category_id' => $categoryA->id,
            'direction' => 'payable',
            'title' => 'TV',
            'total_amount' => '2000.00',
            'installments_count' => 10,
            'first_due_date' => '2026-08-20',
            'frequency' => 'monthly',
            'status' => 'active',
        ]);
        $plan->forceFill(['user_id' => $userA->id])->save();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($userB)
            ->test(InstallmentPlans::class)
            ->call('viewPlan', $plan->id);
    }

    public function test_user_can_delete_installment_plan_and_preserve_obligations()
    {
        $user = User::factory()->create();

        $account = tap(Account::factory()->make(['status' => 'active']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());
        $category = tap(Category::factory()->make(['type' => 'expense']), fn($m) => $m->forceFill(['user_id' => $user->id])->save());

        $plan = new InstallmentPlan([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'payable',
            'title' => 'TV',
            'total_amount' => '2000.00',
            'installments_count' => 2,
            'first_due_date' => '2026-08-20',
            'frequency' => 'monthly',
            'status' => 'active',
        ]);
        $plan->forceFill(['user_id' => $user->id])->save();

        // Obligation 1: Unpaid
        $ob1 = new \App\Models\FinancialObligation([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'installment_plan_id' => $plan->id,
            'direction' => 'payable',
            'title' => 'TV 1/2',
            'amount' => '1000.00',
            'due_date' => '2026-08-20',
            'status' => 'open'
        ]);
        $ob1->forceFill(['user_id' => $user->id])->save();

        // Obligation 2: Paid
        $ob2 = new \App\Models\FinancialObligation([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'installment_plan_id' => $plan->id,
            'direction' => 'payable',
            'title' => 'TV 2/2',
            'amount' => '1000.00',
            'due_date' => '2026-09-20',
            'status' => 'paid'
        ]);
        $ob2->forceFill(['user_id' => $user->id])->save();

        Livewire::actingAs($user)
            ->test(InstallmentPlans::class)
            ->call('cancelPlan', $plan->id)
            ->assertHasNoErrors();

        // Plan should be cancelled
        $this->assertDatabaseHas('installment_plans', [
            'id' => $plan->id,
            'status' => 'cancelled'
        ]);

        // Open obligation should remain (preserve history)
        $this->assertDatabaseHas('financial_obligations', [
            'id' => $ob1->id,
            'status' => 'open'
        ]);

        // Paid obligation should remain
        $this->assertDatabaseHas('financial_obligations', [
            'id' => $ob2->id,
            'status' => 'paid'
        ]);
    }
}
