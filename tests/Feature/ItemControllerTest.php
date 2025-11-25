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
use App\Models\Move;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outcome;
use App\Models\Role;
use App\Models\Size;
use App\Models\Type;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AccessToken $token;

    private Warehouse $warehouse;

    private Type $type;

    private Size $size;

    private Color $color;

    private Gender $gender;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Створення ролі з дозволами
        $role = Role::create(['name' => 'Test Admin']);

        $allowenses = [
            ['section' => 'items', 'action' => 'create'],
            ['section' => 'items', 'action' => 'read'],
            ['section' => 'items', 'action' => 'update'],
            ['section' => 'items', 'action' => 'delete'],
            ['section' => 'items', 'action' => 'income'],
            ['section' => 'items', 'action' => 'outcome'],
            ['section' => 'items', 'action' => 'move'],
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
        $this->type = Type::create([
            'name' => 'Test Type',
            'number_in_row' => 1,
        ]);

        $this->size = Size::create([
            'value' => 'M',
            'description' => 'Medium',
            'number_in_row' => 1,
        ]);

        $this->color = Color::create([
            'value' => '#000000',
            'article' => 'BLK',
            'description' => 'Black color',
            'text_color_value' => '#FFFFFF',
        ]);

        $this->gender = Gender::create([
            'name' => 'Unisex',
            'number_in_row' => 1,
        ]);

        $this->unit = Unit::create([
            'name' => 'шт',
            'description' => 'штука',
        ]);
    }

    /** @test */
    public function it_creates_item_with_basic_fields()
    {
        $itemData = [
            'article' => 'TEST-001',
            'group_id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Test Item',
            'description' => 'Test Description',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
            'gender_id' => $this->gender->id,
            'size_id' => $this->size->id,
            'color_id' => $this->color->id,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items', $itemData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'article' => 'TEST-001',
            'title' => 'Test Item',
            'price' => 100,
            'currency' => 'UAH',
        ]);
    }

    /** @test */
    public function it_creates_item_without_optional_fields()
    {
        $itemData = [
            'article' => 'TEST-002',
            'group_id' => '550e8400-e29b-41d4-a716-446655440001',
            'title' => 'Test Item 2',
            'price' => 200,
            'currency' => 'USD',
            'lack' => 10,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items', $itemData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'article' => 'TEST-002',
            'title' => 'Test Item 2',
            'gender_id' => null,
            'size_id' => null,
            'color_id' => null,
        ]);
    }

    /** @test */
    public function it_prevents_creating_duplicate_items()
    {
        $itemData = [
            'article' => 'TEST-003',
            'group_id' => '550e8400-e29b-41d4-a716-446655440002',
            'title' => 'Test Item 3',
            'price' => 300,
            'currency' => 'EUR',
            'lack' => 15,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
            'gender_id' => $this->gender->id,
            'size_id' => $this->size->id,
            'color_id' => $this->color->id,
        ];

        // Створюємо перший раз
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items', $itemData);

        // Спробуємо створити дубль
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items', $itemData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_creates_multiple_items_successfully()
    {
        // Створюємо кілька товарів
        for ($i = 1; $i <= 5; $i++) {
            Item::create([
                'article' => "TEST-{$i}",
                'group_id' => "550e8400-e29b-41d4-a716-44665544000{$i}",
                'title' => "Test Item {$i}",
                'model' => 'Test Model',
                'price' => 100 * $i,
                'currency' => 'UAH',
                'lack' => 5,
                'type_id' => $this->type->id,
                'unit_id' => $this->unit->id,
            ]);
        }

        // Перевіряємо що всі товари створені
        $this->assertEquals(5, Item::count());

        // Перевіряємо що конкретні товари існують
        $this->assertDatabaseHas('items', [
            'article' => 'TEST-1',
            'title' => 'Test Item 1',
        ]);

        $this->assertDatabaseHas('items', [
            'article' => 'TEST-5',
            'title' => 'Test Item 5',
        ]);
    }

    /** @test */
    public function it_updates_item_basic_fields()
    {
        $item = Item::create([
            'article' => 'TEST-UPD-001',
            'group_id' => '550e8400-e29b-41d4-a716-446655440010',
            'title' => 'Original Title',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        $updateData = [
            'article' => 'TEST-UPD-001',
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'price' => 150,
            'currency' => 'USD',
            'lack' => 10,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson("/api/items/{$item->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'title' => 'Updated Title',
            'price' => 150,
            'currency' => 'USD',
            'lack' => 10,
        ]);
    }

    /** @test */
    public function it_deletes_item_when_no_stock()
    {
        $item = Item::create([
            'article' => 'TEST-DEL-001',
            'group_id' => '550e8400-e29b-41d4-a716-446655440020',
            'title' => 'Item to Delete',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_item_with_stock()
    {
        $item = Item::create([
            'article' => 'TEST-DEL-002',
            'group_id' => '550e8400-e29b-41d4-a716-446655440021',
            'title' => 'Item with Stock',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        // Додаємо товар на склад
        ItemWarehouseAmount::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 10,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function it_sets_income_for_items()
    {
        $item = Item::create([
            'article' => 'TEST-INC-001',
            'group_id' => '550e8400-e29b-41d4-a716-446655440030',
            'title' => 'Item for Income',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        $incomeData = [
            'warehouses' => [
                [
                    'id' => $this->warehouse->id,
                    'batches' => [
                        [
                            'amount' => 10,
                            'price' => 100,
                            'currency' => 'UAH',
                            'items' => [
                                ['id' => $item->id],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items/income', $incomeData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('income', [
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount_of_items' => 10,
        ]);

        $this->assertDatabaseHas('item_warehouse_amounts', [
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 10,
        ]);
    }

    /** @test */
    public function it_sets_outcome_for_items()
    {
        $item = Item::create([
            'article' => 'TEST-OUT-001',
            'group_id' => '550e8400-e29b-41d4-a716-446655440040',
            'title' => 'Item for Outcome',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        // Спочатку додаємо товар на склад
        ItemWarehouseAmount::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 20,
        ]);

        $outcomeData = [
            'warehouseId' => $this->warehouse->id,
            'items' => [
                [
                    'id' => $item->id,
                    'amount' => 5,
                    'reason' => 'sale',
                    'reasonDetail' => 'Sold to customer',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items/outcome', $outcomeData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('outcome', [
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 5,
        ]);

        $this->assertDatabaseHas('item_warehouse_amounts', [
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 15,
        ]);
    }

    /** @test */
    public function it_prevents_outcome_when_insufficient_stock()
    {
        $item = Item::create([
            'article' => 'TEST-OUT-002',
            'group_id' => '550e8400-e29b-41d4-a716-446655440041',
            'title' => 'Item with Low Stock',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        ItemWarehouseAmount::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 5,
        ]);

        $outcomeData = [
            'warehouseId' => $this->warehouse->id,
            'items' => [
                [
                    'id' => $item->id,
                    'amount' => 10, // More than available
                    'reason' => 'sale',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items/outcome', $outcomeData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_moves_items_between_warehouses()
    {
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'address' => 'Second Address',
            'description' => 'Second Description',
            'city_id' => $this->warehouse->city_id,
            'country_id' => $this->warehouse->country_id,
        ]);

        $item = Item::create([
            'article' => 'TEST-MOV-001',
            'group_id' => '550e8400-e29b-41d4-a716-446655440050',
            'title' => 'Item to Move',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        ItemWarehouseAmount::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 20,
        ]);

        $moveData = [
            'fromWarehouseId' => $this->warehouse->id,
            'toWarehouseId' => $warehouse2->id,
            'items' => [
                [
                    'id' => $item->id,
                    'amount' => 10,
                    'reason' => 'transfer',
                    'reasonDetail' => 'Moving stock',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items/move', $moveData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('move', [
            'item_id' => $item->id,
            'from_warehouse_id' => $this->warehouse->id,
            'to_warehouse_id' => $warehouse2->id,
            'amount' => 10,
        ]);

        $this->assertDatabaseHas('item_warehouse_amounts', [
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 10,
        ]);

        $this->assertDatabaseHas('item_warehouse_amounts', [
            'item_id' => $item->id,
            'warehouse_id' => $warehouse2->id,
            'amount' => 10,
        ]);
    }

    /** @test */
    public function it_prevents_moving_to_same_warehouse()
    {
        $item = Item::create([
            'article' => 'TEST-MOV-002',
            'group_id' => '550e8400-e29b-41d4-a716-446655440051',
            'title' => 'Item for Invalid Move',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        ItemWarehouseAmount::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 20,
        ]);

        $moveData = [
            'fromWarehouseId' => $this->warehouse->id,
            'toWarehouseId' => $this->warehouse->id, // Same warehouse
            'items' => [
                [
                    'id' => $item->id,
                    'amount' => 10,
                    'reason' => 'transfer',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->postJson('/api/items/move', $moveData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_deletes_item_with_zero_stock()
    {
        $item = Item::create([
            'article' => 'TEST-DEL-003',
            'group_id' => '550e8400-e29b-41d4-a716-446655440022',
            'title' => 'Item with Zero Stock',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        // Створюємо запис з нульовою кількістю
        ItemWarehouseAmount::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_item_referenced_in_orders()
    {
        $item = Item::create([
            'article' => 'TEST-DEL-004',
            'group_id' => '550e8400-e29b-41d4-a716-446655440023',
            'title' => 'Item in Order',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        // Створюємо контакт для замовлення
        $contact = Contact::create([
            'name' => 'Test Contact',
            'phone' => '380501234567',
            'email' => 'test@test.com',
            'preferred_platforms' => json_encode(['call']),
        ]);

        // Створюємо замовлення
        $order = Order::create([
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'contact_id' => $contact->id,
            'currency' => 'UAH',
            'discount' => 0,
            'total_price' => 100,
            'total_paid' => 0,
            'total_unpaid' => 100,
            'advance_payment' => 0,
        ]);

        // Додаємо товар до замовлення
        OrderItem::create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price_per_one_unit' => 100,
            'quantity' => 1,
            'currency' => 'UAH',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Неможливо видалити: предмет використовується в замовленнях',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_item_with_income_history()
    {
        $item = Item::create([
            'article' => 'TEST-DEL-005',
            'group_id' => '550e8400-e29b-41d4-a716-446655440024',
            'title' => 'Item with Income History',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        // Створюємо історію надходження
        Income::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'price_per_item' => 100,
            'currency' => 'UAH',
            'amount_of_items' => 10,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Неможливо видалити: предмет має історію надходжень',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_item_with_outcome_history()
    {
        $item = Item::create([
            'article' => 'TEST-DEL-006',
            'group_id' => '550e8400-e29b-41d4-a716-446655440025',
            'title' => 'Item with Outcome History',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        // Створюємо історію списання
        Outcome::create([
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'amount' => 5,
            'reason_name' => 'sale',
            'detail' => 'Test sale',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Неможливо видалити: предмет має історію списань',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_item_with_move_history()
    {
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'address' => 'Second Address',
            'description' => 'Second Description',
            'city_id' => $this->warehouse->city_id,
            'country_id' => $this->warehouse->country_id,
        ]);

        $item = Item::create([
            'article' => 'TEST-DEL-007',
            'group_id' => '550e8400-e29b-41d4-a716-446655440026',
            'title' => 'Item with Move History',
            'model' => 'Test Model',
            'price' => 100,
            'currency' => 'UAH',
            'lack' => 5,
            'type_id' => $this->type->id,
            'unit_id' => $this->unit->id,
        ]);

        // Створюємо історію переміщення
        Move::create([
            'item_id' => $item->id,
            'from_warehouse_id' => $this->warehouse->id,
            'to_warehouse_id' => $warehouse2->id,
            'amount' => 10,
            'reason_name' => 'transfer',
            'detail' => 'Test transfer',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Неможливо видалити: предмет має історію переміщень',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }
}
