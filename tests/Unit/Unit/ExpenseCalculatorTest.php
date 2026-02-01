<?php

namespace Tests\Unit\Unit;

use App\Models\Spend;
use App\Services\Report\ExpenseCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ExpenseCalculator();
    }

    /**
     * Test expense calculation with spends
     */
    public function test_calculate_expenses_with_spends(): void
    {
        // Create spends
        Spend::factory()->create([
            'total_price' => 1000,
            'amount_as_cash' => 500,
            'amount_on_card' => 300,
            'amount_via_terminal' => 200,
            'currency' => 'UAH',
            'happened_at' => now(),
            'is_hidden' => false,
        ]);

        Spend::factory()->create([
            'total_price' => 2000,
            'amount_as_cash' => 1000,
            'amount_on_card' => 1000,
            'amount_via_terminal' => 0,
            'currency' => 'UAH',
            'happened_at' => now(),
            'is_hidden' => false,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateExpenses($startDate, $endDate);

        // Total: 1000 + 2000 = 3000
        $this->assertEquals(3000, $result['total']);
        // Breakdown: Cash (500+1000=1500), Card (300+1000=1300), Terminal (200+0=200)
        $this->assertEquals(1500, $result['breakdown']['cash']);
        $this->assertEquals(1300, $result['breakdown']['card']);
        $this->assertEquals(200, $result['breakdown']['terminal']);
        $this->assertEquals(2, $result['spends_count']);
        $this->assertIsArray($result['by_day']);
    }

    /**
     * Test that all spends are included (both visible and hidden)
     */
    public function test_all_spends_included(): void
    {
        // Hidden spend - should be included
        Spend::factory()->create([
            'total_price' => 5000,
            'currency' => 'UAH',
            'happened_at' => now(),
            'is_hidden' => true,
        ]);

        // Visible spend - should be included
        Spend::factory()->create([
            'total_price' => 3000,
            'currency' => 'UAH',
            'happened_at' => now(),
            'is_hidden' => false,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateExpenses($startDate, $endDate);

        // Total: 5000 + 3000 = 8000
        $this->assertEquals(8000, $result['total']);
        $this->assertEquals(2, $result['spends_count']);
    }

    /**
     * Test expenses grouped by day
     */
    public function test_expenses_by_day(): void
    {
        $today = Carbon::now();
        $yesterday = Carbon::now()->subDay();

        Spend::factory()->create([
            'total_price' => 1000,
            'currency' => 'UAH',
            'happened_at' => $today,
            'is_hidden' => false,
        ]);

        Spend::factory()->create([
            'total_price' => 2000,
            'currency' => 'UAH',
            'happened_at' => $yesterday,
            'is_hidden' => false,
        ]);

        $startDate = $yesterday->copy()->subDay();
        $endDate = $today->copy()->addDay();

        $result = $this->calculator->getExpensesByDay($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($today->format('Y-m-d'), $result);
        $this->assertArrayHasKey($yesterday->format('Y-m-d'), $result);
    }

    /**
     * Test total expenses calculation
     */
    public function test_get_total_expenses(): void
    {
        Spend::factory()->create([
            'total_price' => 1500,
            'currency' => 'UAH',
            'happened_at' => now(),
            'is_hidden' => false,
        ]);

        Spend::factory()->create([
            'total_price' => 2500,
            'currency' => 'UAH',
            'happened_at' => now(),
            'is_hidden' => false,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $total = $this->calculator->getTotalExpenses($startDate, $endDate);

        $this->assertEquals(4000, $total);
    }

    /**
     * Test with no spends
     */
    public function test_calculate_expenses_with_no_spends(): void
    {
        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateExpenses($startDate, $endDate);

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['spends_count']);
    }
}
