<?php

namespace Tests\Feature;

use App\Models\AccessToken;
use App\Models\Allowense;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AccessToken $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Створення ролі з дозволами
        $role = Role::create(['name' => 'Test Admin']);

        $allowenses = [
            ['section' => 'contacts', 'action' => 'create'],
            ['section' => 'contacts', 'action' => 'read'],
            ['section' => 'contacts', 'action' => 'update'],
            ['section' => 'contacts', 'action' => 'delete'],
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
    }

    /** @test */
    public function it_can_delete_contact_without_orders()
    {
        // Створення контакту без замовлень
        $contact = Contact::create([
            'name' => 'Test Contact',
            'phone' => '+380501234567',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'preferred_platforms' => ['viber', 'telegram'],
            'additional_info' => 'Test info',
        ]);

        // Спроба видалення контакту
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/contacts/{$contact->id}");

        // Перевірка успішного видалення
        $response->assertStatus(200);
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_cannot_delete_contact_with_orders()
    {
        // Створення контакту
        $contact = Contact::create([
            'name' => 'Test Contact with Orders',
            'phone' => '+380501234567',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'preferred_platforms' => ['viber', 'telegram'],
            'additional_info' => 'Test info',
        ]);

        // Створення замовлення з цим контактом
        Order::create([
            'status' => 'pending',
            'contact_id' => $contact->id,
            'currency' => 'UAH',
            'total_price' => 1000,
        ]);

        // Спроба видалення контакту
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/contacts/{$contact->id}");

        // Перевірка, що видалення заборонено
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Неможливо видалити контакт, оскільки він використовується в замовленнях',
        ]);

        // Перевірка, що контакт все ще існує в базі
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_returns_error_when_deleting_non_existent_contact()
    {
        $nonExistentId = 99999;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/contacts/{$nonExistentId}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Контакт не знайдено',
        ]);
    }

    /** @test */
    public function it_can_delete_contact_after_all_orders_are_removed()
    {
        // Створення контакту
        $contact = Contact::create([
            'name' => 'Test Contact',
            'phone' => '+380501234567',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'preferred_platforms' => ['viber', 'telegram'],
            'additional_info' => 'Test info',
        ]);

        // Створення замовлення
        $order = Order::create([
            'status' => 'pending',
            'contact_id' => $contact->id,
            'currency' => 'UAH',
            'total_price' => 1000,
        ]);

        // Видалення замовлення
        $order->delete();

        // Тепер спроба видалення контакту повинна бути успішною
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token->token,
        ])->deleteJson("/api/contacts/{$contact->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}
