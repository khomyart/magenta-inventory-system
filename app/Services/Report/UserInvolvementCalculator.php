<?php

namespace App\Services\Report;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class UserInvolvementCalculator
{
    /**
     * Involvement level percentages
     */
    private const LEVEL_1_PERCENTAGE = 8; // Full involvement
    private const LEVEL_2_PERCENTAGE = 5; // Partial involvement
    private const LEVEL_3_PERCENTAGE = 3; // Tangent involvement

    /**
     * Calculate user involvement statistics for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array Array of user involvement data
     */
    public function calculateUserInvolvement(Carbon $startDate, Carbon $endDate): array
    {
        $orders = $this->getCompletedAndPaidOrders($startDate, $endDate);

        $userInvolvement = [];

        foreach ($orders as $order) {
            // Level 1 user (8%)
            if ($order->involvement_level_1_user_id) {
                $userId = $order->involvement_level_1_user_id;
                $this->addUserInvolvement(
                    $userInvolvement,
                    $userId,
                    $order,
                    1,
                    self::LEVEL_1_PERCENTAGE
                );
            }

            // Level 2 user (5%)
            if ($order->involvement_level_2_user_id) {
                $userId = $order->involvement_level_2_user_id;
                $this->addUserInvolvement(
                    $userInvolvement,
                    $userId,
                    $order,
                    2,
                    self::LEVEL_2_PERCENTAGE
                );
            }

            // Level 3 user (3%)
            if ($order->involvement_level_3_user_id) {
                $userId = $order->involvement_level_3_user_id;
                $this->addUserInvolvement(
                    $userInvolvement,
                    $userId,
                    $order,
                    3,
                    self::LEVEL_3_PERCENTAGE
                );
            }
        }

        // Sort by earnings descending
        usort($userInvolvement, function ($a, $b) {
            return $b['earnings'] <=> $a['earnings'];
        });

        return $userInvolvement;
    }

    /**
     * Get earnings for a specific user
     *
     * @param User $user User instance
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return array User earnings data
     */
    public function getUserEarnings(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $orders = $this->getCompletedAndPaidOrders($startDate, $endDate);

        $totalEarnings = 0;
        $ordersCount = 0;
        $totalOrdersAmount = 0;

        foreach ($orders as $order) {
            $orderAmount = $this->calculateOrderTotalPayment($order);

            if ($order->involvement_level_1_user_id === $user->id) {
                $totalEarnings += $orderAmount * (self::LEVEL_1_PERCENTAGE / 100);
                $totalOrdersAmount += $orderAmount;
                $ordersCount++;
            }

            if ($order->involvement_level_2_user_id === $user->id) {
                $totalEarnings += $orderAmount * (self::LEVEL_2_PERCENTAGE / 100);
                $totalOrdersAmount += $orderAmount;
                $ordersCount++;
            }

            if ($order->involvement_level_3_user_id === $user->id) {
                $totalEarnings += $orderAmount * (self::LEVEL_3_PERCENTAGE / 100);
                $totalOrdersAmount += $orderAmount;
                $ordersCount++;
            }
        }

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'orders_count' => $ordersCount,
            'total_orders_amount' => round($totalOrdersAmount, 2),
            'earnings' => round($totalEarnings, 2),
        ];
    }

    /**
     * Add user involvement data to the array
     *
     * @param array &$userInvolvement User involvement array (passed by reference)
     * @param int $userId User ID
     * @param Order $order Order instance
     * @param int $level Involvement level (1, 2, or 3)
     * @param float $percentage Involvement percentage
     *
     * @return void
     */
    private function addUserInvolvement(array &$userInvolvement, int $userId, Order $order, int $level, float $percentage): void
    {
        // Use combination of userId and level as key to track each level separately
        $key = $userId . '_' . $level;

        if (! isset($userInvolvement[$key])) {
            $user = User::find($userId);
            $userInvolvement[$key] = [
                'user_id' => $userId,
                'user_name' => $user ? $user->name : 'Unknown',
                'involvement_level' => $level,
                'involvement_percentage' => $percentage,
                'orders_count' => 0,
                'total_orders_amount' => 0,
                'earnings' => 0,
            ];
        }

        $orderAmount = $this->calculateOrderTotalPayment($order);
        $earnings = $orderAmount * ($percentage / 100);

        $userInvolvement[$key]['orders_count']++;
        $userInvolvement[$key]['total_orders_amount'] += $orderAmount;
        $userInvolvement[$key]['earnings'] += $earnings;

        // Round for display
        $userInvolvement[$key]['total_orders_amount'] = round($userInvolvement[$key]['total_orders_amount'], 2);
        $userInvolvement[$key]['earnings'] = round($userInvolvement[$key]['earnings'], 2);
    }

    /**
     * Calculate total payment amount for a single order
     *
     * @param Order $order Order instance
     *
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

    /**
     * Get orders that are fully paid and completed
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     *
     * @return \Illuminate\Support\Collection Collection of Order models
     */
    private function getCompletedAndPaidOrders(Carbon $startDate, Carbon $endDate)
    {
        return Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereRaw(
                "(
                        amount_of_advance_payment_on_card + amount_of_advance_payment_via_terminal + amount_of_advance_payment_as_cash
                        + amount_of_final_payment_on_card + amount_of_final_payment_via_terminal + amount_of_final_payment_as_cash
                    ) >= total_price",
            )
            ->get();
    }
}
