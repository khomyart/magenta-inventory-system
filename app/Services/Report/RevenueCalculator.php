<?php

namespace App\Services\Report;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RevenueCalculator
{
    /**
     * Calculate total revenue for the specified period
     * Only fully paid and completed orders are included
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Array with 'total', 'breakdown', 'by_day', and 'orders_count' keys
     */
    public function calculateRevenue(Carbon $startDate, Carbon $endDate): array
    {
        $orders = $this->getCompletedAndPaidOrders($startDate, $endDate);

        $totalRevenue = 0;
        $breakdown = [
            'cash' => 0,
            'card' => 0,
            'terminal' => 0,
        ];
        $revenueByDay = [];

        foreach ($orders as $order) {
            $orderRevenue = $this->calculateOrderRevenue($order);
            $totalRevenue += $orderRevenue;

            $breakdown['cash'] += ($order->amount_of_advance_payment_as_cash ?? 0) + ($order->amount_of_final_payment_as_cash ?? 0);
            $breakdown['card'] += ($order->amount_of_advance_payment_on_card ?? 0) + ($order->amount_of_final_payment_on_card ?? 0);
            $breakdown['terminal'] += ($order->amount_of_advance_payment_via_terminal ?? 0) + ($order->amount_of_final_payment_via_terminal ?? 0);

            // Group revenue by day (using completed_at date)
            $date = Carbon::parse($order->completed_at)->format('Y-m-d');
            if (! isset($revenueByDay[$date])) {
                $revenueByDay[$date] = 0;
            }
            $revenueByDay[$date] += $orderRevenue;
        }

        return [
            'total' => $totalRevenue,
            'breakdown' => $breakdown,
            'by_day' => $revenueByDay,
            'orders_count' => $orders->count(),
        ];
    }

    /**
     * Get revenue grouped by day
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Revenue grouped by day ['Y-m-d' => amount]
     */
    public function getRevenueByDay(Carbon $startDate, Carbon $endDate): array
    {
        $result = $this->calculateRevenue($startDate, $endDate);

        // Fill missing days with zeros
        $filledData = $this->fillMissingDays($result['by_day'], $startDate, $endDate);

        return $filledData;
    }

    /**
     * Get total revenue for the period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return float Total revenue amount
     */
    public function getTotalRevenue(Carbon $startDate, Carbon $endDate): float
    {
        $result = $this->calculateRevenue($startDate, $endDate);

        return $result['total'];
    }

    /**
     * Calculate revenue from a single order
     * Includes all payment types: advance + final payments
     *
     * @param Order $order Order instance
     * @return float Total revenue from the order
     */
    private function calculateOrderRevenue(Order $order): float
    {
        $revenue = 0;

        // Advance payments
        $revenue += $order->amount_of_advance_payment_on_card ?? 0;
        $revenue += $order->amount_of_advance_payment_via_terminal ?? 0;
        $revenue += $order->amount_of_advance_payment_as_cash ?? 0;

        // Final payments
        $revenue += $order->amount_of_final_payment_on_card ?? 0;
        $revenue += $order->amount_of_final_payment_via_terminal ?? 0;
        $revenue += $order->amount_of_final_payment_as_cash ?? 0;

        return $revenue;
    }

    /**
     * Get orders that are fully paid and completed
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return Collection Collection of Order models
     */
    private function getCompletedAndPaidOrders(Carbon $startDate, Carbon $endDate): Collection
    {
        return Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
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
