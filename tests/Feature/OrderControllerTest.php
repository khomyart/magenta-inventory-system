<?php

namespace Tests\Feature;

use App\Models\AccessToken;
use App\Models\Allowense;
use App\Models\City;
use App\Models\Color;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Gender;
use App\Models\Income;
use App\Models\Item;
use App\Models\ItemWarehouseAmount;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Outcome;
use App\Models\Role;
use App\Models\Service;
use App\Models\Size;
use App\Models\Type;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AccessToken $token;

    private Warehouse $warehouse;

    private Item $item;

    private Service $service;

    private Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        // Створення ролі з дозволами
        $role = Role::create(['name' => 'Test Admin']);

        $allowenses = [
            ['section' => 'orders', 'action' => 'create'],
            ['section' => 'orders', 'action' => 'read'],
            ['section' => 'orders', 'action' => 'update'],
            ['section' => 'orders', 'action' => 'delete'],
        ];

        foreach ($allowenses as $allowense) {
            $allowenseModel = Allowense::firstOrCreate($allowense);
            $role->allowenses()->attach($allowenseModel->id);
        }

        // Створення користувача
        $this->user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'name' => 'Test User',
        ]);

        $this->user->roles()->attach($role->id);

        // Створення токена доступу
        $this->token = AccessToken::create([
            'user_id' => $this->user->id,
            'token' => 'test-token-'.uniqid(),
            'ip_address' => '127.0.0.1',
            'expired_at' => now()->addHour(),
            'last_used' => now(),
        ]);

        // Створення країни та міста
        $country = Country::create([
            'name' => 'Test Country',
        ]);

        $city = City::create([
            'name' => 'Test City',
            'country_id' => $country->id,
        ]);

        // Створення складу
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'address' => 'Test Address',
            'description' => 'Test Description',
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);

        // Створення допоміжних сутностей для товару
        $type = Type::create([
            'name' => 'Test Type',
            'number_in_row' => 1,
        ]);

        $size = Size::create([
            'value' => 'M',
            'description' => 'Medium',
            'number_in_row' => 1,
        ]);

        $color = Color::create([
            'value' => 'Black',
            'article' => 'BLK',
            'description' => 'Black color',
            'text_color_value' => '#FFFFFF',
        ]);

        $gender = Gender::create([
            'name' => 'Unisex',
            'number_in_row' => 1,
        ]);

        $unit = Unit::create([
            'name' => 'шт',
            'description' => 'штука',
        ]);

        // Створення товару
        $this->item = Item::create([
            'title' => 'Test Item',
            'article' => 'TEST-001',
            'model' => 'Test Model',
            'price' => 3700,
            'currency' => 'UAH',
            'group_id' => '550e8400-e29b-41d4-a716-446655440000',
            'type_id' => $type->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'gender_id' => $gender->id,
            'unit_id' => $unit->id,
            'lack' => 0,
        ]);

        // Додавання товару на склад
        ItemWarehouseAmount::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 100,
        ]);

        // Створення послуги
        $this->service = Service::create([
            'title' => 'Test Service',
            'price' => 500,
        ]);

        // Створення контакту
        $this->contact = Contact::create([
            'name' => 'Test Contact',
            'phone' => '380123456789',
            'email' => 'contact@test.com',
            'preferred_platforms' => json_encode(['call', 'email']),
        ]);
    }

    // ==================== CREATE ORDER TESTS ====================

    /** @test */
    public function it_creates_order_with_pending_status_when_no_payment()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 2,
                ],
            ],
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 500,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'total_price' => 7900, // 2*3700 + 1*500
        ]);

        // ВАЖЛИВО: Перевірка що товари НЕ списані (outcome не створено)
        $this->assertEquals(0, Outcome::where('order_id', $orderId)->count());

        // Перевірка що кількість товару на складі не змінилась
        $stock = ItemWarehouseAmount::where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(100, $stock->amount);

        // Перевірка запису в історію статусів
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $orderId,
            'old_status' => null,
            'new_status' => 'pending',
            'comment' => 'Створення замовлення',
        ]);
    }

    /** @test */
    public function it_creates_order_with_confirmed_status_when_advance_payment()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'amount_of_advance_payment_on_card' => 1000,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 2,
                ],
            ],
            'order_services' => [],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'confirmed',
            'amount_of_advance_payment_on_card' => 1000,
        ]);

        // ВАЖЛИВО: Перевірка що товари НЕ списані при створенні з підтвердженням
        $this->assertEquals(0, Outcome::where('order_id', $orderId)->count());

        // Перевірка запису в історію
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $orderId,
            'old_status' => null,
            'new_status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_creates_order_with_combined_advance_payments()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'amount_of_advance_payment_on_card' => 100,
            'amount_of_advance_payment_via_terminal' => 200,
            'amount_of_advance_payment_as_cash' => 300,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $order = Order::find($response->json('order.id'));

        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals(100, $order->amount_of_advance_payment_on_card);
        $this->assertEquals(200, $order->amount_of_advance_payment_via_terminal);
        $this->assertEquals(300, $order->amount_of_advance_payment_as_cash);
    }

    /** @test */
    public function it_calculates_total_price_correctly()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 500,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 1000,
                    'quantity' => 3,
                ],
            ],
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 800,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        // (3 * 1000) + (2 * 800) - 500 = 3000 + 1600 - 500 = 4100
        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'total_price' => 4100,
        ]);
    }

    /** @test */
    public function it_sets_fully_payed_at_when_creating_order_with_full_payment()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', [
            'currency' => 'UAH',
            'discount' => 0,
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 500,
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 1000,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $orderId = $response->json('order.id');

        $order = Order::find($orderId);
        $this->assertNotNull($order->fully_payed_at);
        $this->assertEquals(1000, $order->total_price);
    }

    /** @test */
    public function it_requires_warehouse_id_when_order_has_items()
    {
        $orderData = [
            'currency' => 'UAH',
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_create_order_without_items_only_services()
    {
        $orderData = [
            'currency' => 'UAH',
            'discount' => 0,
            'warehouse_id' => null,
            'contact_id' => $this->contact->id,
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 500,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'total_price' => 1000,
        ]);
    }

    /** @test */
    public function it_creates_order_with_completion_deadline()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'completion_deadline' => '2025-12-31 23:59:59',
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'completion_deadline' => '2025-12-31 23:59:59',
        ]);
    }

    /** @test */
    public function it_creates_order_with_notes()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'notes' => 'Термінове виконання до кінця тижня',
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 500,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'notes' => 'Термінове виконання до кінця тижня',
        ]);
    }

    /** @test */
    public function it_creates_order_with_usd_currency()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'USD',
            'discount' => 0,
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 100,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'currency' => 'USD',
        ]);
    }

    /** @test */
    public function it_creates_order_with_eur_currency()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'EUR',
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 100,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'currency' => 'EUR',
        ]);
    }

    /** @test */
    public function it_records_initial_status_in_history_when_creating_order()
    {
        $orderData = [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 500,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(200);

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $orderId,
            'old_status' => null,
            'new_status' => 'pending',
            'comment' => 'Створення замовлення',
        ]);
    }

    // ==================== CONFIRM ORDER TESTS ====================

    /** @test */
    public function it_confirms_pending_order_without_advance_payment()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 2,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/confirm", []);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);

        // ВАЖЛИВО: Перевірка що товари НЕ списані при підтвердженні
        $this->assertEquals(0, Outcome::where('order_id', $order->id)->count());

        // Перевірка запису в історію
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'pending',
            'new_status' => 'confirmed',
            'comment' => 'Замовлення підтверджено без авансу',
        ]);
    }

    /** @test */
    public function it_confirms_pending_order_with_advance_payment()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/confirm", [
            'amount_of_advance_payment_on_card' => 1000,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'amount_of_advance_payment_on_card' => 1000,
        ]);

        // Перевірка коментарю в історії
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'pending',
            'new_status' => 'confirmed',
            'comment' => 'Замовлення підтверджено з авансом 1000 UAH',
        ]);
    }

    /** @test */
    public function it_cannot_confirm_non_pending_order()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/confirm", []);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_confirm_nonexistent_order()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders/99999/confirm', []);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_confirms_order_with_combined_advance_payments()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/confirm", [
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 300,
            'amount_of_advance_payment_as_cash' => 200,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 300,
            'amount_of_advance_payment_as_cash' => 200,
        ]);

        // Перевірка коментарю в історії
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'pending',
            'new_status' => 'confirmed',
            'comment' => 'Замовлення підтверджено з авансом 1000 UAH',
        ]);
    }

    // ==================== START WORK TESTS ====================

    /** @test */
    public function it_starts_work_on_confirmed_order_and_creates_outcome()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 1000,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 5,
            'currency' => 'UAH',
        ]);

        // Перевірка наявності товару до початку роботи
        $stockBefore = ItemWarehouseAmount::where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(100, $stockBefore->amount);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/start-work");

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_progress',
        ]);

        // ВАЖЛИВО: Перевірка що outcome створено і товари списані
        $this->assertEquals(1, Outcome::where('order_id', $order->id)->count());

        $outcome = Outcome::where('order_id', $order->id)->first();
        $this->assertEquals($this->item->id, $outcome->item_id);
        $this->assertEquals($this->warehouse->id, $outcome->warehouse_id);
        $this->assertEquals(5, $outcome->amount);

        // Перевірка що кількість на складі зменшилась
        $stockAfter = ItemWarehouseAmount::where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(95, $stockAfter->amount); // 100 - 5 = 95

        // Перевірка запису в історію
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'confirmed',
            'new_status' => 'in_progress',
            'comment' => 'Замовлення прийнято в роботу',
        ]);
    }

    /** @test */
    public function it_cannot_start_work_when_insufficient_stock()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 1000,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 200, // Більше ніж доступно (100)
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/start-work");

        $response->assertStatus(422);

        // Перевірка що статус не змінився
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);

        // Перевірка що outcome не створено
        $this->assertEquals(0, Outcome::where('order_id', $order->id)->count());
    }

    /** @test */
    public function it_cannot_start_work_on_non_confirmed_order()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/start-work");

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_start_work_on_order_without_items()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 500,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderServices()->create([
            'service_id' => $this->service->id,
            'price_per_one_unit' => 500,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/start-work");

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_progress',
        ]);
    }

    // ==================== COMPLETE ORDER TESTS ====================

    /** @test */
    public function it_completes_in_progress_order_with_involvement_level_1()
    {
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/complete", [
            'involvement_level_1_user_id' => $user2->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'involvement_level_1_user_id' => $user2->id,
            'involvement_level_2_user_id' => null,
            'involvement_level_3_user_id' => null,
        ]);

        $order->refresh();
        $this->assertNotNull($order->completed_at);

        // Перевірка запису в історію
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'in_progress',
            'new_status' => 'completed',
            'comment' => 'Замовлення виконано',
        ]);
    }

    /** @test */
    public function it_completes_order_with_involvement_levels_2_and_3()
    {
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);
        $user3 = User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
        ]);

        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/complete", [
            'involvement_level_2_user_id' => $user2->id,
            'involvement_level_3_user_id' => $user3->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'involvement_level_1_user_id' => null,
            'involvement_level_2_user_id' => $user2->id,
            'involvement_level_3_user_id' => $user3->id,
        ]);
    }

    /** @test */
    public function it_cannot_complete_with_level_1_and_level_2_together()
    {
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);
        $user3 = User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
        ]);

        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/complete", [
            'involvement_level_1_user_id' => $user2->id,
            'involvement_level_2_user_id' => $user3->id,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_complete_with_same_user_in_multiple_levels()
    {
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/complete", [
            'involvement_level_2_user_id' => $user2->id,
            'involvement_level_3_user_id' => $user2->id,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_complete_non_in_progress_order()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/complete", []);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_completes_order_without_involvement_levels()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/complete", []);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'involvement_level_1_user_id' => null,
            'involvement_level_2_user_id' => null,
            'involvement_level_3_user_id' => null,
        ]);

        $order->refresh();
        $this->assertNotNull($order->completed_at);
    }

    // ==================== PAYMENT TESTS ====================

    /** @test */
    public function it_adds_final_payment_to_confirmed_order()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 300,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/payment", [
            'amount_of_final_payment_on_card' => 200,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'amount_of_final_payment_on_card' => 200,
        ]);
    }

    /** @test */
    public function it_adds_to_existing_final_payments()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 200,
            'amount_of_final_payment_via_terminal' => 100,
            'amount_of_final_payment_as_cash' => 50,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/payment", [
            'amount_of_final_payment_on_card' => 150,
            'amount_of_final_payment_via_terminal' => 50,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'amount_of_final_payment_on_card' => 350, // 200 + 150
            'amount_of_final_payment_via_terminal' => 150, // 100 + 50
            'amount_of_final_payment_as_cash' => 50, // unchanged
        ]);
    }

    /** @test */
    public function it_sets_fully_payed_at_when_payment_is_complete()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
            'fully_payed_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/payment", [
            'amount_of_final_payment_on_card' => 500,
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertNotNull($order->fully_payed_at);
    }

    /** @test */
    public function it_cannot_make_payment_for_pending_order()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/payment", [
            'amount_of_final_payment_on_card' => 500,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_make_payment_for_cancelled_order()
    {
        $order = Order::create([
            'status' => 'cancelled',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/payment", [
            'amount_of_final_payment_on_card' => 500,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_adds_payment_to_in_progress_order()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 300,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/payment", [
            'amount_of_final_payment_on_card' => 400,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'amount_of_final_payment_on_card' => 400,
        ]);
    }

    /** @test */
    public function it_adds_payment_to_completed_order()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/payment", [
            'amount_of_final_payment_on_card' => 500,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'amount_of_final_payment_on_card' => 500,
        ]);

        // Перевірка що fully_payed_at встановлено
        $order->refresh();
        $this->assertNotNull($order->fully_payed_at);
    }

    // ==================== CANCEL ORDER TESTS ====================

    /** @test */
    public function it_cancels_in_progress_order_with_item_return()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 7400,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 2,
            'currency' => 'UAH',
        ]);

        // Створюємо outcome (симулюємо що товари вже списані)
        Outcome::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 2,
            'reason_name' => 'order',
            'detail' => "Замовлення #{$order->id}",
            'order_id' => $order->id,
        ]);

        // Зменшуємо кількість на складі
        $stock = ItemWarehouseAmount::where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $stock->amount = 98; // 100 - 2
        $stock->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        // Перевірка що income створено
        $this->assertEquals(1, Income::where('order_id', $order->id)->count());

        $income = Income::where('order_id', $order->id)->first();
        $this->assertEquals($this->item->id, $income->item_id);
        $this->assertEquals(2, $income->amount_of_items);

        // Перевірка що товари повернулись на склад
        $stockAfter = ItemWarehouseAmount::where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(100, $stockAfter->amount); // 98 + 2 = 100

        // Перевірка історії
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'in_progress',
            'new_status' => 'cancelled',
            'comment' => 'Замовлення відмінено з поверненням товарів',
        ]);
    }

    /** @test */
    public function it_cancels_order_without_item_return()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        // Перевірка що income НЕ створено
        $this->assertEquals(0, Income::where('order_id', $order->id)->count());

        // Перевірка історії
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'comment' => 'Замовлення відмінено без повернення товарів',
        ]);
    }

    /** @test */
    public function it_can_cancel_completed_order()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
            'completed_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_cannot_cancel_already_cancelled_order()
    {
        $order = Order::create([
            'status' => 'cancelled',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => false,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_cancel_completed_order_older_than_two_weeks()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'completed_at' => now()->subWeeks(2),
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => false,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cancels_completed_order_with_item_return()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 7400,
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 2,
            'currency' => 'UAH',
        ]);

        // Створюємо outcome (симулюємо що товари були списані)
        Outcome::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 2,
            'reason_name' => 'order',
            'detail' => "Замовлення #{$order->id}",
            'order_id' => $order->id,
        ]);

        // Зменшуємо кількість на складі
        $stock = ItemWarehouseAmount::where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $stockBefore = $stock->amount;
        $stock->amount = $stockBefore - 2;
        $stock->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        // Перевірка що income створено
        $this->assertEquals(1, Income::where('order_id', $order->id)->count());

        // Перевірка що товари повернулись на склад
        $stockAfter = ItemWarehouseAmount::where('item_id', $this->item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals($stockBefore, $stockAfter->amount);

        // Перевірка історії
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'completed',
            'new_status' => 'cancelled',
            'comment' => 'Замовлення відмінено з поверненням товарів',
        ]);
    }

    /** @test */
    public function it_cancels_pending_order()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_cancels_confirmed_order()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/cancel", [
            'return_items' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    // ==================== UPDATE ORDER TESTS ====================

    /** @test */
    public function it_updates_order_basic_fields()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
            'notes' => 'Original notes',
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $updateData = [
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'currency' => 'UAH',
            'discount' => 500,
            'notes' => 'Updated notes',
            'completion_deadline' => '2025-12-31 23:59:59',
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 1,
                ],
            ],
            'order_services' => [],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'discount' => 500,
            'notes' => 'Updated notes',
        ]);
    }

    /** @test */
    public function it_can_change_warehouse_for_pending_order()
    {
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'address' => 'Another Address',
            'description' => 'Another Description',
            'city_id' => $this->warehouse->city_id,
            'country_id' => $this->warehouse->country_id,
        ]);

        // Додаємо товар на другий склад
        ItemWarehouseAmount::create([
            'item_id' => $this->item->id,
            'warehouse_id' => $warehouse2->id,
            'amount' => 50,
        ]);

        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $updateData = [
            'status' => 'pending',
            'warehouse_id' => $warehouse2->id,
            'currency' => 'UAH',
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'warehouse_id' => $warehouse2->id,
        ]);
    }

    /** @test */
    public function it_cannot_change_warehouse_for_in_progress_order()
    {
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'address' => 'Another Address',
            'description' => 'Another Description',
            'city_id' => $this->warehouse->city_id,
            'country_id' => $this->warehouse->country_id,
        ]);

        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $updateData = [
            'status' => 'in_progress',
            'warehouse_id' => $warehouse2->id,
            'currency' => 'UAH',
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", $updateData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_update_cancelled_order()
    {
        $order = Order::create([
            'status' => 'cancelled',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'cancelled',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 10,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_update_completed_order_older_than_one_week()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'completed_at' => now()->subWeeks(2),
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'completed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 500,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_update_involvement_levels_on_completed_order()
    {
        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);
        $user3 = User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
        ]);

        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'completed_at' => now(),
            'involvement_level_1_user_id' => $user2->id,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderServices()->create([
            'service_id' => $this->service->id,
            'price_per_one_unit' => 1000,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'completed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'discount' => 0,
            'involvement_level_1_user_id' => null,
            'involvement_level_2_user_id' => $user2->id,
            'involvement_level_3_user_id' => $user3->id,
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 1000,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'involvement_level_1_user_id' => null,
            'involvement_level_2_user_id' => $user2->id,
            'involvement_level_3_user_id' => $user3->id,
        ]);
    }

    /** @test */
    public function it_cannot_change_items_for_in_progress_order()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'in_progress',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 2, // Зміна кількості
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_change_items_for_completed_order()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'completed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 4000, // Зміна ціни
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_change_services_for_in_progress_order()
    {
        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 500,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderServices()->create([
            'service_id' => $this->service->id,
            'price_per_one_unit' => 500,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'in_progress',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 500,
                    'quantity' => 2, // Зміна кількості
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_change_services_for_completed_order()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 500,
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderServices()->create([
            'service_id' => $this->service->id,
            'price_per_one_unit' => 500,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'completed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'order_services' => [], // Видалення послуг
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_change_contact_for_in_progress_order()
    {
        $contact2 = Contact::create([
            'name' => 'Second Contact',
            'phone' => '380987654321',
            'email' => 'contact2@test.com',
            'preferred_platforms' => json_encode(['call']),
        ]);

        $order = Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'in_progress',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $contact2->id, // Зміна контакту
            'discount' => 0,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_change_contact_for_completed_order()
    {
        $contact2 = Contact::create([
            'name' => 'Second Contact',
            'phone' => '380987654321',
            'email' => 'contact2@test.com',
            'preferred_platforms' => json_encode(['call']),
        ]);

        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'completed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $contact2->id, // Зміна контакту
            'discount' => 0,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_change_items_for_pending_order()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'pending',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 3700,
                    'quantity' => 3, // Зміна кількості
                ],
            ],
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals(1, $order->orderItems->count());
        $this->assertEquals(3, $order->orderItems->first()->quantity);
    }

    /** @test */
    public function it_can_change_items_for_confirmed_order()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 3700,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderItems()->create([
            'item_id' => $this->item->id,
            'price_per_one_unit' => 3700,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'amount_of_advance_payment_on_card' => 500,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 4000, // Зміна ціни
                    'quantity' => 2, // Зміна кількості
                ],
            ],
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals(1, $order->orderItems->count());
        $this->assertEquals(2, $order->orderItems->first()->quantity);
        $this->assertEquals(4000, $order->orderItems->first()->price_per_one_unit);
    }

    /** @test */
    public function it_can_change_services_for_pending_order()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 500,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderServices()->create([
            'service_id' => $this->service->id,
            'price_per_one_unit' => 500,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'pending',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'order_services' => [
                [
                    'service_id' => $this->service->id,
                    'price_per_one_unit' => 600,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals(1, $order->orderServices->count());
        $this->assertEquals(2, $order->orderServices->first()->quantity);
    }

    /** @test */
    public function it_can_change_services_for_confirmed_order()
    {
        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 500,
            'amount_of_advance_payment_on_card' => 100,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $order->orderServices()->create([
            'service_id' => $this->service->id,
            'price_per_one_unit' => 500,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'discount' => 0,
            'amount_of_advance_payment_on_card' => 100,
            'order_services' => [], // Видалення послуг
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals(0, $order->orderServices->count());
    }

    /** @test */
    public function it_can_change_contact_for_pending_order()
    {
        $contact2 = Contact::create([
            'name' => 'New Contact',
            'phone' => '380555555555',
            'email' => 'new@test.com',
            'preferred_platforms' => json_encode(['call']),
        ]);

        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'pending',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $contact2->id,
            'discount' => 0,
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals($contact2->id, $order->contact_id);
    }

    /** @test */
    public function it_can_change_contact_for_confirmed_order()
    {
        $contact2 = Contact::create([
            'name' => 'New Contact',
            'phone' => '380555555555',
            'email' => 'new@test.com',
            'preferred_platforms' => json_encode(['call']),
        ]);

        $order = Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 200,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $contact2->id,
            'discount' => 0,
            'amount_of_advance_payment_on_card' => 200,
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals($contact2->id, $order->contact_id);
    }

    // ==================== STATUS TRANSITION TESTS ====================

    /** @test */
    public function it_prevents_status_change_via_update()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('pending', $order->status); // Статус не змінився
    }

    /** @test */
    public function it_prevents_invalid_status_transitions()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        // Спроба перейти з pending в completed (неможливо)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'completed',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /** @test */
    public function it_prevents_status_change_from_cancelled()
    {
        $order = Order::create([
            'status' => 'cancelled',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'pending',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_allows_cancelling_from_any_status_except_cancelled()
    {
        $statuses = ['pending', 'confirmed', 'in_progress', 'completed'];

        foreach ($statuses as $status) {
            $order = Order::create([
                'status' => $status,
                'warehouse_id' => $this->warehouse->id,
                'contact_id' => $this->contact->id,
                'currency' => 'UAH',
                'discount' => 0,
                'total_price' => 1000,
                'amount_of_advance_payment_on_card' => 0,
                'amount_of_advance_payment_via_terminal' => 0,
                'amount_of_advance_payment_as_cash' => 0,
                'amount_of_final_payment_on_card' => 0,
                'amount_of_final_payment_via_terminal' => 0,
                'amount_of_final_payment_as_cash' => 0,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$this->token->token,
            ])->postJson("/api/orders/{$order->id}/cancel", [
                'return_items' => false,
            ]);

            $response->assertStatus(200);
            $order->refresh();
            $this->assertEquals('cancelled', $order->status);
        }
    }

    /** @test */
    public function it_prevents_status_change_from_completed_to_non_cancelled()
    {
        $order = Order::create([
            'status' => 'completed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'completed_at' => now(),
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $invalidStatuses = ['pending', 'confirmed', 'in_progress'];

        foreach ($invalidStatuses as $newStatus) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$this->token->token,
            ])->patchJson("/api/orders/{$order->id}", [
                'status' => $newStatus,
                'currency' => 'UAH',
                'warehouse_id' => $this->warehouse->id,
            ]);

            $response->assertStatus(422);
        }
    }

    // ==================== READ ORDERS TESTS ====================

    /** @test */
    public function it_reads_orders_with_pagination()
    {
        // Створюємо декілька замовлень
        for ($i = 0; $i < 15; $i++) {
            Order::create([
                'status' => 'pending',
                'warehouse_id' => $this->warehouse->id,
                'contact_id' => $this->contact->id,
                'currency' => 'UAH',
                'discount' => 0,
                'total_price' => 1000 * ($i + 1),
                'amount_of_advance_payment_on_card' => 0,
                'amount_of_advance_payment_via_terminal' => 0,
                'amount_of_advance_payment_as_cash' => 0,
                'amount_of_final_payment_on_card' => 0,
                'amount_of_final_payment_via_terminal' => 0,
                'amount_of_final_payment_as_cash' => 0,
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
    }

    /** @test */
    public function it_filters_orders_by_status()
    {
        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&status_filter_value=confirmed&status_filter_mode=equal');

        $response->assertStatus(200);

        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertEquals('confirmed', $order['status']);
        }
    }

    /** @test */
    public function it_filters_orders_by_total_price()
    {
        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 500,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1500,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&total_price_filter_value=1000&total_price_filter_mode=more');

        $response->assertStatus(200);

        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertGreaterThan(1000, $order['total_price']);
        }
    }

    /** @test */
    public function it_filters_orders_by_contact_name()
    {
        $contact2 = Contact::create([
            'name' => 'John Doe',
            'phone' => '380111111111',
            'email' => 'john@test.com',
            'preferred_platforms' => json_encode(['call']),
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $contact2->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&contact_filter_value=John&contact_filter_mode=include');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $this->assertStringContainsString('John', $order['contact']['name']);
        }
    }

    /** @test */
    public function it_sorts_orders_by_total_price_desc()
    {
        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 500,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1500,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&orderField=total_price&orderValue=desc');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));

        // Перевірка що сортування правильне
        for ($i = 0; $i < count($orders) - 1; $i++) {
            $this->assertGreaterThanOrEqual($orders[$i + 1]['total_price'], $orders[$i]['total_price']);
        }
    }

    /** @test */
    public function it_filters_orders_by_remaining_to_pay()
    {
        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 300,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 1000,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&remaining_to_pay_filter_value=500&remaining_to_pay_filter_mode=more');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $remainingToPay = $order['total_price'] - (
                $order['amount_of_advance_payment_on_card'] +
                $order['amount_of_advance_payment_via_terminal'] +
                $order['amount_of_advance_payment_as_cash'] +
                $order['amount_of_final_payment_on_card'] +
                $order['amount_of_final_payment_via_terminal'] +
                $order['amount_of_final_payment_as_cash']
            );
            $this->assertGreaterThan(500, $remainingToPay);
        }
    }

    /** @test */
    public function it_filters_orders_by_advance_payment()
    {
        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 100,
            'amount_of_advance_payment_via_terminal' => 50,
            'amount_of_advance_payment_as_cash' => 50,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&advance_payment_filter_value=300&advance_payment_filter_mode=more');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $advancePayment = $order['amount_of_advance_payment_on_card'] +
                $order['amount_of_advance_payment_via_terminal'] +
                $order['amount_of_advance_payment_as_cash'];
            $this->assertGreaterThan(300, $advancePayment);
        }
    }

    /** @test */
    public function it_filters_orders_by_final_payment()
    {
        Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 300,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 100,
            'amount_of_final_payment_via_terminal' => 50,
            'amount_of_final_payment_as_cash' => 50,
        ]);

        Order::create([
            'status' => 'in_progress',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 500,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&final_payment_filter_value=300&final_payment_filter_mode=more');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $finalPayment = $order['amount_of_final_payment_on_card'] +
                $order['amount_of_final_payment_via_terminal'] +
                $order['amount_of_final_payment_as_cash'];
            $this->assertGreaterThan(300, $finalPayment);
        }
    }

    /** @test */
    public function it_filters_orders_by_completion_deadline_range()
    {
        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'completion_deadline' => '2025-12-01',
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'completion_deadline' => '2025-12-31',
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&completion_deadline_from_filter_value=2025-12-15&completion_deadline_to_filter_value=2026-01-01');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $this->assertNotNull($order['completion_deadline']);
            $this->assertGreaterThanOrEqual('2025-12-15', substr($order['completion_deadline'], 0, 10));
            $this->assertLessThan('2026-01-01', substr($order['completion_deadline'], 0, 10));
        }
    }

    /** @test */
    public function it_filters_orders_with_null_completion_deadline()
    {
        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'completion_deadline' => null,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'completion_deadline' => '2025-12-31',
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&completion_deadline_is_null_filter_value=true');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $this->assertNull($order['completion_deadline']);
        }
    }

    /** @test */
    public function it_filters_orders_by_notes()
    {
        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'notes' => 'Термінове виконання',
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'notes' => 'Звичайне замовлення',
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&notes_filter_value=Термінове&notes_filter_mode=include');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $this->assertStringContainsString('Термінове', $order['notes']);
        }
    }

    /** @test */
    public function it_filters_orders_by_contact_phone()
    {
        $contact2 = Contact::create([
            'name' => 'Another Contact',
            'phone' => '380999999999',
            'email' => 'another@test.com',
            'preferred_platforms' => json_encode(['call']),
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $contact2->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&contact_filter_value=999&contact_filter_mode=include');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
        foreach ($orders as $order) {
            $this->assertStringContainsString('999', $order['contact']['phone']);
        }
    }

    /** @test */
    public function it_sorts_orders_by_status_asc()
    {
        Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&orderField=status&orderValue=asc');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));
    }

    /** @test */
    public function it_sorts_orders_by_remaining_to_pay_desc()
    {
        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 500,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        Order::create([
            'status' => 'confirmed',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 2000,
            'amount_of_advance_payment_on_card' => 200,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->getJson('/api/orders?itemsPerPage=10&page=1&orderField=remaining_to_pay&orderValue=desc');

        $response->assertStatus(200);

        $orders = $response->json('data');
        $this->assertGreaterThan(0, count($orders));

        // Перевірка що сортування правильне
        for ($i = 0; $i < count($orders) - 1; $i++) {
            $remaining1 = $orders[$i]['total_price'] - (
                $orders[$i]['amount_of_advance_payment_on_card'] +
                $orders[$i]['amount_of_advance_payment_via_terminal'] +
                $orders[$i]['amount_of_advance_payment_as_cash'] +
                $orders[$i]['amount_of_final_payment_on_card'] +
                $orders[$i]['amount_of_final_payment_via_terminal'] +
                $orders[$i]['amount_of_final_payment_as_cash']
            );
            $remaining2 = $orders[$i + 1]['total_price'] - (
                $orders[$i + 1]['amount_of_advance_payment_on_card'] +
                $orders[$i + 1]['amount_of_advance_payment_via_terminal'] +
                $orders[$i + 1]['amount_of_advance_payment_as_cash'] +
                $orders[$i + 1]['amount_of_final_payment_on_card'] +
                $orders[$i + 1]['amount_of_final_payment_via_terminal'] +
                $orders[$i + 1]['amount_of_final_payment_as_cash']
            );
            $this->assertGreaterThanOrEqual($remaining2, $remaining1);
        }
    }

    // ==================== VALIDATION TESTS ====================

    /** @test */
    public function it_rejects_invalid_currency()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'currency' => 'INVALID',
            'discount' => 0,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_invalid_status()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->patchJson("/api/orders/{$order->id}", [
            'status' => 'invalid_status',
            'currency' => 'UAH',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /** @test */
    public function it_rejects_negative_amounts()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'currency' => 'UAH',
            'discount' => -100,
            'amount_of_advance_payment_on_card' => -50,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_nonexistent_warehouse_id()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', [
            'warehouse_id' => 99999,
            'currency' => 'UAH',
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => $this->item->id,
                    'price_per_one_unit' => 1000,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_nonexistent_item_id()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'currency' => 'UAH',
            'discount' => 0,
            'order_items' => [
                [
                    'item_id' => 99999,
                    'price_per_one_unit' => 1000,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_nonexistent_service_id()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'currency' => 'UAH',
            'discount' => 0,
            'order_services' => [
                [
                    'service_id' => 99999,
                    'price_per_one_unit' => 500,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_nonexistent_contact_id()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/orders', [
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => 99999,
            'currency' => 'UAH',
            'discount' => 0,
        ]);

        $response->assertStatus(422);
    }

    // ==================== DELETE ORDER TESTS ====================

    /** @test */
    public function it_cannot_delete_order()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(404);
    }

    // ==================== ORDER STATUS HISTORY TESTS ====================

    /** @test */
    public function it_records_all_status_changes_in_history()
    {
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $this->contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 1000,
            'amount_of_advance_payment_on_card' => 0,
            'amount_of_advance_payment_via_terminal' => 0,
            'amount_of_advance_payment_as_cash' => 0,
            'amount_of_final_payment_on_card' => 0,
            'amount_of_final_payment_via_terminal' => 0,
            'amount_of_final_payment_as_cash' => 0,
        ]);

        // pending → confirmed
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/confirm", []);

        // confirmed → in_progress
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/start-work");

        // in_progress → completed
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/orders/{$order->id}/complete", []);

        // Перевірка що всі переходи записані
        $history = OrderStatusHistory::where('order_id', $order->id)->orderBy('id')->get();

        $this->assertCount(3, $history);

        $this->assertEquals('pending', $history[0]->old_status);
        $this->assertEquals('confirmed', $history[0]->new_status);

        $this->assertEquals('confirmed', $history[1]->old_status);
        $this->assertEquals('in_progress', $history[1]->new_status);

        $this->assertEquals('in_progress', $history[2]->old_status);
        $this->assertEquals('completed', $history[2]->new_status);
    }
}
