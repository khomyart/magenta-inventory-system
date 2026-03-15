<?php

namespace Tests\Feature;

use App\Models\AccessToken;
use App\Models\Allowense;
use App\Models\BusinessAccountTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessAccountTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccessToken $token;
    private string $section = 'business_account_transactions';

    protected function setUp(): void
    {
        parent::setUp();

        \Mockery::close();

        $role = Role::create(['name' => 'Admin']);
        $actions = ['create', 'read', 'update', 'delete'];

        foreach ($actions as $action) {
            $allowense = Allowense::firstOrCreate([
                'section' => $this->section,
                'action' => $action,
            ]);
            $role->allowenses()->attach($allowense->id);
        }

        $this->user = User::create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'name' => 'Admin User',
        ]);
        $this->user->roles()->attach($role->id);

        $this->token = AccessToken::create([
            'user_id' => $this->user->id,
            'token' => 'test-token',
            'ip_address' => '127.0.0.1',
            'expired_at' => now()->addHour(),
            'last_used' => now(),
        ]);
    }

    /** @test */
    public function it_can_create_a_transaction()
    {
        $data = [
            'title' => 'Test Income',
            'amount_on_card' => 100,
            'amount_via_terminal' => 50,
            'amount_as_cash' => 20,
            'currency' => 'UAH',
            'type' => 'income',
            'happened_at' => '2026-03-14 12:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token->token,
        ])->postJson('/api/business_account_transactions/create', $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('business_account_transactions', [
            'title' => 'Test Income',
            'total_price' => 170,
            'type' => 'income',
        ]);
    }

    /** @test */
    public function it_can_read_transactions_with_filters()
    {
        BusinessAccountTransaction::create([
            'title' => 'Transaction A',
            'total_price' => 100,
            'amount_on_card' => 100,
            'currency' => 'UAH',
            'type' => 'income',
            'happened_at' => '2026-03-10 10:00',
            'created_by_user_id' => $this->user->id,
        ]);

        BusinessAccountTransaction::create([
            'title' => 'Transaction B',
            'total_price' => 200,
            'amount_on_card' => 200,
            'currency' => 'UAH',
            'type' => 'outcome',
            'happened_at' => '2026-03-11 10:00',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token->token,
        ])->postJson('/api/business_account_transactions/read', [
            'itemsPerPage' => 10,
            'page' => 1,
            'title_filter_value' => 'Transaction A',
            'title_filter_mode' => 'include',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Transaction A');
    }

    /** @test */
    public function it_can_update_a_transaction()
    {
        $transaction = BusinessAccountTransaction::create([
            'title' => 'Old Title',
            'total_price' => 100,
            'amount_on_card' => 100,
            'currency' => 'UAH',
            'type' => 'income',
            'happened_at' => '2026-03-10 10:00',
            'created_by_user_id' => $this->user->id,
        ]);

        $data = [
            'title' => 'New Title',
            'amount_on_card' => 150,
            'amount_via_terminal' => 0,
            'amount_as_cash' => 0,
            'currency' => 'UAH',
            'type' => 'income',
            'happened_at' => '2026-03-10 11:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token->token,
        ])->postJson("/api/business_account_transactions/update/{$transaction->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('business_account_transactions', [
            'id' => $transaction->id,
            'title' => 'New Title',
            'total_price' => 150,
        ]);
    }

    /** @test */
    public function it_can_delete_a_transaction()
    {
        $transaction = BusinessAccountTransaction::create([
            'title' => 'To Delete',
            'total_price' => 100,
            'amount_on_card' => 100,
            'currency' => 'UAH',
            'type' => 'outcome',
            'happened_at' => '2026-03-10 10:00',
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token->token,
        ])->postJson("/api/business_account_transactions/delete/{$transaction->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('business_account_transactions', ['id' => $transaction->id]);
    }

    /** @test */
    public function it_denies_access_without_permissions()
    {
        $otherUser = User::create([
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
            'name' => 'Other User',
        ]);

        $otherToken = AccessToken::create([
            'user_id' => $otherUser->id,
            'token' => 'other-token',
            'ip_address' => '127.0.0.1',
            'expired_at' => now()->addHour(),
            'last_used' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherToken->token,
        ])->postJson('/api/business_account_transactions/read', [
            'itemsPerPage' => 10,
            'page' => 1,
        ]);

        $response->assertStatus(403);
    }
}
