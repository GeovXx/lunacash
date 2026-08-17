<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\CreditCardInvoice;
use App\Models\FinancialGoal;
use App\Models\FinancialObligation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Retrieves a combined list of persistent and ephemeral notifications for the given user.
     * Persistent notifications are limited to avoid massive queries.
     */
    public function getNotifications(User $user, int $persistentLimit = 15): array
    {
        $ephemeral = $this->getEphemeralNotifications($user);

        $persistent = Notification::where('user_id', $user->id)
            ->orderBy('is_read', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit($persistentLimit)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->type,
                    'message' => $notif->message,
                    'data' => $notif->data,
                    'is_read' => $notif->is_read,
                    'created_at' => $notif->created_at,
                    'is_ephemeral' => false,
                ];
            })
            ->toArray();

        // Combine prioritizing ephemeral unread-like alerts first
        return array_merge($ephemeral, $persistent);
    }

    /**
     * Gets only the unread count of persistent notifications.
     */
    public function getUnreadPersistentCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Checks if there are any ephemeral alerts that need attention.
     */
    public function hasEphemeralAlerts(User $user): bool
    {
        $cacheKey = "user_{$user->id}_has_ephemeral_alerts";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user) {
            return count($this->getEphemeralNotifications($user)) > 0;
        });
    }

    /**
     * Marks a persistent notification as read securely.
     */
    public function markAsRead(string $notificationId, User $user): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notification && ! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Creates a persistent notification with concurrency protection (Cache::lock) and deduplication.
     */
    public function createPersistent(User $user, string $type, string $message, array $data = []): ?Notification
    {
        // Deterministic signature based on data and type for today's context (can be adjusted to include ID of entity)
        $signature = md5($user->id.'_'.$type.'_'.json_encode($data));

        // Cache lock to prevent concurrent requests creating the exact same notification simultaneously
        $lockKey = "notif_lock_{$signature}";

        return Cache::lock($lockKey, 10)->get(function () use ($user, $type, $message, $data, $signature) {
            // Check existence based on data signature
            // If data is used exactly for the signature, we can check if a recent unread notification with same type and data exists
            // Since we can't easily query JSON in sqlite, we use the signature logic in the design

            // Actually, to make it compatible with Hostinger (MySQL/MariaDB) and tests (SQLite),
            // a simple approach is to include a 'signature' key in the json data to query against,
            // but json queries in SQLite for WHERE clauses can be tricky.
            // Let's rely on retrieving the latest 5 unread notifications of this type and comparing in memory,
            // since we don't want to create migrations for a `signature` column.

            $recentUnread = Notification::where('user_id', $user->id)
                ->where('type', $type)
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($recentUnread as $notif) {
                // If it already exists and is unread, don't duplicate
                if (isset($notif->data['signature']) && $notif->data['signature'] === $signature) {
                    return $notif;
                }
            }

            $data['signature'] = $signature; // Embed signature for future deduplication checks

            $notification = new Notification([
                'type' => $type,
                'message' => $message,
                'data' => $data,
                'is_read' => false,
            ]);
            $notification->id = Str::uuid()->toString();
            $notification->user_id = $user->id;
            $notification->save();

            return $notification;
        });
    }

    /**
     * Computes on-the-fly ephemeral notifications. No database persistence here.
     */
    private function getEphemeralNotifications(User $user): array
    {
        $alerts = [];
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // 1. Obligations (Contas)
        $obligations = FinancialObligation::where('user_id', $user->id)
            ->whereIn('status', ['open', 'overdue'])
            ->get();

        foreach ($obligations as $obligation) {
            $dueDate = Carbon::parse($obligation->due_date);

            if ($dueDate->isBefore($today)) {
                $alerts[] = [
                    'id' => 'eph_obs_overdue_'.$obligation->id,
                    'type' => 'overdue',
                    'message' => "Conta atrasada: {$obligation->title}",
                    'data' => ['obligation_id' => $obligation->id, 'amount' => $obligation->amount],
                    'is_read' => false,
                    'created_at' => now(),
                    'is_ephemeral' => true,
                ];
            } elseif ($dueDate->isBetween($today, $tomorrow)) {
                $alerts[] = [
                    'id' => 'eph_obs_soon_'.$obligation->id,
                    'type' => 'due_soon',
                    'message' => "Vencendo em breve: {$obligation->title}",
                    'data' => ['obligation_id' => $obligation->id, 'amount' => $obligation->amount],
                    'is_read' => false,
                    'created_at' => now(),
                    'is_ephemeral' => true,
                ];
            }
        }

        // 2. Invoices (Faturas)
        $invoices = CreditCardInvoice::where('user_id', $user->id)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', $today->copy()->addDays(3))
            ->get();

        foreach ($invoices as $invoice) {
            $alerts[] = [
                'id' => 'eph_inv_soon_'.$invoice->id,
                'type' => 'invoice_due_soon',
                'message' => 'Fatura próxima ao vencimento',
                'data' => ['invoice_id' => $invoice->id, 'amount' => $invoice->total_amount],
                'is_read' => false,
                'created_at' => now(),
                'is_ephemeral' => true,
            ];
        }

        // 3. Goals (Metas)
        $goals = FinancialGoal::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($goals as $goal) {
            if ($goal->current_amount >= $goal->target_amount) {
                $alerts[] = [
                    'id' => 'eph_goal_comp_'.$goal->id,
                    'type' => 'goal_completed',
                    'message' => "Meta atingida: {$goal->name}!",
                    'data' => ['goal_id' => $goal->id],
                    'is_read' => false,
                    'created_at' => now(),
                    'is_ephemeral' => true,
                ];
            } elseif ($goal->target_date) {
                $targetDate = Carbon::parse($goal->target_date);
                if ($targetDate->isBetween($today, $today->copy()->addDays(7))) {
                    $alerts[] = [
                        'id' => 'eph_goal_soon_'.$goal->id,
                        'type' => 'goal_due_soon',
                        'message' => "O prazo da meta '{$goal->name}' está acabando",
                        'data' => ['goal_id' => $goal->id],
                        'is_read' => false,
                        'created_at' => now(),
                        'is_ephemeral' => true,
                    ];
                }
            }
        }

        // 4. Budgets (Orçamentos)
        $budgetService = app(BudgetService::class);
        $budgets = Budget::where('user_id', $user->id)->where('status', 'active')->get();

        foreach ($budgets as $budget) {
            $progress = $budgetService->getBudgetProgress($budget);
            $percentage = $progress['percentage_used'] ?? 0;

            if ($percentage > 100) {
                $alerts[] = [
                    'id' => 'eph_bdg_excd_'.$budget->id,
                    'type' => 'budget_exceeded',
                    'message' => "Orçamento estourado: {$budget->name}",
                    'data' => ['budget_id' => $budget->id, 'percentage' => $percentage],
                    'is_read' => false,
                    'created_at' => now(),
                    'is_ephemeral' => true,
                ];
            } elseif ($percentage >= 80) {
                $alerts[] = [
                    'id' => 'eph_bdg_warn_'.$budget->id,
                    'type' => 'budget_warning',
                    'message' => "Atenção: orçamento {$budget->name} próximo do limite ({$percentage}%)",
                    'data' => ['budget_id' => $budget->id, 'percentage' => $percentage],
                    'is_read' => false,
                    'created_at' => now(),
                    'is_ephemeral' => true,
                ];
            }
        }

        return $alerts;
    }
}
