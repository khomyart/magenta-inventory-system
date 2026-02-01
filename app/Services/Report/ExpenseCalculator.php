<?php

namespace App\Services\Report;

use App\Models\Spend;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExpenseCalculator
{
    /**
     * Calculate total expenses for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Array with 'total', 'breakdown', 'by_day', and 'spends_count' keys
     */
    public function calculateExpenses(Carbon $startDate, Carbon $endDate): array
    {
        $spends = $this->getSpends($startDate, $endDate);

        $totalExpenses = 0;
        $breakdown = [
            'cash' => 0,
            'card' => 0,
            'terminal' => 0,
        ];
        $expensesByDay = [];

        foreach ($spends as $spend) {
            $totalExpenses += $spend->total_price;

            $breakdown['cash'] += $spend->amount_as_cash ?? 0;
            $breakdown['card'] += $spend->amount_on_card ?? 0;
            $breakdown['terminal'] += $spend->amount_via_terminal ?? 0;

            // Group expenses by day (using happened_at date)
            $date = Carbon::parse($spend->happened_at)->format('Y-m-d');
            if (! isset($expensesByDay[$date])) {
                $expensesByDay[$date] = 0;
            }
            $expensesByDay[$date] += $spend->total_price;
        }

        return [
            'total' => $totalExpenses,
            'breakdown' => $breakdown,
            'by_day' => $expensesByDay,
            'spends_count' => $spends->count(),
        ];
    }

    /**
     * Get expenses grouped by day
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Expenses grouped by day ['Y-m-d' => amount]
     */
    public function getExpensesByDay(Carbon $startDate, Carbon $endDate): array
    {
        $result = $this->calculateExpenses($startDate, $endDate);

        // Fill missing days with zeros
        $filledData = $this->fillMissingDays($result['by_day'], $startDate, $endDate);

        return $filledData;
    }

    /**
     * Get total expenses for the period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return float Total expenses amount
     */
    public function getTotalExpenses(Carbon $startDate, Carbon $endDate): float
    {
        $result = $this->calculateExpenses($startDate, $endDate);

        return $result['total'];
    }

    /**
     * Get spends for the specified period
     * All spends are included (both visible and hidden)
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return Collection Collection of Spend models
     */
    private function getSpends(Carbon $startDate, Carbon $endDate): Collection
    {
        return Spend::query()
            ->whereBetween('happened_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();
    }

    /**
     * Fill missing days in the array with zeros
     *
     * @param array $data Array with dates as keys ['Y-m-d' => amount]
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Array with all dates filled ['Y-m-d' => amount]
     */
    private function fillMissingDays(array $data, Carbon $startDate, Carbon $endDate): array
    {
        $result = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $result[$dateKey] = $data[$dateKey] ?? 0;
            $currentDate->addDay();
        }

        return $result;
    }
}
