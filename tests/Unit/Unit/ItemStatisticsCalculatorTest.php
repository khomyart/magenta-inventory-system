<?php

namespace Tests\Unit\Unit;

use App\Models\Color;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Size;
use App\Models\Type;
use App\Services\Report\ItemStatisticsCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemStatisticsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ItemStatisticsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ItemStatisticsCalculator();
    }

    /**
     * Test getting most used items sorted by orders count
     */
    public function test_get_most_used_items_by_orders_count(): void
    {
        $type = Type::factory()->create(['name' => 'T-shirt']);

        $item1 = Item::factory()->create(['title' => 'Item A', 'type_id' => $type->id]);
        $item2 = Item::factory()->create(['title' => 'Item B', 'type_id' => $type->id]);

        // Item A used in 3 orders
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'item_id' => $item1->id,
                'quantity' => 1,
                'price_per_one_unit' => 100,
            ]);
        }

        // Item B used in 2 orders
        for ($i = 0; $i < 2; $i++) {
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'item_id' => $item2->id,
                'quantity' => 1,
                'price_per_one_unit' => 200,
            ]);
        }

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate, 10, 'orders_count');

        $this->assertCount(2, $result);
        $this->assertEquals('Item A', $result[0]['item_title']);
        $this->assertEquals(3, $result[0]['orders_count']);
        $this->assertEquals('Item B', $result[1]['item_title']);
        $this->assertEquals(2, $result[1]['orders_count']);
    }

    /**
     * Test getting most used items sorted by total quantity
     */
    public function test_get_most_used_items_by_total_quantity(): void
    {
        $type = Type::factory()->create();

        $item1 = Item::factory()->create(['type_id' => $type->id]);
        $item2 = Item::factory()->create(['type_id' => $type->id]);

        // Item 1: 2 orders, total quantity = 15
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'item_id' => $item1->id,
            'quantity' => 10,
            'price_per_one_unit' => 100,
        ]);

        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'item_id' => $item1->id,
            'quantity' => 5,
            'price_per_one_unit' => 100,
        ]);

        // Item 2: 3 orders, total quantity = 6
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'item_id' => $item2->id,
                'quantity' => 2,
                'price_per_one_unit' => 200,
            ]);
        }

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate, 10, 'total_quantity');

        $this->assertCount(2, $result);
        // Item 1 should be first (15 total quantity)
        $this->assertEquals($item1->id, $result[0]['item_id']);
        $this->assertEquals(15, $result[0]['total_quantity']);

        // Item 2 should be second (6 total quantity)
        $this->assertEquals($item2->id, $result[1]['item_id']);
        $this->assertEquals(6, $result[1]['total_quantity']);
    }

    /**
     * Test filtering by type_id
     */
    public function test_filter_by_type_id(): void
    {
        $type1 = Type::factory()->create(['name' => 'Type 1']);
        $type2 = Type::factory()->create(['name' => 'Type 2']);

        $item1 = Item::factory()->create(['type_id' => $type1->id]);
        $item2 = Item::factory()->create(['type_id' => $type2->id]);

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'item_id' => $item1->id]);

        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'item_id' => $item2->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Filter by type1 - should only return item1
        $result = $this->calculator->getMostUsedItems($startDate, $endDate, 10, 'orders_count', $type1->id);

        $this->assertCount(1, $result);
        $this->assertEquals($item1->id, $result[0]['item_id']);
    }

    /**
     * Test filtering by type_id and color_id
     */
    public function test_filter_by_type_and_color(): void
    {
        $type = Type::factory()->create();
        $colorRed = Color::factory()->create(['description' => 'Red']);
        $colorBlue = Color::factory()->create(['description' => 'Blue']);

        $item1 = Item::factory()->create(['type_id' => $type->id, 'color_id' => $colorRed->id]);
        $item2 = Item::factory()->create(['type_id' => $type->id, 'color_id' => $colorBlue->id]);

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'item_id' => $item1->id]);

        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'item_id' => $item2->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Filter by type and red color - should only return item1
        $result = $this->calculator->getMostUsedItems(
            $startDate,
            $endDate,
            10,
            'orders_count',
            $type->id,
            $colorRed->id
        );

        $this->assertCount(1, $result);
        $this->assertEquals($item1->id, $result[0]['item_id']);
        $this->assertEquals('Red', $result[0]['item_color']);
    }

    /**
     * Test filtering by type_id and size_id
     */
    public function test_filter_by_type_and_size(): void
    {
        $type = Type::factory()->create();
        $sizeM = Size::factory()->create(['value' => 'M']);
        $sizeL = Size::factory()->create(['value' => 'L']);

        $item1 = Item::factory()->create(['type_id' => $type->id, 'size_id' => $sizeM->id]);
        $item2 = Item::factory()->create(['type_id' => $type->id, 'size_id' => $sizeL->id]);

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'item_id' => $item1->id]);

        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'item_id' => $item2->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Filter by type and size M - should only return item1
        $result = $this->calculator->getMostUsedItems(
            $startDate,
            $endDate,
            10,
            'orders_count',
            $type->id,
            null,
            $sizeM->id
        );

        $this->assertCount(1, $result);
        $this->assertEquals($item1->id, $result[0]['item_id']);
        $this->assertEquals('M', $result[0]['item_size']);
    }

    /**
     * Test filtering by type_id, color_id, and size_id
     */
    public function test_filter_by_type_color_and_size(): void
    {
        $type = Type::factory()->create();
        $color = Color::factory()->create(['description' => 'Red']);
        $size = Size::factory()->create(['value' => 'M']);

        $item1 = Item::factory()->create([
            'type_id' => $type->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
        ]);

        $item2 = Item::factory()->create([
            'type_id' => $type->id,
            'color_id' => $color->id,
            'size_id' => null, // Different size
        ]);

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'item_id' => $item1->id]);

        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'item_id' => $item2->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Filter by type, color, and size - should only return item1
        $result = $this->calculator->getMostUsedItems(
            $startDate,
            $endDate,
            10,
            'orders_count',
            $type->id,
            $color->id,
            $size->id
        );

        $this->assertCount(1, $result);
        $this->assertEquals($item1->id, $result[0]['item_id']);
        $this->assertEquals('Red', $result[0]['item_color']);
        $this->assertEquals('M', $result[0]['item_size']);
    }

    /**
     * Test that only completed and paid orders are included
     */
    public function test_only_completed_and_paid_orders_included(): void
    {
        $type = Type::factory()->create();
        $item = Item::factory()->create(['type_id' => $type->id]);

        // Completed and paid - should be included
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'item_id' => $item->id]);

        // Completed but not paid - should NOT be included
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => null,
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'item_id' => $item->id]);

        // Pending - should NOT be included
        $order3 = Order::factory()->create([
            'status' => 'pending',
        ]);
        OrderItem::factory()->create(['order_id' => $order3->id, 'item_id' => $item->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['orders_count']);
    }

    /**
     * Test date range filtering
     */
    public function test_filter_by_date_range(): void
    {
        $type = Type::factory()->create();
        $item = Item::factory()->create(['type_id' => $type->id]);

        // Within range
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'item_id' => $item->id]);

        // Outside range (too old)
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => Carbon::now()->subDays(10),
            'completed_at' => Carbon::now()->subDays(10),
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'item_id' => $item->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['orders_count']);
    }

    /**
     * Test limit parameter
     */
    public function test_limit_parameter(): void
    {
        $type = Type::factory()->create();

        // Create 15 items
        for ($i = 1; $i <= 15; $i++) {
            $item = Item::factory()->create(['type_id' => $type->id]);
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item->id]);
        }

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Test with limit 5
        $result = $this->calculator->getMostUsedItems($startDate, $endDate, 5);
        $this->assertCount(5, $result);

        // Test with limit 10
        $result = $this->calculator->getMostUsedItems($startDate, $endDate, 10);
        $this->assertCount(10, $result);
    }

    /**
     * Test returns empty array when no completed orders
     */
    public function test_returns_empty_array_when_no_completed_orders(): void
    {
        $type = Type::factory()->create();
        $item = Item::factory()->create(['type_id' => $type->id]);
        $order = Order::factory()->create(['status' => 'pending']);
        OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate);

        $this->assertEmpty($result);
    }

    /**
     * Test invalid sortBy defaults to orders_count
     */
    public function test_invalid_sort_by_defaults_to_orders_count(): void
    {
        $type = Type::factory()->create();

        $item1 = Item::factory()->create(['type_id' => $type->id]);
        $item2 = Item::factory()->create(['type_id' => $type->id]);

        // Item 1: 1 order, quantity = 10
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'item_id' => $item1->id,
            'quantity' => 10,
        ]);

        // Item 2: 2 orders, quantity = 4
        for ($i = 0; $i < 2; $i++) {
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'item_id' => $item2->id,
                'quantity' => 2,
            ]);
        }

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate, 10, 'invalid_field');

        // Should be sorted by orders_count (Item 2 first with 2 orders)
        $this->assertEquals($item2->id, $result[0]['item_id']);
        $this->assertEquals(2, $result[0]['orders_count']);
    }

    /**
     * Test revenue calculation
     */
    public function test_revenue_calculation(): void
    {
        $type = Type::factory()->create();
        $item = Item::factory()->create(['type_id' => $type->id]);

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'price_per_one_unit' => 100,
        ]);

        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'item_id' => $item->id,
            'quantity' => 3,
            'price_per_one_unit' => 200,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['orders_count']);
        $this->assertEquals(8, $result[0]['total_quantity']);
        // Total revenue: (5 * 100) + (3 * 200) = 1100
        $this->assertEquals(1100, $result[0]['total_revenue']);
    }

    /**
     * Test getTotalItemsUsed returns correct count
     */
    public function test_get_total_items_used(): void
    {
        $type = Type::factory()->create();

        $item1 = Item::factory()->create(['type_id' => $type->id]);
        $item2 = Item::factory()->create(['type_id' => $type->id]);
        $item3 = Item::factory()->create(['type_id' => $type->id]);

        // Item 1 in 2 orders
        for ($i = 0; $i < 2; $i++) {
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item1->id]);
        }

        // Item 2 in 1 order
        $order = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item2->id]);

        // Item 3 in 1 order
        $order = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item3->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalItemsUsed($startDate, $endDate);

        $this->assertEquals(3, $count);
    }

    /**
     * Test getTotalItemsUsed with type filter
     */
    public function test_get_total_items_used_with_type_filter(): void
    {
        $type1 = Type::factory()->create();
        $type2 = Type::factory()->create();

        $item1 = Item::factory()->create(['type_id' => $type1->id]);
        $item2 = Item::factory()->create(['type_id' => $type2->id]);

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'item_id' => $item1->id]);

        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'item_id' => $item2->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Only count items of type1
        $count = $this->calculator->getTotalItemsUsed($startDate, $endDate, $type1->id);

        $this->assertEquals(1, $count);
    }

    /**
     * Test getTotalItemsUsed with no completed orders
     */
    public function test_get_total_items_used_with_no_completed_orders(): void
    {
        $type = Type::factory()->create();
        $item = Item::factory()->create(['type_id' => $type->id]);
        $order = Order::factory()->create(['status' => 'pending']);
        OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalItemsUsed($startDate, $endDate);

        $this->assertEquals(0, $count);
    }

    /**
     * Test same item in multiple orders counts as one
     */
    public function test_same_item_multiple_orders_counts_as_one(): void
    {
        $type = Type::factory()->create();
        $item = Item::factory()->create(['type_id' => $type->id]);

        // Same item in 5 different orders
        for ($i = 0; $i < 5; $i++) {
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item->id]);
        }

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalItemsUsed($startDate, $endDate);

        $this->assertEquals(1, $count);
    }

    /**
     * Test item details are included in results
     */
    public function test_item_details_included_in_results(): void
    {
        $type = Type::factory()->create(['name' => 'T-Shirt']);
        $color = Color::factory()->create(['description' => 'Blue']);
        $size = Size::factory()->create(['value' => 'L']);

        $item = Item::factory()->create([
            'article' => 'ART-123',
            'title' => 'Blue T-Shirt',
            'type_id' => $type->id,
            'color_id' => $color->id,
            'size_id' => $size->id,
        ]);

        $order = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'item_id' => $item->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedItems($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals('ART-123', $result[0]['item_article']);
        $this->assertEquals('Blue T-Shirt', $result[0]['item_title']);
        $this->assertEquals('T-Shirt', $result[0]['item_type']);
        $this->assertEquals('Blue', $result[0]['item_color']);
        $this->assertEquals('L', $result[0]['item_size']);
    }
}
