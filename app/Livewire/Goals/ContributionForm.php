<?php

namespace App\Livewire\Goals;

use App\Models\Account;
use App\Models\FinancialGoal;
use App\Services\GoalService;
use Livewire\Component;

class ContributionForm extends Component
{
    public FinancialGoal $goal;

    public $account_id = '';

    public $amount = '';

    public $contribution_date = '';

    public $description = '';

    public function mount(FinancialGoal $goal)
    {
        $this->authorizeAccess($goal);
        $this->goal = $goal;
        $this->contribution_date = now()->format('Y-m-d');
    }

    private function authorizeAccess(FinancialGoal $goal)
    {
        if ($goal->user_id !== auth()->id()) {
            abort(404);
        }
    }

    public function getAccountsProperty()
    {
        return Account::where('user_id', auth()->id())
            ->where('status', 'active')
            ->get();
    }

    public function save(GoalService $goalService)
    {
        $this->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'contribution_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        try {
            $goalService->addContribution(
                $this->goal->id,
                $this->account_id,
                $this->amount,
                $this->contribution_date,
                auth()->id(),
                $this->description
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->dispatch('contribution-added');
        $this->dispatch('closeModal');
    }

    public function render()
    {
        return view('livewire.goals.contribution-form');
    }
}
