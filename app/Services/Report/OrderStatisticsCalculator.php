<?php

namespace App\Services\Report;

use App\Models\Order;
use Carbon\Carbon;

class OrderStatisticsCalculator
{
    /**
     * Get orders grouped by status for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Orders count grouped by status
     */
    public function getOrdersByStatus(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::query()
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $statusCounts = [
            'pending' => 0,
            'confirmed' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($orders as $order) {
            if (isset($statusCounts[$order->status])) {
                $statusCounts[$order->status]++;
            }
        }

        return $statusCounts;
    }

    /**
     * Get total count of orders for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return int Total orders count
     */
    public function getTotalOrdersCount(Carbon $startDate, Carbon $endDate): int
    {
        return Order::query()
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->count();
    }

    /**
     * Get orders distribution statistics
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Array with detailed order statistics
     */
    public function getOrdersDistribution(Carbon $startDate, Carbon $endDate): array
    {
        $ordersByStatus = $this->getOrdersByStatus($startDate, $endDate);
        $totalCount = $this->getTotalOrdersCount($startDate, $endDate);

        // Calculate completed and paid orders count
        $completedAndPaidCount = Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at')
            ->whereRaw(
                "(
                        amount_of_advance_payment_on_card + amount_of_advance_payment_via_terminal + amount_of_advance_payment_as_cash
                        + amount_of_final_payment_on_card + amount_of_final_payment_via_terminal + amount_of_final_payment_as_cash
                    ) >= total_price",
            )
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->count();

        return [
            'total' => $totalCount,
            'by_status' => $ordersByStatus,
            'completed_and_paid' => $completedAndPaidCount,
        ];
    }

    /**
     * Calculate average order value for completed and paid orders
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return float Average order value
     */
    public function calculateAverageOrderValue(Carbon $startDate, Carbon $endDate): float
    {
        $orders = Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        $totalRevenue = 0;
        foreach ($orders as $order) {
            $totalRevenue += $this->calculateOrderTotalPayment($order);
        }

        return round($totalRevenue / $orders->count(), 2);
    }

    /**
     * Calculate total payment amount for a single order
     *
     * @param Order $order Order instance
     * @return float Total payment amount
     */
    private function calculateOrderTotalPayment(Order $order): float
    {
        $total = 0;

        // Advance payments
        $total += $order->amount_of_advance_payment_on_card ?? 0;
        $total += $order->amount_of_advance_payment_via_terminal ?? 0;
        $total += $order->amount_of_advance_payment_as_cash ?? 0;

        // Final payments
        $total += $order->amount_of_final_payment_on_card ?? 0;
        $total += $order->amount_of_final_payment_via_terminal ?? 0;
        $total += $order->amount_of_final_payment_as_cash ?? 0;

        return $total;
    }
}
