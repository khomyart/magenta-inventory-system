<?php

namespace Tests\Unit\Unit;

use App\Models\Order;
use App\Services\Report\RevenueCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private RevenueCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RevenueCalculator();
    }

    /**
     * Test revenue calculation with completed and paid orders
     */
    public function test_calculate_revenue_with_completed_orders(): void
    {
        // Create completed and paid orders
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
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_final_payment_on_card' => 1500,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateRevenue($startDate, $endDate);

        // Total: (1000+500+300+2000+1000+200) + (500+1500) = 5000 + 2000 = 7000
        $this->assertEquals(7000, $result['total']);
        $this->assertEquals(2, $result['orders_count']);
        $this->assertIsArray($result['by_day']);
    }

    /**
     * Test that only completed and paid orders are included
     */
    public function test_only_completed_and_paid_orders_included(): void
    {
        // This should NOT be included (status not completed)
        Order::factory()->create([
            'status' => 'pending',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 1000,
        ]);

        // This should NOT be included (not fully paid)
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => null,
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 1000,
        ]);

        // This should NOT be included (not completed)
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => null,
            'amount_of_advance_payment_on_card' => 1000,
        ]);

        // This SHOULD be included
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 5000,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateRevenue($startDate, $endDate);

        $this->assertEquals(5000, $result['total']);
        $this->assertEquals(1, $result['orders_count']);
    }

    /**
     * Test revenue grouped by day
     */
    public function test_revenue_by_day(): void
    {
        $today = Carbon::now();
        $yesterday = Carbon::now()->subDay();

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => $today,
            'completed_at' => $today,
            'amount_of_advance_payment_on_card' => 1000,
        ]);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => $yesterday,
            'completed_at' => $yesterday,
            'amount_of_advance_payment_on_card' => 2000,
        ]);

        $startDate = $yesterday->copy()->subDay();
        $endDate = $today->copy()->addDay();

        $result = $this->calculator->getRevenueByDay($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($today->format('Y-m-d'), $result);
        $this->assertArrayHasKey($yesterday->format('Y-m-d'), $result);
    }

    /**
     * Test total revenue calculation
     */
    public function test_get_total_revenue(): void
    {
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 3000,
            'amount_of_final_payment_on_card' => 7000,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $total = $this->calculator->getTotalRevenue($startDate, $endDate);

        $this->assertEquals(10000, $total);
    }

    /**
     * Test with no orders
     */
    public function test_calculate_revenue_with_no_orders(): void
    {
        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateRevenue($startDate, $endDate);

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['orders_count']);
    }
}
