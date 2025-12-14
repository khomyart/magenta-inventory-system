<?php

namespace App\Services\Report;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ItemStatisticsCalculator
{
    /**
     * Get most used items from completed and paid orders for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @param int $limit Maximum number of items to return (default: 10)
     * @param string $sortBy Sort by 'orders_count' or 'total_quantity' (default: 'orders_count')
     * @param int|null $typeId Filter by type_id
     * @param int|null $colorId Filter by color_id
     * @param int|null $sizeId Filter by size_id
     * @return array Array of items with usage statistics
     */
    public function getMostUsedItems(
        Carbon $startDate,
        Carbon $endDate,
        int $limit = 10,
        string $sortBy = 'orders_count',
        ?int $typeId = null,
        ?int $colorId = null,
        ?int $sizeId = null
    ): array {
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

        // Build query with filters
        $query = OrderItem::query()
            ->select([
                'item_id',
                DB::raw('COUNT(DISTINCT order_id) as orders_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(price_per_one_unit * quantity) as total_revenue'),
            ])
            ->whereIn('order_id', $completedOrderIds)
            ->groupBy('item_id');

        // Apply filters through join with items table if any filter is specified
        if ($typeId !== null || $colorId !== null || $sizeId !== null) {
            $query->join('items', 'orders_items.item_id', '=', 'items.id');

            if ($typeId !== null) {
                $query->where('items.type_id', $typeId);
            }

            if ($colorId !== null) {
                $query->where('items.color_id', $colorId);
            }

            if ($sizeId !== null) {
                $query->where('items.size_id', $sizeId);
            }
        }

        // Apply sorting
        if ($sortBy === 'orders_count') {
            $query->orderByDesc('orders_count')->orderByDesc('total_quantity');
        } else {
            $query->orderByDesc('total_quantity')->orderByDesc('orders_count');
        }

        // Get items statistics
        $itemsStats = $query
            ->limit($limit)
            ->get();

        // Load item details with relationships
        $result = [];
        foreach ($itemsStats as $stat) {
            $item = Item::with(['type', 'color', 'size'])->find($stat->item_id);

            if (!$item) {
                continue;
            }

            $result[] = [
                'item_id' => $stat->item_id,
                'item_article' => $item->article,
                'item_title' => $item->title,
                'item_type' => $item->type?->name ?? null,
                'item_color' => $item->color?->description ?? null,
                'item_size' => $item->size?->value ?? null,
                'orders_count' => (int) $stat->orders_count,
                'total_quantity' => (float) $stat->total_quantity,
                'total_revenue' => round((float) $stat->total_revenue, 2),
            ];
        }

        return $result;
    }

    /**
     * Get total items used count for the specified period
     *
     * @param Carbon $startDate Start date of the period
     * @param Carbon $endDate End date of the period
     * @param int|null $typeId Filter by type_id
     * @param int|null $colorId Filter by color_id
     * @param int|null $sizeId Filter by size_id
     * @return int Total unique items used
     */
    public function getTotalItemsUsed(
        Carbon $startDate,
        Carbon $endDate,
        ?int $typeId = null,
        ?int $colorId = null,
        ?int $sizeId = null
    ): int {
        $completedOrderIds = Order::query()
            ->where('status', 'completed')
            ->whereNotNull('fully_payed_at')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->pluck('id');

        if ($completedOrderIds->isEmpty()) {
            return 0;
        }

        $query = OrderItem::query()
            ->whereIn('order_id', $completedOrderIds);

        // Apply filters through join with items table if any filter is specified
        if ($typeId !== null || $colorId !== null || $sizeId !== null) {
            $query->join('items', 'orders_items.item_id', '=', 'items.id');

            if ($typeId !== null) {
                $query->where('items.type_id', $typeId);
            }

            if ($colorId !== null) {
                $query->where('items.color_id', $colorId);
            }

            if ($sizeId !== null) {
                $query->where('items.size_id', $sizeId);
            }
        }

        return $query
            ->distinct('item_id')
            ->count('item_id');
    }
}
