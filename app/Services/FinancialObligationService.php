<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialObligation;
use App\Models\Transaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialObligationService
{
    // =========================================================================
    // PAYABLES (Contas a Pagar)
    // =========================================================================

    public function createPayable(array $data)
    {
        return $this->_createObligation($data, 'payable', 'expense');
    }

    public function payObligation(FinancialObligation $obligation, array $paymentData)
    {
        return $this->_settleObligation($obligation, $paymentData, 'payable', 'expense');
    }

    public function updatePayable(FinancialObligation $obligation, array $data)
    {
        return $this->_updateObligation($obligation, $data, 'expense');
    }

    // =========================================================================
    // RECEIVABLES (Contas a Receber)
    // =========================================================================

    public function createReceivable(array $data)
    {
        return $this->_createObligation($data, 'receivable', 'income');
    }

    public function receiveObligation(FinancialObligation $obligation, array $paymentData)
    {
        return $this->_settleObligation($obligation, $paymentData, 'receivable', 'income');
    }

    public function updateReceivable(FinancialObligation $obligation, array $data)
    {
        return $this->_updateObligation($obligation, $data, 'income');
    }

    // =========================================================================
    // PRIVATE DOMAIN LOGIC
    // =========================================================================

    private function _createObligation(array $data, string $expectedDirection, string $expectedCategoryType)
    {
        return DB::transaction(function () use ($data, $expectedDirection, $expectedCategoryType) {
            $userId = auth()->id() ?? $data['user_id'] ?? throw new InvalidArgumentException('User ID is required.');

            // Validate Account
            $account = Account::where('id', $data['account_id'])
                ->where('user_id', $userId)
                ->firstOrFail();

            // Validate Category
            $category = Category::where('id', $data['category_id'])
                ->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)->orWhereNull('user_id');
                })->firstOrFail();

            if ($category->type !== $expectedCategoryType) {
                throw new InvalidArgumentException(ucfirst($expectedDirection).'s must use an '.$expectedCategoryType.' category.');
            }

            $obligation = new FinancialObligation([
                'account_id' => $account->id,
                'category_id' => $category->id,
                'direction' => $expectedDirection, // Forced by backend
                'title' => $data['title'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'BRL',
                'due_date' => $data['due_date'],
                'issued_date' => $data['issued_date'] ?? null,
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
            ]);
            $obligation->user_id = $userId;
            $obligation->save();

            return $obligation;
        });
    }

    private function _settleObligation(FinancialObligation $obligation, array $paymentData, string $expectedDirection, string $transactionType)
    {
        return DB::transaction(function () use ($obligation, $paymentData, $expectedDirection, $transactionType) {
            $userId = auth()->id() ?? $paymentData['user_id'] ?? throw new InvalidArgumentException('User ID is required.');

            if ($obligation->user_id !== $userId) {
                throw new AuthorizationException('Unauthorized.');
            }

            if ($obligation->status !== 'open') {
                throw new InvalidArgumentException('Only open obligations can be processed.');
            }

            if ($obligation->direction !== $expectedDirection) {
                throw new InvalidArgumentException('This method only processes '.$expectedDirection.'s.');
            }

            if (bccomp((string) $paymentData['amount'], (string) $obligation->amount, 2) !== 0) {
                throw new InvalidArgumentException('Payment amount must exactly match the obligation amount.');
            }

            // Overwrite account if provided in paymentData, otherwise use obligation's account
            $accountId = $paymentData['account_id'] ?? $obligation->account_id;

            $account = Account::where('id', $accountId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $transaction = new Transaction([
                'account_id' => $account->id,
                'category_id' => $obligation->category_id,
                'financial_obligation_id' => $obligation->id,
                'type' => $transactionType,
                'amount' => $paymentData['amount'],
                'currency' => $obligation->currency,
                'transaction_date' => $paymentData['transaction_date'] ?? now()->toDateString(),
                'status' => 'completed',
                'description' => ($expectedDirection === 'payable' ? 'Payment for: ' : 'Receipt for: ').$obligation->title,
            ]);
            $transaction->user_id = $userId;
            $transaction->save();

            // Mark obligation as paid
            $obligation->update(['status' => 'paid']);

            return $transaction;
        });
    }

    private function _updateObligation(FinancialObligation $obligation, array $data, string $expectedCategoryType)
    {
        return DB::transaction(function () use ($obligation, $data, $expectedCategoryType) {
            $userId = auth()->id() ?? $data['user_id'] ?? throw new InvalidArgumentException('User ID is required.');

            if ($obligation->user_id !== $userId) {
                throw new AuthorizationException('Unauthorized.');
            }

            if ($obligation->installment_plan_id) {
                // If it belongs to an installment plan, only non-financial fields can be updated
                $obligation->update([
                    'notes' => $data['notes'] ?? $obligation->notes,
                ]);

                return $obligation;
            }

            // Standalone obligation: can update financial fields if open
            if ($obligation->status !== 'open') {
                throw new InvalidArgumentException('Only open obligations can be updated.');
            }

            if (isset($data['account_id'])) {
                $account = Account::where('id', $data['account_id'])->where('user_id', $userId)->firstOrFail();
                $obligation->account_id = $account->id;
            }

            if (isset($data['category_id'])) {
                $category = Category::where('id', $data['category_id'])
                    ->where(function ($q) use ($userId) {
                        $q->where('user_id', $userId)->orWhereNull('user_id');
                    })->firstOrFail();

                if ($category->type !== $expectedCategoryType) {
                    throw new InvalidArgumentException(ucfirst($obligation->direction).'s must use an '.$expectedCategoryType.' category.');
                }
                $obligation->category_id = $category->id;
            }

            $obligation->title = $data['title'] ?? $obligation->title;
            $obligation->amount = $data['amount'] ?? $obligation->amount;
            $obligation->due_date = $data['due_date'] ?? $obligation->due_date;
            $obligation->notes = $data['notes'] ?? $obligation->notes;

            $obligation->save();

            return $obligation;
        });
    }
}
