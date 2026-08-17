<?php

namespace App\Http\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Design System'])]
class DesignSystem extends Component
{
    public function render()
    {
        return view('livewire.design-system');
    }
}
