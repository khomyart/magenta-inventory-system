<?php

namespace App\Services\Report;

use App\Models\BusinessAccountTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TransactionCalculator
{
    /**
     * Calculate total transactions for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Array with income and outcome data
     */
    public function calculateTransactions(Carbon $startDate, Carbon $endDate): array
    {
        $transactions = $this->getTransactions($startDate, $endDate);

        $income = [
            'total' => 0,
            'cash' => 0,
            'card' => 0,
            'terminal' => 0,
        ];

        $outcome = [
            'total' => 0,
            'cash' => 0,
            'card' => 0,
            'terminal' => 0,
        ];

        foreach ($transactions as $transaction) {
            $target = $transaction->type === 'income' ? $income : $outcome;
            
            $income_outcome_key = $transaction->type === 'income' ? 'income' : 'outcome';
            if ($income_outcome_key === 'income') {
                $income['total'] += $transaction->total_price;
                $income['cash'] += $transaction->amount_as_cash ?? 0;
                $income['card'] += $transaction->amount_on_card ?? 0;
                $income['terminal'] += $transaction->amount_via_terminal ?? 0;
            } else {
                $outcome['total'] += $transaction->total_price;
                $outcome['cash'] += $transaction->amount_as_cash ?? 0;
                $outcome['card'] += $transaction->amount_on_card ?? 0;
                $outcome['terminal'] += $transaction->amount_via_terminal ?? 0;
            }
        }

        return [
            'income' => $income,
            'outcome' => $outcome,
            'count' => $transactions->count(),
        ];
    }

    /**
     * Get transactions for the specified period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    private function getTransactions(Carbon $startDate, Carbon $endDate): Collection
    {
        return BusinessAccountTransaction::query()
            ->whereBetween('happened_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();
    }
}
