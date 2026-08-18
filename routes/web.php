<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Accounts;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Categories;
use App\Livewire\DesignSystem;
use App\Livewire\Expenses;
use App\Livewire\Home;
use App\Livewire\Incomes;
use App\Livewire\RecurringProfiles;
use App\Livewire\Transactions;
use App\Livewire\Transfers;
use App\Livewire\Budgets\BudgetDetail;
use App\Livewire\Budgets\BudgetsList;
use App\Livewire\Calendar\CalendarView;
use App\Livewire\Goals\GoalDetail;
use App\Livewire\Goals\GoalsList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', Home::class)->name('home');
    Route::get('/contas', Accounts::class)->name('accounts');
    Route::get('/categorias', Categories::class)->name('categories');
    Route::get('/lancamentos', Transactions::class)->name('transactions');
    // Reports & Export
    Route::get('/relatorios', App\Livewire\Reports\ReportIndex::class)->name('reports.index');
    Route::get('/exportar/caixa', [App\Http\Controllers\ExportController::class, 'exportCashFlow'])->name('export.cash-flow');
    Route::get('/exportar/categorias', [App\Http\Controllers\ExportController::class, 'exportCategories'])->name('export.categories');
    Route::get('/exportar/posicao', [App\Http\Controllers\ExportController::class, 'exportBalances'])->name('export.balances');
    
    Route::get('/transferencias', Transfers::class)->name('transfers');
    Route::get('/recorrencias', RecurringProfiles::class)->name('recurring-profiles');
    Route::get('/receitas', Incomes::class)->name('incomes');
    Route::get('/despesas', Expenses::class)->name('expenses');
    Route::get('/calendario', CalendarView::class)->name('calendar');

    // Budgets
    Route::get('/orcamentos', BudgetsList::class)->name('budgets.index');
    Route::get('/orcamentos/{budgetId}', BudgetDetail::class)->name('budgets.show');

    // Goals
    Route::get('/metas', GoalsList::class)->name('goals.index');
    Route::get('/metas/{goal}', GoalDetail::class)->name('goals.show');

    Route::get('/design-system', DesignSystem::class)->name('design-system');
    Route::post('/logout', LogoutController::class)->name('logout');
});
