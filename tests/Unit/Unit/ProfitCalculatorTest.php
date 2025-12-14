<?php

namespace Tests\Unit\Unit;

use App\Services\Report\ProfitCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class ProfitCalculatorTest extends TestCase
{
    private ProfitCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ProfitCalculator();
    }

    /**
     * Test profit calculation with positive profit
     */
    public function test_calculate_profit_with_positive_profit(): void
    {
        $revenue = [
            'total' => 10000,
            'by_day' => [
                '2025-11-25' => 5000,
                '2025-11-26' => 5000,
            ],
        ];

        $expenses = [
            'total' => 6000,
            'by_day' => [
                '2025-11-25' => 3000,
                '2025-11-26' => 3000,
            ],
        ];

        $result = $this->calculator->calculateProfit($revenue, $expenses);

        // Total profit: 10000 - 6000 = 4000
        $this->assertEquals(4000, $result['total']);
        $this->assertIsArray($result['by_day']);
        $this->assertEquals(2000, $result['by_day']['2025-11-25']);
        $this->assertEquals(2000, $result['by_day']['2025-11-26']);
    }

    /**
     * Test profit calculation with negative profit (loss)
     */
    public function test_calculate_profit_with_negative_profit(): void
    {
        $revenue = [
            'total' => 5000,
            'by_day' => [
                '2025-11-25' => 5000,
            ],
        ];

        $expenses = [
            'total' => 8000,
            'by_day' => [
                '2025-11-25' => 8000,
            ],
        ];

        $result = $this->calculator->calculateProfit($revenue, $expenses);

        // Total profit: 5000 - 8000 = -3000 (loss)
        $this->assertEquals(-3000, $result['total']);
        $this->assertEquals(-3000, $result['by_day']['2025-11-25']);
    }

    /**
     * Test profit calculation by day with mixed dates
     */
    public function test_calculate_profit_by_day(): void
    {
        $revenue = [
            'total' => 7000,
            'by_day' => [
                '2025-11-25' => 5000,
                '2025-11-26' => 2000,
            ],
        ];

        $expenses = [
            'total' => 4000,
            'by_day' => [
                '2025-11-25' => 1000,
                '2025-11-27' => 3000, // Different date
            ],
        ];

        $result = $this->calculator->calculateProfit($revenue, $expenses);

        // Total: 7000 - 4000 = 3000
        $this->assertEquals(3000, $result['total']);

        // By day:
        $this->assertEquals(4000, $result['by_day']['2025-11-25']); // 5000 - 1000
        $this->assertEquals(2000, $result['by_day']['2025-11-26']); // 2000 - 0
        $this->assertEquals(-3000, $result['by_day']['2025-11-27']); // 0 - 3000
    }

    /**
     * Test profit margin calculation
     */
    public function test_calculate_profit_margin(): void
    {
        // Profit margin = (4000 / 10000) * 100 = 40%
        $margin = $this->calculator->calculateProfitMargin(4000, 10000);
        $this->assertEquals(40.0, $margin);

        // Profit margin = (2500 / 5000) * 100 = 50%
        $margin = $this->calculator->calculateProfitMargin(2500, 5000);
        $this->assertEquals(50.0, $margin);

        // Profit margin with negative profit = (-1000 / 5000) * 100 = -20%
        $margin = $this->calculator->calculateProfitMargin(-1000, 5000);
        $this->assertEquals(-20.0, $margin);
    }

    /**
     * Test profit margin with zero revenue
     */
    public function test_calculate_profit_margin_with_zero_revenue(): void
    {
        $margin = $this->calculator->calculateProfitMargin(1000, 0);
        $this->assertEquals(0.0, $margin);
    }

    /**
     * Test get profit by day method
     */
    public function test_get_profit_by_day(): void
    {
        $startDate = Carbon::parse('2025-11-25');
        $endDate = Carbon::parse('2025-11-27');

        $revenueByDay = [
            '2025-11-25' => 5000,
            '2025-11-26' => 3000,
            '2025-11-27' => 2000,
        ];

        $expensesByDay = [
            '2025-11-25' => 2000,
            '2025-11-26' => 1000,
            '2025-11-27' => 1500,
        ];

        $result = $this->calculator->getProfitByDay($startDate, $endDate, $revenueByDay, $expensesByDay);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(3000, $result['2025-11-25']); // 5000 - 2000
        $this->assertEquals(2000, $result['2025-11-26']); // 3000 - 1000
        $this->assertEquals(500, $result['2025-11-27']); // 2000 - 1500
    }

    /**
     * Test get profit by day with missing dates
     */
    public function test_get_profit_by_day_with_missing_dates(): void
    {
        $startDate = Carbon::parse('2025-11-25');
        $endDate = Carbon::parse('2025-11-27');

        $revenueByDay = [
            '2025-11-25' => 5000,
            // 2025-11-26 missing
        ];

        $expensesByDay = [
            // 2025-11-25 missing
            '2025-11-27' => 2000,
        ];

        $result = $this->calculator->getProfitByDay($startDate, $endDate, $revenueByDay, $expensesByDay);

        $this->assertEquals(5000, $result['2025-11-25']); // 5000 - 0
        $this->assertEquals(0, $result['2025-11-26']); // 0 - 0
        $this->assertEquals(-2000, $result['2025-11-27']); // 0 - 2000
    }
}
