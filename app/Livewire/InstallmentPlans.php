<?php

namespace App\Livewire;

use App\Models\InstallmentPlan;
use App\Services\InstallmentPlanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class InstallmentPlans extends Component
{
    use WithPagination;

    public $viewingPlanId = null;

    #[On('installment-plan-created')]
    public function planCreated()
    {
        $this->resetPage();
    }

    public function viewPlan($id)
    {
        $plan = InstallmentPlan::where('user_id', Auth::id())->findOrFail($id);
        $this->viewingPlanId = $id;
    }

    public function closeView()
    {
        $this->viewingPlanId = null;
    }

    public function cancelPlan($id, InstallmentPlanService $service)
    {
        $plan = InstallmentPlan::where('user_id', Auth::id())->findOrFail($id);

        if ($plan->status === 'active') {
            $service->cancelPlan($plan);
        }
    }

    public function render()
    {
        $plans = InstallmentPlan::where('user_id', Auth::id())
            ->with(['account', 'category'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $viewingPlan = null;
        if ($this->viewingPlanId) {
            $viewingPlan = InstallmentPlan::with(['obligations' => function ($q) {
                $q->orderBy('installment_number');
            }])
                ->where('user_id', Auth::id())
                ->findOrFail($this->viewingPlanId);
        }

        return view('livewire.installment-plans', [
            'plans' => $plans,
            'viewingPlan' => $viewingPlan,
        ]);
    }
}
