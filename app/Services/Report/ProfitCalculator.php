<?php

namespace App\Services\Report;

use Carbon\Carbon;

class ProfitCalculator
{
    /**
     * Calculate profit from revenue and expenses arrays
     * Profit = Revenue - Expenses
     *
     * @param array $revenue Revenue data with 'total' and 'by_day' keys
     * @param array $expenses Expenses data with 'total' and 'by_day' keys
     * @return array Array with 'total' and 'by_day' profit data
     */
    public function calculateProfit(array $revenue, array $expenses): array
    {
        $totalProfit = $revenue['total'] - $expenses['total'];
        $profitByDay = [];

        // Merge revenue and expenses by day
        $allDates = array_unique(array_merge(
            array_keys($revenue['by_day']),
            array_keys($expenses['by_day'])
        ));

        foreach ($allDates as $date) {
            $dayRevenue = $revenue['by_day'][$date] ?? 0;
            $dayExpenses = $expenses['by_day'][$date] ?? 0;
            $profitByDay[$date] = $dayRevenue - $dayExpenses;
        }

        // Sort by date
        ksort($profitByDay);

        return [
            'total' => $totalProfit,
            'by_day' => $profitByDay,
        ];
    }

    /**
     * Get profit grouped by day
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @param array $revenueByDay Revenue grouped by day ['Y-m-d' => amount]
     * @param array $expensesByDay Expenses grouped by day ['Y-m-d' => amount]
     * @return array Profit grouped by day ['Y-m-d' => amount]
     */
    public function getProfitByDay(Carbon $startDate, Carbon $endDate, array $revenueByDay, array $expensesByDay): array
    {
        $profitByDay = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayRevenue = $revenueByDay[$dateKey] ?? 0;
            $dayExpenses = $expensesByDay[$dateKey] ?? 0;
            $profitByDay[$dateKey] = $dayRevenue - $dayExpenses;
            $currentDate->addDay();
        }

        return $profitByDay;
    }

    /**
     * Calculate profit margin percentage
     * Profit Margin = (Profit / Revenue) * 100
     *
     * @param float $profit Total profit amount
     * @param float $revenue Total revenue amount
     * @return float Profit margin percentage (0-100)
     */
    public function calculateProfitMargin(float $profit, float $revenue): float
    {
        if ($revenue == 0) {
            return 0;
        }

        return round(($profit / $revenue) * 100, 2);
    }
}
