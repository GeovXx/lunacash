<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Livewire\Accounts;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\Register;
use App\Http\Livewire\Categories;
use App\Http\Livewire\DesignSystem;
use App\Http\Livewire\Expenses;
use App\Http\Livewire\Home;
use App\Http\Livewire\Incomes;
use App\Http\Livewire\RecurringProfiles;
use App\Http\Livewire\Transactions;
use App\Http\Livewire\Transfers;
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
    Route::get('/relatorios', App\Http\Livewire\Reports\ReportIndex::class)->name('reports.index');
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
