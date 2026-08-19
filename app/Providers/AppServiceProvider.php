<?php

namespace App\Providers;

use App\Livewire\GlobalSearch;
use App\Livewire\HomePage;
use App\Livewire\NotificationBell;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInstallment;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardInvoicePayment;
use App\Models\CreditCardTransaction;
use App\Models\FinancialGoal;
use App\Models\FinancialObligation;
use App\Models\GoalContribution;
use App\Models\InstallmentPlan;
use App\Models\Notification;
use App\Models\RecurringProfile;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionTag;
use App\Models\Transfer;
use App\Observers\AuditObserver;
use App\Policies\CategoryPolicy;
use App\Policies\UserOwnedPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        Livewire::component('home-page', HomePage::class);
        Livewire::component('global-search', GlobalSearch::class);
        Livewire::component('notification-bell', NotificationBell::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Category::observe(AuditObserver::class);

        foreach ([
            Account::class,
            AuditLog::class,
            Budget::class,
            BudgetLine::class,
            CreditCard::class,
            CreditCardInstallment::class,
            CreditCardInvoice::class,
            CreditCardInvoicePayment::class,
            CreditCardTransaction::class,
            FinancialGoal::class,
            FinancialObligation::class,
            InstallmentPlan::class,
            GoalContribution::class,
            Notification::class,
            RecurringProfile::class,
            Tag::class,
            Transaction::class,
            TransactionTag::class,
            Transfer::class,
        ] as $model) {
            Gate::policy($model, UserOwnedPolicy::class);
            $model::observe(AuditObserver::class);
        }
    }
}
