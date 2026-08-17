<?php

namespace App\Http\Livewire;

use App\Models\Account;
use App\Services\BudgetService;
use App\Services\CalendarService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Dashboard'])]
class Home extends Component
{
    public function render(DashboardService $dashboardService, CalendarService $calendarService, BudgetService $budgetService)
    {
        $userId = Auth::id();
        $month = Carbon::today();

        $accountsBalance = $dashboardService->getAccountsBalance($userId);
        $monthlyIncomes = $dashboardService->getMonthlyIncomes($userId, $month);
        $monthlyExpenses = $dashboardService->getMonthlyExpenses($userId, $month);
        $pendingInvoicesTotal = $dashboardService->getPendingInvoicesTotal($userId);

        $activeAccounts = Account::where('user_id', $userId)->where('status', 'active')->get();

        $budgets = $dashboardService->getActiveBudgets($userId, $month, $budgetService);
        $goals = $dashboardService->getActiveGoals($userId);

        $startOfGrid = Carbon::today();
        $endOfGrid = Carbon::today()->addDays(15);
        $upcomingEvents = $calendarService->getEventsForPeriod($userId, $startOfGrid->toDateString(), $endOfGrid->toDateString());

        $cashFlowChart = $dashboardService->getCashFlowEvolution($userId, 6);
        $expensesByCategoryChart = $dashboardService->getExpensesByCategory($userId, $month);

        return view('livewire.home', [
            'accountsBalance' => $accountsBalance,
            'monthlyIncomes' => $monthlyIncomes,
            'monthlyExpenses' => $monthlyExpenses,
            'pendingInvoicesTotal' => $pendingInvoicesTotal,
            'activeAccounts' => $activeAccounts,
            'budgets' => $budgets,
            'goals' => $goals,
            'upcomingEvents' => $upcomingEvents,
            'currentMonth' => clone $month,
            'cashFlowChart' => $cashFlowChart,
            'expensesByCategoryChart' => $expensesByCategoryChart,
        ]);
    }
}
