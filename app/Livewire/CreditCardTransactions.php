<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\CreditCard;
use App\Models\CreditCardInstallment;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreditCardTransactions extends Component
{
    public $creditCardId;

    public $categoryId;

    public $description;

    public $amount;

    public $transactionDate;

    public $installmentsTotal = 1;

    public $isModalOpen = false;

    public $selectedTransaction = null;

    public $transactionInstallments = [];

    public $isInstallmentsModalOpen = false;

    protected function rules()
    {
        return [
            'creditCardId' => 'required|exists:credit_cards,id',
            'categoryId' => 'required|exists:categories,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transactionDate' => 'required|date',
            'installmentsTotal' => 'required|integer|min:1|max:48',
        ];
    }

    public function render()
    {
        return view('livewire.credit-card-transactions', [
            'transactions' => CreditCardTransaction::forUser()->with(['creditCard', 'category'])->latest('transaction_date')->get(),
            'creditCards' => CreditCard::forUser()->where('status', 'active')->get(),
            'categories' => Category::visibleTo(Auth::user())->where('type', 'expense')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->transactionDate = now()->toDateString();
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
        $this->creditCardId = '';
        $this->categoryId = '';
        $this->description = '';
        $this->amount = '';
        $this->transactionDate = '';
        $this->installmentsTotal = 1;
    }

    public function store()
    {
        $this->validate();

        $category = Category::visibleTo(Auth::user())->findOrFail($this->categoryId);
        if ($category->type !== 'expense') {
            $this->addError('categoryId', 'Apenas categorias do tipo despesa são permitidas.');

            return;
        }

        try {
            DB::transaction(function () use ($category) {
                $card = CreditCard::where('id', $this->creditCardId)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                // Validate Limit
                if ($card->available_limit !== null) {
                    $newAvailableLimit = bcsub((string) $card->available_limit, (string) $this->amount, 2);
                    if ($newAvailableLimit < 0) {
                        throw new \Exception('Limite indisponível para esta compra.');
                    }
                    $card->update(['available_limit' => $newAvailableLimit]);
                }

                $transaction = new CreditCardTransaction([
                    'credit_card_id' => $card->id,
                    'category_id' => $category->id,
                    'description' => $this->description,
                    'amount' => $this->amount,
                    'currency' => 'BRL',
                    'transaction_date' => $this->transactionDate,
                    'status' => 'posted',
                    'installments_total' => $this->installmentsTotal,
                ]);
                $transaction->user_id = Auth::id();
                $transaction->save();

                $installmentAmount = bcdiv((string) $this->amount, (string) $this->installmentsTotal, 2);
                $remainder = bcsub((string) $this->amount, bcmul($installmentAmount, (string) $this->installmentsTotal, 2), 2);

                $txDate = Carbon::parse($this->transactionDate);

                for ($i = 1; $i <= $this->installmentsTotal; $i++) {
                    $currentAmount = $installmentAmount;
                    if ($i === 1 && $remainder > 0) {
                        $currentAmount = bcadd($currentAmount, $remainder, 2);
                    }

                    // Determine the invoice for this specific installment
                    $targetDate = $txDate->copy()->addMonthsNoOverflow($i - 1);
                    $invoice = $this->getOrCreateInvoiceForDate($card, $targetDate);

                    $installment = new CreditCardInstallment([
                        'credit_card_transaction_id' => $transaction->id,
                        'invoice_id' => $invoice->id,
                        'sequence' => $i,
                        'due_date' => $invoice->due_date,
                        'amount' => $currentAmount,
                        'currency' => 'BRL',
                        'status' => 'pending',
                    ]);
                    $installment->user_id = Auth::id();
                    $installment->save();

                    // Increment the invoice total amount
                    $invoice->update([
                        'total_amount' => bcadd((string) $invoice->total_amount, $currentAmount, 2),
                    ]);
                }
            });

            session()->flash('message', 'Compra registrada com sucesso!');
            $this->closeModal();
        } catch (ModelNotFoundException $e) {
            $this->addError('creditCardId', 'Cartão não encontrado ou acesso negado.');
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Limite indisponível para esta compra.') {
                $this->addError('amount', $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    private function getOrCreateInvoiceForDate(CreditCard $card, Carbon $date)
    {
        $statementDay = $card->statement_day;

        $closingDateThisMonth = $date->copy()->day($statementDay > $date->daysInMonth ? $date->daysInMonth : $statementDay);

        if ($date->copy()->startOfDay()->lte($closingDateThisMonth->copy()->startOfDay())) {
            $periodEnd = $closingDateThisMonth;
        } else {
            $nextMonth = $date->copy()->addMonthNoOverflow();
            $periodEnd = $nextMonth->copy()->day($statementDay > $nextMonth->daysInMonth ? $nextMonth->daysInMonth : $statementDay);
        }

        $periodStart = (clone $periodEnd)->subMonthNoOverflow()->addDay();

        $dueDay = $card->due_day;
        $dueDate = (clone $periodEnd)->day($dueDay > $periodEnd->daysInMonth ? $periodEnd->daysInMonth : $dueDay);
        if ($dueDate->copy()->startOfDay()->lte($periodEnd->copy()->startOfDay())) {
            $dueDate->addMonthNoOverflow();
            $dueDate->day($dueDay > $dueDate->daysInMonth ? $dueDate->daysInMonth : $dueDay);
        }

        $invoice = CreditCardInvoice::forUser()
            ->where('credit_card_id', $card->id)
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if (! $invoice) {
            $invoice = new CreditCardInvoice([
                'credit_card_id' => $card->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'closing_date' => $periodEnd->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'open',
                'minimum_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
            ]);
            $invoice->user_id = Auth::id();
            $invoice->save();
        }

        return $invoice;
    }

    public function viewInstallments($transactionId)
    {
        $this->selectedTransaction = CreditCardTransaction::forUser()->findOrFail($transactionId);
        $this->transactionInstallments = $this->selectedTransaction->installments()->with('invoice')->orderBy('sequence')->get();
        $this->isInstallmentsModalOpen = true;
    }

    public function closeInstallmentsModal()
    {
        $this->isInstallmentsModalOpen = false;
        $this->selectedTransaction = null;
        $this->transactionInstallments = [];
    }
}
