<?php

namespace App\Services\Report;

use App\Models\Order;
use App\Models\OrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceStatisticsCalculator
{
    /**
     * Get most used services from completed and paid orders for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @param int $limit Maximum number of services to return (default: 10)
     * @param string $sortBy Sort by 'orders_count' or 'total_quantity' (default: 'orders_count')
     * @return array Array of services with usage statistics
     */
    public function getMostUsedServices(Carbon $startDate, Carbon $endDate, int $limit = 10, string $sortBy = 'orders_count'): array
    {
        // Get IDs of completed and paid orders in the specified period
        $completedOrderIds = Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->pluck('id');

        if ($completedOrderIds->isEmpty()) {
            return [];
        }

        // Validate sortBy parameter
        $validSortFields = ['orders_count', 'total_quantity'];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'orders_count';
        }

        // Build query
        $query = OrderService::query()
            ->select([
                'service_id',
                DB::raw('COUNT(DISTINCT order_id) as orders_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(price_per_one_unit * quantity) as total_revenue'),
            ])
            ->whereIn('order_id', $completedOrderIds)
            ->groupBy('service_id');

        // Apply sorting
        if ($sortBy === 'orders_count') {
            $query->orderByDesc('orders_count')->orderByDesc('total_quantity');
        } else {
            $query->orderByDesc('total_quantity')->orderByDesc('orders_count');
        }

        // Get services statistics from completed orders
        $servicesStats = $query
            ->limit($limit)
            ->with('service')
            ->get();

        $result = [];
        foreach ($servicesStats as $stat) {
            if (!$stat->service) {
                continue;
            }

            $result[] = [
                'service_id' => $stat->service_id,
                'service_title' => $stat->service->title,
                'orders_count' => (int) $stat->orders_count,
                'total_quantity' => (float) $stat->total_quantity,
                'total_revenue' => round((float) $stat->total_revenue, 2),
            ];
        }

        return $result;
    }

    /**
     * Get total services usage count for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @return int Total unique services used
     */
    public function getTotalServicesUsed(Carbon $startDate, Carbon $endDate): int
    {
        $completedOrderIds = Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->pluck('id');

        if ($completedOrderIds->isEmpty()) {
            return 0;
        }

        return OrderService::query()
            ->whereIn('order_id', $completedOrderIds)
            ->distinct('service_id')
            ->count('service_id');
    }
}
