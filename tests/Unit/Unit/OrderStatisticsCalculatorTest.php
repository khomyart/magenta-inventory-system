<?php

namespace Tests\Unit\Unit;

use App\Models\Order;
use App\Services\Report\OrderStatisticsCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatisticsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private OrderStatisticsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new OrderStatisticsCalculator();
    }

    /**
     * Test orders grouped by status
     */
    public function test_get_orders_by_status(): void
    {
        // Create orders with different statuses
        Order::factory()->create(['status' => 'pending', 'created_at' => now()]);
        Order::factory()->create(['status' => 'pending', 'created_at' => now()]);
        Order::factory()->create(['status' => 'confirmed', 'created_at' => now()]);
        Order::factory()->create(['status' => 'in_progress', 'created_at' => now()]);
        Order::factory()->create(['status' => 'completed', 'created_at' => now()]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getOrdersByStatus($startDate, $endDate);

        $this->assertEquals(2, $result['pending']);
        $this->assertEquals(1, $result['confirmed']);
        $this->assertEquals(1, $result['in_progress']);
        $this->assertEquals(1, $result['completed']);
        $this->assertEquals(0, $result['cancelled']);
    }

    /**
     * Test total orders count
     */
    public function test_get_total_orders_count(): void
    {
        Order::factory()->count(5)->create(['created_at' => now()]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalOrdersCount($startDate, $endDate);

        $this->assertEquals(5, $count);
    }

    /**
     * Test orders distribution statistics
     */
    public function test_get_orders_distribution(): void
    {
        // Create various orders
        Order::factory()->create([
            'status' => 'pending',
            'created_at' => now(),
        ]);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'total_price' => 900,
            'amount_of_advance_payment_on_card' => 100,
            'amount_of_advance_payment_via_terminal' => 100,
            'amount_of_advance_payment_as_cash' => 100,
            'amount_of_final_payment_on_card' => 100,
            'amount_of_final_payment_via_terminal' => 100,
            'amount_of_final_payment_as_cash' => 400,
        ]);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => null,
            'completed_at' => now(),
            'created_at' => now(),
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getOrdersDistribution($startDate, $endDate);

        $this->assertEquals(3, $result['total']);
        $this->assertIsArray($result['by_status']);
        $this->assertEquals(1, $result['by_status']['pending']);
        $this->assertEquals(2, $result['by_status']['completed']);
        // Only one completed AND paid order
        $this->assertEquals(1, $result['completed_and_paid']);
    }

    /**
     * Test average order value calculation
     */
    public function test_calculate_average_order_value(): void
    {
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 1000,
            'amount_of_final_payment_on_card' => 4000,
        ]);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 2000,
            'amount_of_final_payment_on_card' => 3000,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $average = $this->calculator->calculateAverageOrderValue($startDate, $endDate);

        // First order: 1000 + 4000 = 5000
        // Second order: 2000 + 3000 = 5000
        // Average: (5000 + 5000) / 2 = 5000
        $this->assertEquals(5000, $average);
    }

    /**
     * Test average order value with different payment types
     */
    public function test_calculate_average_order_value_with_multiple_payment_types(): void
    {
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 1000,
            'amount_of_advance_payment_via_terminal' => 500,
            'amount_of_advance_payment_as_cash' => 300,
            'amount_of_final_payment_on_card' => 2000,
            'amount_of_final_payment_via_terminal' => 1000,
            'amount_of_final_payment_as_cash' => 200,
        ]);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 2000,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $average = $this->calculator->calculateAverageOrderValue($startDate, $endDate);

        // First order: 1000 + 500 + 300 + 2000 + 1000 + 200 = 5000
        // Second order: 2000
        // Average: (5000 + 2000) / 2 = 3500
        $this->assertEquals(3500, $average);
    }

    /**
     * Test average order value with no orders
     */
    public function test_calculate_average_order_value_with_no_orders(): void
    {
        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $average = $this->calculator->calculateAverageOrderValue($startDate, $endDate);

        $this->assertEquals(0, $average);
    }

    /**
     * Test orders by status with all 5 statuses
     */
    public function test_orders_by_status_with_all_statuses(): void
    {
        Order::factory()->create(['status' => 'pending', 'created_at' => now()]);
        Order::factory()->create(['status' => 'confirmed', 'created_at' => now()]);
        Order::factory()->create(['status' => 'in_progress', 'created_at' => now()]);
        Order::factory()->create(['status' => 'completed', 'created_at' => now()]);
        Order::factory()->create(['status' => 'cancelled', 'created_at' => now()]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getOrdersByStatus($startDate, $endDate);

        // Each status should have exactly 1 order
        $this->assertCount(5, $result);
        $this->assertEquals(1, $result['pending']);
        $this->assertEquals(1, $result['confirmed']);
        $this->assertEquals(1, $result['in_progress']);
        $this->assertEquals(1, $result['completed']);
        $this->assertEquals(1, $result['cancelled']);
    }

    /**
     * Test that only orders within date range are counted
     */
    public function test_orders_filtered_by_date_range(): void
    {
        // Order outside date range (too old)
        Order::factory()->create([
            'status' => 'pending',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Order within date range
        Order::factory()->create([
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $count = $this->calculator->getTotalOrdersCount($startDate, $endDate);

        // Only 1 order should be counted (the one within range)
        $this->assertEquals(1, $count);
    }
}
