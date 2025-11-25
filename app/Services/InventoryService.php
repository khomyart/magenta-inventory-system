<?php

namespace App\Services;

use App\Models\Income;
use App\Models\Item;
use App\Models\ItemWarehouseAmount;
use App\Models\Order;
use App\Models\Outcome;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Перевірка наявності товарів на складі
     *
     * @param  array  $items Масив товарів з item_id та quantity
     * @param  int  $warehouseId ID складу
     * @return array Масив помилок (порожній якщо все OK)
     */
    public function checkStockAvailability(array $items, int $warehouseId): array
    {
        $errors = [];

        foreach ($items as $item) {
            $stock = ItemWarehouseAmount::where('item_id', $item['item_id'])
                ->where('warehouse_id', $warehouseId)
                ->first();

            $itemsDetail = Item::where('id', $item['item_id'])->first();

            if (! $stock) {
                $errors[] = "Товар ID {$item['item_id']} не знайдено на складі";
            } elseif ($stock->amount < $item['quantity'] && $itemsDetail) {
                $errors[] = "Недостатньо товару ID {$item['item_id']} ({$itemsDetail['title']}) на складі. Доступно: {$stock->amount}, потрібно: {$item['quantity']}";
            }
        }

        return $errors;
    }

    /**
     * Створення Outcome записів (списання товарів зі складу)
     * Використовується при підтвердженні замовлення (status = confirmed)
     *
     * @param  Order  $order Замовлення
     * @param  array  $items Масив товарів з item_id, quantity
     * @param  string  $reason Причина списання (за замовчуванням "order")
     * @return void
     *
     * @throws \Exception
     */
    public function recordOutcome(Order $order, array $items, string $reason = 'order'): void
    {
        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                // Створюємо запис outcome
                Outcome::create([
                    'item_id' => $item['item_id'],
                    'warehouse_id' => $order->warehouse_id,
                    'amount' => $item['quantity'],
                    'reason_name' => $reason,
                    'additional_reason_name' => null,
                    'detail' => "Замовлення #{$order->id}",
                    'order_id' => $order->id,
                ]);

                // Оновлюємо кількість на складі
                $this->reduceStock($item['item_id'], $order->warehouse_id, $item['quantity']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Створення Income записів (повернення товарів на склад)
     * Використовується при скасуванні замовлення (status = cancelled)
     *
     * @param  Order  $order Замовлення
     * @param  array  $items Масив товарів з item_id, quantity, price_per_one_unit
     * @param  string  $currency Валюта
     * @return void
     *
     * @throws \Exception
     */
    public function recordIncome(Order $order, array $items, string $currency): void
    {
        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                // Створюємо запис income
                Income::create([
                    'item_id' => $item['item_id'],
                    'warehouse_id' => $order->warehouse_id,
                    'amount_of_items' => $item['quantity'],
                    'price_per_item' => $item['price_per_one_unit'] ?? 0,
                    'currency' => $currency,
                    'order_id' => $order->id,
                ]);

                // Оновлюємо кількість на складі
                $this->increaseStock($item['item_id'], $order->warehouse_id, $item['quantity']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Зменшення кількості товару на складі
     *
     * @param  int  $itemId ID товару
     * @param  int  $warehouseId ID складу
     * @param  int  $quantity Кількість для зменшення
     * @return void
     */
    private function reduceStock(int $itemId, int $warehouseId, int $quantity): void
    {
        $stock = ItemWarehouseAmount::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($stock) {
            $newAmount = $stock->amount - $quantity;

            if ($newAmount <= 0) {
                // Видаляємо запис якщо кількість 0 або менше
                $stock->delete();
            } else {
                // Оновлюємо кількість
                $stock->amount = $newAmount;
                $stock->save();
            }
        }
    }

    /**
     * Збільшення кількості товару на складі
     *
     * @param  int  $itemId ID товару
     * @param  int  $warehouseId ID складу
     * @param  int  $quantity Кількість для збільшення
     * @return void
     */
    private function increaseStock(int $itemId, int $warehouseId, int $quantity): void
    {
        $stock = ItemWarehouseAmount::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($stock) {
            // Якщо запис існує - збільшуємо кількість
            $stock->amount += $quantity;
            $stock->save();
        } else {
            // Якщо запису немає - створюємо новий
            ItemWarehouseAmount::create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'amount' => $quantity,
            ]);
        }
    }

    /**
     * Відкат outcome операції (видалення outcome записів та повернення товарів)
     * Використовується при редагуванні замовлення
     *
     * @param  int  $orderId ID замовлення
     * @return void
     */
    public function rollbackOutcome(int $orderId): void
    {
        $outcomes = Outcome::where('order_id', $orderId)->get();

        foreach ($outcomes as $outcome) {
            // Повертаємо товари на склад
            $this->increaseStock($outcome->item_id, $outcome->warehouse_id, $outcome->amount);

            // Видаляємо outcome запис
            $outcome->delete();
        }
    }

    /**
     * Відкат income операції (видалення income записів та списання товарів)
     * Використовується при редагуванні замовлення
     *
     * @param  int  $orderId ID замовлення
     * @return void
     */
    public function rollbackIncome(int $orderId): void
    {
        $incomes = Income::where('order_id', $orderId)->get();

        foreach ($incomes as $income) {
            // Списуємо товари зі складу
            $this->reduceStock($income->item_id, $income->warehouse_id, $income->amount_of_items);

            // Видаляємо income запис
            $income->delete();
        }
    }
}
