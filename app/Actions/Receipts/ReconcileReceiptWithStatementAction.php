<?php

namespace App\Actions\Receipts;

use App\Models\ReceiptScan;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReconcileReceiptWithStatementAction
{
    /**
     * Searches for matching bank statement transactions for a given receipt scan.
     * Tolerance: Delta Amount <= 0.05 and Date range <= 4 days.
     *
     * @param ReceiptScan $scan
     * @return Collection<Transaction>
     */
    public function findCandidates(ReceiptScan $scan): Collection
    {
        $amount = (float) $scan->total_amount;
        $purchasedAt = $scan->purchased_at ? Carbon::parse($scan->purchased_at) : now();

        $startDate = (clone $purchasedAt)->subDays(4)->startOfDay();
        $endDate = (clone $purchasedAt)->addDays(4)->endOfDay();

        return Transaction::where('user_id', $scan->user_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->filter(function (Transaction $transaction) use ($amount) {
                $txAmount = abs((float) $transaction->amount);
                // Difference in cents
                return abs($txAmount - $amount) <= 0.05;
            })
            ->values();
    }

    /**
     * Unifies the receipt scan with the target transaction, preventing duplication.
     *
     * @param ReceiptScan $scan
     * @param Transaction $transaction
     * @return bool
     */
    public function match(ReceiptScan $scan, Transaction $transaction): bool
    {
        if ($scan->user_id !== $transaction->user_id) {
            return false;
        }

        $scan->update([
            'transaction_id' => $transaction->id,
            'match_status' => 'matched',
        ]);

        // Enrich transaction description if helpful
        if ($scan->merchant_name && !str_contains($transaction->description, $scan->merchant_name)) {
            $transaction->description = $transaction->description . " ({$scan->merchant_name})";
            $transaction->save();
        }

        return true;
    }
}
