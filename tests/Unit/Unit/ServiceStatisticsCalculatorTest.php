<?php

namespace Tests\Unit\Unit;

use App\Models\Order;
use App\Models\OrderService;
use App\Models\Service;
use App\Services\Report\ServiceStatisticsCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceStatisticsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ServiceStatisticsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ServiceStatisticsCalculator();
    }

    /**
     * Test getting most used services sorted by orders count
     */
    public function test_get_most_used_services_by_orders_count(): void
    {
        $service1 = Service::factory()->create(['title' => 'Service A']);
        $service2 = Service::factory()->create(['title' => 'Service B']);
        $service3 = Service::factory()->create(['title' => 'Service C']);

        // Service A used in 3 orders
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order3 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);

        OrderService::factory()->create([
            'order_id' => $order1->id,
            'service_id' => $service1->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);
        OrderService::factory()->create([
            'order_id' => $order2->id,
            'service_id' => $service1->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);
        OrderService::factory()->create([
            'order_id' => $order3->id,
            'service_id' => $service1->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        // Service B used in 2 orders
        $order4 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order5 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);

        OrderService::factory()->create([
            'order_id' => $order4->id,
            'service_id' => $service2->id,
            'quantity' => 1,
            'price_per_one_unit' => 200,
        ]);
        OrderService::factory()->create([
            'order_id' => $order5->id,
            'service_id' => $service2->id,
            'quantity' => 1,
            'price_per_one_unit' => 200,
        ]);

        // Service C used in 1 order
        $order6 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);

        OrderService::factory()->create([
            'order_id' => $order6->id,
            'service_id' => $service3->id,
            'quantity' => 1,
            'price_per_one_unit' => 300,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedServices($startDate, $endDate, 10, 'orders_count');

        $this->assertCount(3, $result);

        // Check sorting by orders_count (descending)
        $this->assertEquals('Service A', $result[0]['service_title']);
        $this->assertEquals(3, $result[0]['orders_count']);
        $this->assertEquals(3, $result[0]['total_quantity']);
        $this->assertEquals(300, $result[0]['total_revenue']);

        $this->assertEquals('Service B', $result[1]['service_title']);
        $this->assertEquals(2, $result[1]['orders_count']);
        $this->assertEquals(2, $result[1]['total_quantity']);
        $this->assertEquals(400, $result[1]['total_revenue']);

        $this->assertEquals('Service C', $result[2]['service_title']);
        $this->assertEquals(1, $result[2]['orders_count']);
        $this->assertEquals(1, $result[2]['total_quantity']);
        $this->assertEquals(300, $result[2]['total_revenue']);
    }

    /**
     * Test getting most used services sorted by total quantity
     */
    public function test_get_most_used_services_by_total_quantity(): void
    {
        $service1 = Service::factory()->create(['title' => 'Service A']);
        $service2 = Service::factory()->create(['title' => 'Service B']);

        // Service A: 2 orders, total quantity = 15
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);

        OrderService::factory()->create([
            'order_id' => $order1->id,
            'service_id' => $service1->id,
            'quantity' => 10,
            'price_per_one_unit' => 100,
        ]);
        OrderService::factory()->create([
            'order_id' => $order2->id,
            'service_id' => $service1->id,
            'quantity' => 5,
            'price_per_one_unit' => 100,
        ]);

        // Service B: 3 orders, total quantity = 6
        $order3 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order4 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order5 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);

        OrderService::factory()->create([
            'order_id' => $order3->id,
            'service_id' => $service2->id,
            'quantity' => 2,
            'price_per_one_unit' => 200,
        ]);
        OrderService::factory()->create([
            'order_id' => $order4->id,
            'service_id' => $service2->id,
            'quantity' => 2,
            'price_per_one_unit' => 200,
        ]);
        OrderService::factory()->create([
            'order_id' => $order5->id,
            'service_id' => $service2->id,
            'quantity' => 2,
            'price_per_one_unit' => 200,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedServices($startDate, $endDate, 10, 'total_quantity');

        $this->assertCount(2, $result);

        // Check sorting by total_quantity (descending)
        // Service A should be first (15 total quantity)
        $this->assertEquals('Service A', $result[0]['service_title']);
        $this->assertEquals(15, $result[0]['total_quantity']);
        $this->assertEquals(2, $result[0]['orders_count']);

        // Service B should be second (6 total quantity)
        $this->assertEquals('Service B', $result[1]['service_title']);
        $this->assertEquals(6, $result[1]['total_quantity']);
        $this->assertEquals(3, $result[1]['orders_count']);
    }

    /**
     * Test that only completed and paid orders are included
     */
    public function test_only_completed_and_paid_orders_included(): void
    {
        $service = Service::factory()->create(['title' => 'Test Service']);

        // Completed and paid order - should be included
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create([
            'order_id' => $order1->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        // Completed but not paid - should NOT be included
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => null,
            'completed_at' => now(),
        ]);
        OrderService::factory()->create([
            'order_id' => $order2->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        // Paid but not completed - should NOT be included
        $order3 = Order::factory()->create([
            'status' => 'in_progress',
            'fully_payed_at' => now(),
            'completed_at' => null,
        ]);
        OrderService::factory()->create([
            'order_id' => $order3->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        // Pending order - should NOT be included
        $order4 = Order::factory()->create([
            'status' => 'pending',
            'fully_payed_at' => null,
            'completed_at' => null,
        ]);
        OrderService::factory()->create([
            'order_id' => $order4->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedServices($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['orders_count']);
        $this->assertEquals(1, $result[0]['total_quantity']);
        $this->assertEquals(100, $result[0]['total_revenue']);
    }

    /**
     * Test filtering by date range based on completed_at
     */
    public function test_filter_by_date_range(): void
    {
        $service = Service::factory()->create(['title' => 'Test Service']);

        // Order within date range
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create([
            'order_id' => $order1->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        // Order outside date range (too old)
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => Carbon::now()->subDays(10),
            'completed_at' => Carbon::now()->subDays(10),
        ]);
        OrderService::factory()->create([
            'order_id' => $order2->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedServices($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['orders_count']);
    }

    /**
     * Test limit parameter works correctly
     */
    public function test_limit_parameter(): void
    {
        // Create 15 services
        for ($i = 1; $i <= 15; $i++) {
            $service = Service::factory()->create(['title' => "Service $i"]);
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderService::factory()->create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'quantity' => 1,
                'price_per_one_unit' => 100,
            ]);
        }

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Test with limit 5
        $result = $this->calculator->getMostUsedServices($startDate, $endDate, 5);
        $this->assertCount(5, $result);

        // Test with limit 10
        $result = $this->calculator->getMostUsedServices($startDate, $endDate, 10);
        $this->assertCount(10, $result);

        // Test with limit 20 (should return only 15)
        $result = $this->calculator->getMostUsedServices($startDate, $endDate, 20);
        $this->assertCount(15, $result);
    }

    /**
     * Test returns empty array when no completed orders
     */
    public function test_returns_empty_array_when_no_completed_orders(): void
    {
        $service = Service::factory()->create(['title' => 'Test Service']);
        $order = Order::factory()->create([
            'status' => 'pending',
            'fully_payed_at' => null,
            'completed_at' => null,
        ]);
        OrderService::factory()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'quantity' => 1,
            'price_per_one_unit' => 100,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedServices($startDate, $endDate);

        $this->assertEmpty($result);
    }

    /**
     * Test returns empty array when no services in orders
     */
    public function test_returns_empty_array_when_no_services(): void
    {
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedServices($startDate, $endDate);

        $this->assertEmpty($result);
    }

    /**
     * Test invalid sortBy parameter defaults to orders_count
     */
    public function test_invalid_sort_by_defaults_to_orders_count(): void
    {
        $service1 = Service::factory()->create(['title' => 'Service A']);
        $service2 = Service::factory()->create(['title' => 'Service B']);

        // Service A: 1 order, quantity = 10
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create([
            'order_id' => $order1->id,
            'service_id' => $service1->id,
            'quantity' => 10,
            'price_per_one_unit' => 100,
        ]);

        // Service B: 2 orders, quantity = 4
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order3 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create([
            'order_id' => $order2->id,
            'service_id' => $service2->id,
            'quantity' => 2,
            'price_per_one_unit' => 100,
        ]);
        OrderService::factory()->create([
            'order_id' => $order3->id,
            'service_id' => $service2->id,
            'quantity' => 2,
            'price_per_one_unit' => 100,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        // Test with invalid sortBy parameter
        $result = $this->calculator->getMostUsedServices($startDate, $endDate, 10, 'invalid_field');

        $this->assertCount(2, $result);

        // Should be sorted by orders_count (Service B first with 2 orders)
        $this->assertEquals('Service B', $result[0]['service_title']);
        $this->assertEquals(2, $result[0]['orders_count']);

        $this->assertEquals('Service A', $result[1]['service_title']);
        $this->assertEquals(1, $result[1]['orders_count']);
    }

    /**
     * Test revenue calculation is correct
     */
    public function test_revenue_calculation(): void
    {
        $service = Service::factory()->create(['title' => 'Test Service']);

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);

        // Order 1: quantity 5 * price 100 = 500
        OrderService::factory()->create([
            'order_id' => $order1->id,
            'service_id' => $service->id,
            'quantity' => 5,
            'price_per_one_unit' => 100,
        ]);

        // Order 2: quantity 3 * price 200 = 600
        OrderService::factory()->create([
            'order_id' => $order2->id,
            'service_id' => $service->id,
            'quantity' => 3,
            'price_per_one_unit' => 200,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getMostUsedServices($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['orders_count']);
        $this->assertEquals(8, $result[0]['total_quantity']);
        // Total revenue: 500 + 600 = 1100
        $this->assertEquals(1100, $result[0]['total_revenue']);
    }

    /**
     * Test getTotalServicesUsed returns correct count
     */
    public function test_get_total_services_used(): void
    {
        $service1 = Service::factory()->create();
        $service2 = Service::factory()->create();
        $service3 = Service::factory()->create();

        // Service 1 used in 2 orders
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create(['order_id' => $order1->id, 'service_id' => $service1->id]);
        OrderService::factory()->create(['order_id' => $order2->id, 'service_id' => $service1->id]);

        // Service 2 used in 1 order
        $order3 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create(['order_id' => $order3->id, 'service_id' => $service2->id]);

        // Service 3 used in 1 order
        $order4 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create(['order_id' => $order4->id, 'service_id' => $service3->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalServicesUsed($startDate, $endDate);

        // Should return 3 unique services
        $this->assertEquals(3, $count);
    }

    /**
     * Test getTotalServicesUsed with no completed orders
     */
    public function test_get_total_services_used_with_no_completed_orders(): void
    {
        $service = Service::factory()->create();
        $order = Order::factory()->create([
            'status' => 'pending',
            'fully_payed_at' => null,
            'completed_at' => null,
        ]);
        OrderService::factory()->create(['order_id' => $order->id, 'service_id' => $service->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalServicesUsed($startDate, $endDate);

        $this->assertEquals(0, $count);
    }

    /**
     * Test getTotalServicesUsed filtered by date range
     */
    public function test_get_total_services_used_filtered_by_date(): void
    {
        $service1 = Service::factory()->create();
        $service2 = Service::factory()->create();

        // Service 1 within date range
        $order1 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
        ]);
        OrderService::factory()->create(['order_id' => $order1->id, 'service_id' => $service1->id]);

        // Service 2 outside date range
        $order2 = Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => Carbon::now()->subDays(10),
            'completed_at' => Carbon::now()->subDays(10),
        ]);
        OrderService::factory()->create(['order_id' => $order2->id, 'service_id' => $service2->id]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalServicesUsed($startDate, $endDate);

        // Only 1 service within date range
        $this->assertEquals(1, $count);
    }

    /**
     * Test same service used in multiple orders counts as one unique service
     */
    public function test_same_service_multiple_orders_counts_as_one(): void
    {
        $service = Service::factory()->create();

        // Same service in 5 different orders
        for ($i = 0; $i < 5; $i++) {
            $order = Order::factory()->create([
                'status' => 'completed',
                'fully_payed_at' => now(),
                'completed_at' => now(),
            ]);
            OrderService::factory()->create(['order_id' => $order->id, 'service_id' => $service->id]);
        }

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalServicesUsed($startDate, $endDate);

        // Should count as 1 unique service
        $this->assertEquals(1, $count);
    }
}
