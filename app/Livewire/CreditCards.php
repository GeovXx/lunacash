<?php

namespace App\Livewire;

use App\Models\CreditCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreditCards extends Component
{
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $creditCardId = null;

    public $name;
    public $issuer;
    public $last_digits;
    public $limit_amount;
    public $statement_day;
    public $due_day;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'issuer' => 'nullable|string|max:255',
            'last_digits' => 'nullable|digits:4',
            'limit_amount' => 'required|numeric|min:0',
            'statement_day' => 'required|integer|min:1|max:31',
            'due_day' => 'required|integer|min:1|max:31',
        ];
    }

    public function render()
    {
        return view('livewire.credit-cards', [
            'creditCards' => CreditCard::forUser()->get()
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->creditCardId = null;
        $this->name = '';
        $this->issuer = '';
        $this->last_digits = '';
        $this->limit_amount = '';
        $this->statement_day = '';
        $this->due_day = '';
    }

    public function store()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'issuer' => $this->issuer ?: null,
            'last_digits' => $this->last_digits ?: null,
            'limit_amount' => $this->limit_amount,
            'statement_day' => $this->statement_day,
            'due_day' => $this->due_day,
            'currency' => 'BRL',
            'status' => 'active',
        ];

        if ($this->creditCardId) {
            $creditCard = CreditCard::forUser()->findOrFail($this->creditCardId);
            $this->authorize('update', $creditCard);
            
            $usedLimit = bcsub((string) $creditCard->limit_amount, (string) $creditCard->available_limit, 2);
            $newAvailableLimit = bcsub((string) $this->limit_amount, $usedLimit, 2);
            
            if ($newAvailableLimit < 0) {
                $this->addError('limit_amount', 'O novo limite não pode ser menor que o limite já utilizado (R$ ' . number_format($usedLimit, 2, ',', '.') . ').');
                return;
            }

            $data['available_limit'] = $newAvailableLimit;
            $creditCard->update($data);
            session()->flash('message', 'Cartão atualizado com sucesso.');
        } else {
            $data['available_limit'] = $this->limit_amount;
            $card = new CreditCard($data);
            $card->user_id = Auth::id();
            $card->save();
            session()->flash('message', 'Cartão criado com sucesso.');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $creditCard = CreditCard::forUser()->findOrFail($id);
        $this->authorize('view', $creditCard);
        $this->creditCardId = $id;
        $this->name = $creditCard->name;
        $this->issuer = $creditCard->issuer;
        $this->last_digits = $creditCard->last_digits;
        $this->limit_amount = $creditCard->limit_amount;
        $this->statement_day = $creditCard->statement_day;
        $this->due_day = $creditCard->due_day;

        $this->openModal();
    }

    public function confirmDelete($id)
    {
        $creditCard = CreditCard::forUser()->findOrFail($id);
        $this->authorize('view', $creditCard);
        $this->creditCardId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        $creditCard = CreditCard::forUser()->findOrFail($this->creditCardId);
        $this->authorize('delete', $creditCard);
        
        if ($creditCard->invoices()->where('status', '!=', 'paid')->exists() || $creditCard->transactions()->exists()) {
             session()->flash('error', 'Este cartão possui faturas ou transações e não pode ser excluído diretamente. Exclua as dependências primeiro.');
             $this->isDeleteModalOpen = false;
             return;
        }

        $creditCard->delete();
        session()->flash('message', 'Cartão excluído com sucesso.');
        $this->isDeleteModalOpen = false;
    }
}
