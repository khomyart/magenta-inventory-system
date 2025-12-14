<?php

namespace Tests\Unit\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\Report\UserInvolvementCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInvolvementCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private UserInvolvementCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new UserInvolvementCalculator();
    }

    /**
     * Test user involvement with level 1 (11%)
     */
    public function test_calculate_user_involvement_with_level_1(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 10000, // Total: 10000
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals($user->id, $result[0]['user_id']);
        $this->assertEquals('John Doe', $result[0]['user_name']);
        $this->assertEquals(1, $result[0]['involvement_level']);
        $this->assertEquals(11, $result[0]['involvement_percentage']);
        $this->assertEquals(1, $result[0]['orders_count']);
        $this->assertEquals(10000, $result[0]['total_orders_amount']);
        // 10000 * 11% = 1100
        $this->assertEquals(1100, $result[0]['earnings']);
    }

    /**
     * Test user involvement with level 2 (8%)
     */
    public function test_calculate_user_involvement_with_level_2(): void
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_2_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 10000, // Total: 10000
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals($user->id, $result[0]['user_id']);
        $this->assertEquals(2, $result[0]['involvement_level']);
        $this->assertEquals(8, $result[0]['involvement_percentage']);
        // 10000 * 8% = 800
        $this->assertEquals(800, $result[0]['earnings']);
    }

    /**
     * Test user involvement with level 3 (5%)
     */
    public function test_calculate_user_involvement_with_level_3(): void
    {
        $user = User::factory()->create(['name' => 'Bob Johnson']);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_3_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 10000, // Total: 10000
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals($user->id, $result[0]['user_id']);
        $this->assertEquals(3, $result[0]['involvement_level']);
        $this->assertEquals(5, $result[0]['involvement_percentage']);
        // 10000 * 5% = 500
        $this->assertEquals(500, $result[0]['earnings']);
    }

    /**
     * Test user involvement with multiple orders at same level
     */
    public function test_calculate_user_involvement_with_multiple_orders(): void
    {
        $user = User::factory()->create(['name' => 'Alice Brown']);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 5000,
            'total_price' => 4000,
        ]);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 3000,
            'total_price' => 2000,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['orders_count']);
        $this->assertEquals(8000, $result[0]['total_orders_amount']); // 5000 + 3000
        // (5000 + 3000) * 11% = 880
        $this->assertEquals(880, $result[0]['earnings']);
    }

    /**
     * Test user involvement sorted by earnings
     */
    public function test_calculate_user_involvement_sorted_by_earnings(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user3 = User::factory()->create(['name' => 'User 3']);

        // User 1: 10000 * 11% = 1100
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user1->id,
            'amount_of_advance_payment_on_card' => 10000,
        ]);

        // User 2: 30000 * 8% = 2400
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_2_user_id' => $user2->id,
            'amount_of_advance_payment_on_card' => 30000,
        ]);

        // User 3: 20000 * 5% = 1000
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_3_user_id' => $user3->id,
            'amount_of_advance_payment_on_card' => 20000,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertCount(3, $result);
        // Should be sorted by earnings descending: User2(2400), User1(1100), User3(1000)
        $this->assertEquals($user2->id, $result[0]['user_id']);
        $this->assertEquals(2400, $result[0]['earnings']);
        $this->assertEquals($user1->id, $result[1]['user_id']);
        $this->assertEquals(1100, $result[1]['earnings']);
        $this->assertEquals($user3->id, $result[2]['user_id']);
        $this->assertEquals(1000, $result[2]['earnings']);
    }

    /**
     * Test get user earnings method
     */
    public function test_get_user_earnings(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 5000,
            'total_price' => 4000,
        ]);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_2_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 10000,
            'total_price' => 9000,
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->getUserEarnings($user, $startDate, $endDate);

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals('Test User', $result['user_name']);
        $this->assertEquals(2, $result['orders_count']);
        $this->assertEquals(15000, $result['total_orders_amount']); // 5000 + 10000
        // (5000 * 11%) + (10000 * 8%) = 550 + 800 = 1350
        $this->assertEquals(1350, $result['earnings']);
    }

    /**
     * Test user involvement with no orders
     */
    public function test_user_involvement_with_no_orders(): void
    {
        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Test that only completed and paid orders are included
     */
    public function test_only_completed_and_paid_orders_included(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        // This should NOT be included (status not completed)
        Order::factory()->create([
            'status' => 'pending',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 10000,
        ]);

        // This should NOT be included (not fully paid)
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => null,
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 10000,
            'total_price' => 10000
        ]);

        // This should NOT be included (not completed)
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => null,
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 10000,
            'total_price' => 10000
        ]);

        // This SHOULD be included
        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 5000,
            'total_price' => 4000
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['orders_count']);
        $this->assertEquals(5000, $result[0]['total_orders_amount']);
        // 5000 * 11% = 550
        $this->assertEquals(550, $result[0]['earnings']);
    }

    /**
     * Test user involvement with multiple payment types
     */
    public function test_user_involvement_with_multiple_payment_types(): void
    {
        $user = User::factory()->create(['name' => 'Multi Payment User']);

        Order::factory()->create([
            'status' => 'completed',
            'fully_payed_at' => now(),
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user->id,
            'amount_of_advance_payment_on_card' => 1000,
            'amount_of_advance_payment_via_terminal' => 500,
            'amount_of_advance_payment_as_cash' => 300,
            'amount_of_final_payment_on_card' => 2000,
            'amount_of_final_payment_via_terminal' => 1000,
            'amount_of_final_payment_as_cash' => 200,
            'total_price' => 4999
        ]);

        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDay();

        $result = $this->calculator->calculateUserInvolvement($startDate, $endDate);

        $this->assertCount(1, $result);
        // Total: 1000 + 500 + 300 + 2000 + 1000 + 200 = 5000
        $this->assertEquals(5000, $result[0]['total_orders_amount']);
        // 5000 * 11% = 550
        $this->assertEquals(550, $result[0]['earnings']);
    }
}
