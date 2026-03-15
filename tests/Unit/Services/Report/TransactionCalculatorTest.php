<?php

namespace Tests\Unit\Services\Report;

use App\Models\BusinessAccountTransaction;
use App\Models\User;
use App\Services\Report\TransactionCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private TransactionCalculator $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionCalculator();
    }

    public function test_calculate_transactions_with_no_transactions()
    {
        $result = $this->service->calculateTransactions(Carbon::now()->subDay(), Carbon::now()->addDay());

        $this->assertEquals(0, $result['count']);
        $expectedEmpty = ['total' => 0, 'cash' => 0, 'card' => 0, 'terminal' => 0];
        $this->assertEquals($expectedEmpty, $result['income']);
        $this->assertEquals($expectedEmpty, $result['outcome']);
    }

    public function test_calculate_transactions_correctly_sums_income_and_outcome()
    {
        $now = Carbon::now();
        $user = User::factory()->create();

        $transactions = [
            ['type' => 'income',  'total_price' => 100, 'amount_as_cash' => 100, 'amount_on_card' => 0, 'amount_via_terminal' => 0],
            ['type' => 'income',  'total_price' => 250, 'amount_as_cash' => 50,  'amount_on_card' => 200, 'amount_via_terminal' => 0],
            ['type' => 'outcome', 'total_price' => 50,  'amount_as_cash' => 0,   'amount_on_card' => 0,   'amount_via_terminal' => 50],
            ['type' => 'outcome', 'total_price' => 75,  'amount_as_cash' => 75,  'amount_on_card' => 0,   'amount_via_terminal' => 0],

            // Виправлено: замість null тепер 0, як того вимагає база даних
            ['type' => 'income',  'total_price' => 30,  'amount_as_cash' => 0,   'amount_on_card' => 30,  'amount_via_terminal' => 0],
        ];

        foreach ($transactions as $data) {
            BusinessAccountTransaction::create(array_merge($data, [
                'title' => 'Test Transaction',
                'currency' => 'UAH',
                'created_by_user' => $user->id,
                'happened_at' => $now,
            ]));
        }

        // Act
        $result = $this->service->calculateTransactions($now->copy()->subDay(), $now->copy()->addDay());

        // Assert
        $this->assertEquals(5, $result['count']);

        $this->assertEquals([
            'total' => 380, // 100 + 250 + 30
            'cash' => 150,  // 100 + 50 + 0
            'card' => 230,  // 0 + 200 + 30
            'terminal' => 0,
        ], $result['income']);

        $this->assertEquals([
            'total' => 125, // 50 + 75
            'cash' => 75,   // 0 + 75
            'card' => 0,
            'terminal' => 50,
        ], $result['outcome']);
    }
}
