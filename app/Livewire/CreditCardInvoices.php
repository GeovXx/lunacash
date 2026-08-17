<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardInvoicePayment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreditCardInvoices extends Component
{
    public $creditCardId;

    public $invoiceIdToPay;

    public $accountId;

    public $paymentAmount;

    public $paymentDate;

    public $isPaymentModalOpen = false;

    public $selectedInvoice = null;

    public $invoiceInstallments = [];

    public $isDetailsModalOpen = false;

    protected function rules()
    {
        return [
            'accountId' => 'required|exists:accounts,id',
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
        ];
    }

    public function mount()
    {
        $firstCard = CreditCard::forUser()->first();
        if ($firstCard) {
            $this->creditCardId = $firstCard->id;
        }
    }

    public function render()
    {
        $invoices = [];
        if ($this->creditCardId) {
            $invoices = CreditCardInvoice::forUser()
                ->where('credit_card_id', $this->creditCardId)
                ->orderBy('closing_date', 'desc')
                ->get();
        }

        return view('livewire.credit-card-invoices', [
            'creditCards' => CreditCard::forUser()->get(),
            'invoices' => $invoices,
            'accounts' => Account::forUser()->where('status', 'active')->get(),
        ]);
    }

    public function openPaymentModal($invoiceId)
    {
        $invoice = CreditCardInvoice::forUser()->findOrFail($invoiceId);
        $this->invoiceIdToPay = $invoice->id;
        $this->paymentAmount = bcsub((string) $invoice->total_amount, (string) $invoice->paid_amount, 2);
        $this->paymentDate = now()->toDateString();
        $this->accountId = '';
        $this->isPaymentModalOpen = true;
    }

    public function closePaymentModal()
    {
        $this->isPaymentModalOpen = false;
        $this->invoiceIdToPay = null;
        $this->paymentAmount = '';
        $this->accountId = '';
        $this->paymentDate = '';
    }

    public function payInvoice()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $invoice = CreditCardInvoice::forUser()->where('id', $this->invoiceIdToPay)->lockForUpdate()->firstOrFail();
                $card = CreditCard::forUser()->where('id', $invoice->credit_card_id)->lockForUpdate()->firstOrFail();
                $account = Account::forUser()->where('id', $this->accountId)->firstOrFail();

                $remainingDebt = bcsub((string) $invoice->total_amount, (string) $invoice->paid_amount, 2);

                if (bccomp((string) $this->paymentAmount, $remainingDebt, 2) === 1) {
                    throw new \Exception('O valor do pagamento (R$ '.number_format($this->paymentAmount, 2, ',', '.').') não pode ser maior que o saldo devedor restante (R$ '.number_format($remainingDebt, 2, ',', '.').').');
                }

                // Create the Transaction in the bank account (type: payment)
                $bankTransaction = new Transaction([
                    'account_id' => $account->id,
                    'type' => 'payment',
                    'amount' => $this->paymentAmount,
                    'currency' => 'BRL',
                    'transaction_date' => $this->paymentDate,
                    'status' => 'posted',
                    'description' => 'Pagamento Fatura Cartão '.$card->name,
                ]);
                $bankTransaction->user_id = Auth::id();
                $bankTransaction->save();

                // Create the Invoice Payment
                $invoicePayment = new CreditCardInvoicePayment([
                    'invoice_id' => $invoice->id,
                    'account_id' => $account->id,
                    'transaction_id' => $bankTransaction->id,
                    'amount' => $this->paymentAmount,
                    'currency' => 'BRL',
                    'payment_date' => $this->paymentDate,
                    'status' => 'completed',
                ]);
                $invoicePayment->user_id = Auth::id();
                $invoicePayment->save();

                // Update Invoice
                $newPaidAmount = bcadd((string) $invoice->paid_amount, (string) $this->paymentAmount, 2);
                $newStatus = bccomp($newPaidAmount, (string) $invoice->total_amount, 2) === 0 ? 'paid' : 'partially_paid';

                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => $newStatus,
                ]);

                // Update Card Available Limit
                if ($card->available_limit !== null) {
                    $newAvailableLimit = bcadd((string) $card->available_limit, (string) $this->paymentAmount, 2);
                    if ($card->limit_amount !== null && bccomp($newAvailableLimit, (string) $card->limit_amount, 2) === 1) {
                        $newAvailableLimit = $card->limit_amount; // Don't exceed total limit
                    }
                    $card->update(['available_limit' => $newAvailableLimit]);
                }
            });

            session()->flash('message', 'Pagamento registrado com sucesso!');
            $this->closePaymentModal();
        } catch (\Exception $e) {
            $this->addError('paymentAmount', $e->getMessage());
        }
    }

    public function viewInvoiceDetails($invoiceId)
    {
        $this->selectedInvoice = CreditCardInvoice::forUser()->findOrFail($invoiceId);
        $this->invoiceInstallments = $this->selectedInvoice->installments()->with(['transaction', 'transaction.category'])->orderBy('sequence')->get();
        $this->isDetailsModalOpen = true;
    }

    public function closeDetailsModal()
    {
        $this->isDetailsModalOpen = false;
        $this->selectedInvoice = null;
        $this->invoiceInstallments = [];
    }
}
