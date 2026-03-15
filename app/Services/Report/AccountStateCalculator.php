<?php

namespace App\Services\Report;

use App\Models\BusinessAccountTransaction;
use App\Models\Order;
use App\Models\Spend;

class AccountStateCalculator
{
    /**
     * Calculate current account state (all time)
     *
     * @return array Account state by payment method
     */
    public function calculateCurrentState(): array
    {
        // 1. Revenue from completed and paid orders
        $ordersQuery = Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at');

        $orderRevenue = [
            'card' => $ordersQuery->sum('amount_of_advance_payment_on_card') + $ordersQuery->sum('amount_of_final_payment_on_card'),
            'terminal' => $ordersQuery->sum('amount_of_advance_payment_via_terminal') + $ordersQuery->sum('amount_of_final_payment_via_terminal'),
            'cash' => $ordersQuery->sum('amount_of_advance_payment_as_cash') + $ordersQuery->sum('amount_of_final_payment_as_cash'),
        ];

        // 2. Expenses from Spends
        $spendsQuery = Spend::query();
        $spendExpenses = [
            'card' => $spendsQuery->sum('amount_on_card'),
            'terminal' => $spendsQuery->sum('amount_via_terminal'),
            'cash' => $spendsQuery->sum('amount_as_cash'),
        ];

        // 3. Transactions (Income)
        $incomeTransactionsQuery = BusinessAccountTransaction::query()->where('type', 'income');
        $transactionIncome = [
            'card' => $incomeTransactionsQuery->sum('amount_on_card'),
            'terminal' => $incomeTransactionsQuery->sum('amount_via_terminal'),
            'cash' => $incomeTransactionsQuery->sum('amount_as_cash'),
        ];

        // 4. Transactions (Outcome)
        $outcomeTransactionsQuery = BusinessAccountTransaction::query()->where('type', 'outcome');
        $transactionOutcome = [
            'card' => $outcomeTransactionsQuery->sum('amount_on_card'),
            'terminal' => $outcomeTransactionsQuery->sum('amount_via_terminal'),
            'cash' => $outcomeTransactionsQuery->sum('amount_as_cash'),
        ];

        $state = [
            'card' => $orderRevenue['card'] + $transactionIncome['card'] - $spendExpenses['card'] - $transactionOutcome['card'],
            'terminal' => $orderRevenue['terminal'] + $transactionIncome['terminal'] - $spendExpenses['terminal'] - $transactionOutcome['terminal'],
            'cash' => $orderRevenue['cash'] + $transactionIncome['cash'] - $spendExpenses['cash'] - $transactionOutcome['cash'],
        ];

        $state['total'] = $state['card'] + $state['terminal'] + $state['cash'];

        return $state;
    }
}
