<?php

namespace App\Http\Livewire\Reports;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ReportIndex extends Component
{
    use WithPagination;

    public $currentMonth;

    public $activeTab = 'cash_flow'; // cash_flow, category, balances

    protected $queryString = ['activeTab', 'currentMonth' => ['except' => '']];

    public function mount()
    {
        $this->currentMonth = $this->currentMonth ?: Carbon::today()->format('Y-m');
    }

    public function previousMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth.'-01')->subMonth()->format('Y-m');
        $this->resetPage();
    }

    public function nextMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth.'-01')->addMonth()->format('Y-m');
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render(ReportService $reportService)
    {
        $userId = Auth::id();
        $date = Carbon::parse($this->currentMonth.'-01');

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $cashFlowReport = [];
        $categoryReport = [];
        $balancesReport = [];

        if ($this->activeTab === 'cash_flow') {
            $cashFlowReport = $reportService->getCashFlowReport($userId, $start, $end);
        } elseif ($this->activeTab === 'category') {
            $categoryReport = $reportService->getCategoryReport($userId, $start, $end);
        } elseif ($this->activeTab === 'balances') {
            $balancesReport = $reportService->getBalancesReport($userId);
        }

        return view('livewire.reports.report-index', [
            'monthName' => ucfirst($date->translatedFormat('F Y')),
            'cashFlowReport' => $cashFlowReport,
            'categoryReport' => $categoryReport,
            'balancesReport' => $balancesReport,
        ])->layout('layouts.app');
    }
}
