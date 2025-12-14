<?php

namespace App\Http\Controllers;

use App\Helpers\AuthAPI;
use App\Helpers\ErrorHandler;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public string $section = 'orders';

    public AuthAPI|false $authAPI;

    public function __construct(Request $request)
    {
        $this->authAPI = AuthAPI::isAuthenticated($request->bearerToken(), $request->ip());
    }

    private function getFieldsAndRulesForCreatingOrUpdatingSection(): array
    {
        return [
            // pending - коли тільки створили
            // confirmed - коли аванс внесли
            // in progress - коли почали працювати
            // completed - робота завершена
            // canceled - відмінено
            //            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])],
            'amount_of_advance_payment_on_card' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_advance_payment_via_terminal' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_advance_payment_as_cash' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_final_payment_on_card' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_final_payment_via_terminal' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_final_payment_as_cash' => ['nullable', 'numeric', 'gte:0'],
            'currency' => ['required', Rule::in(['UAH', 'USD', 'EUR'])],
            'discount' => ['nullable', 'numeric', 'gte:0'],
            'completion_deadline' => ['nullable', 'date'],
            'fully_payed_at' => ['nullable', 'date'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'involvement_level_1_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'involvement_level_2_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'involvement_level_3_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'order_items' => ['nullable', 'array'],
            'order_items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'order_items.*.price_per_one_unit' => ['required', 'numeric', 'gte:0'],
            'order_items.*.quantity' => ['required', 'integer', 'gte:1'],
            'order_services' => ['nullable', 'array'],
            'order_services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'order_services.*.price_per_one_unit' => ['required', 'numeric', 'gte:0'],
            'order_services.*.quantity' => ['required', 'integer', 'gte:1'],
        ];
    }

    private function getFieldRulesForOrderingSection(): array
    {
        return [
            'orderField' => [
                'string', Rule::in(['id', 'status', 'total_price', 'remaining_to_pay', 'created_at', 'completion_deadline',
                    'advance_payment', 'final_payment', 'completed_at', 'fully_payed_at', 'contact', 'notes', ]), 'nullable',
            ],
        ];
    }

    private function getFieldsAndRulesForFilteringSection(): array
    {
        $stringFilters = ['include', 'exclude', 'equal', 'notequal'];
        $numericFilters = ['more', 'less', 'equal', 'notequal'];
        $dateFilters = ['include', 'more', 'less'];

        return [
            'id_filter_value' => ['string', 'nullable'],
            'id_filter_mode' => ['string', Rule::in($numericFilters), 'nullable'],
            'status_filter_value' => ['string', 'nullable'],
            'status_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'total_price_filter_value' => ['string', 'nullable'],
            'total_price_filter_mode' => ['string', Rule::in($numericFilters), 'nullable'],
            'remaining_to_pay_filter_value' => ['string', 'nullable'],
            'remaining_to_pay_filter_mode' => ['string', Rule::in($numericFilters), 'nullable'],
            'contact_filter_value' => ['string', 'nullable'],
            'contact_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'advance_payment_filter_value' => ['string', 'nullable'],
            'advance_payment_filter_mode' => ['string', Rule::in($numericFilters), 'nullable'],
            'final_payment_filter_value' => ['string', 'nullable'],
            'final_payment_filter_mode' => ['string', Rule::in($numericFilters), 'nullable'],
            'completion_deadline_from_filter_value' => ['string', 'nullable'],
            'completion_deadline_from_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'completion_deadline_to_filter_value' => ['string', 'nullable'],
            'completion_deadline_to_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'completion_deadline_is_null_filter_value' => ['nullable', 'in:true,false,1,0'],
            'created_at_from_filter_value' => ['string', 'nullable'],
            'created_at_from_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'created_at_to_filter_value' => ['string', 'nullable'],
            'created_at_to_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'created_at_is_null_filter_value' => ['nullable', 'in:true,false,1,0'],
            'completed_at_from_filter_value' => ['string', 'nullable'],
            'completed_at_from_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'completed_at_to_filter_value' => ['string', 'nullable'],
            'completed_at_to_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'completed_at_is_null_filter_value' => ['nullable', 'in:true,false,1,0'],
            'fully_payed_at_from_filter_value' => ['string', 'nullable'],
            'fully_payed_at_from_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'fully_payed_at_to_filter_value' => ['string', 'nullable'],
            'fully_payed_at_to_filter_mode' => ['string', Rule::in($dateFilters), 'nullable'],
            'fully_payed_at_is_null_filter_value' => ['nullable', 'in:true,false,1,0'],
            'notes_filter_value' => ['string', 'nullable'],
            'notes_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
            'involved_users_filter_value' => ['string', 'nullable'],
            'involved_users_filter_mode' => ['string', Rule::in($stringFilters), 'nullable'],
        ];
    }

    private function getListOfFilteringFields(): array
    {
        return [
            'id',
            'status',
            'total_price',
            'remaining_to_pay',
            'contact',
            'advance_payment',
            'final_payment',
            'completion_deadline_from',
            'completion_deadline_to',
            'created_at_from',
            'created_at_to',
            'completed_at_from',
            'completed_at_to',
            'fully_payed_at_from',
            'fully_payed_at_to',
            'notes',
            'involved_users',
        ];
    }

    public function getSectionModel(): Order
    {
        return new Order();
    }

    private function calculateTotalPrice(array $orderItems, array $orderServices, float $discount): float
    {
        $total = 0;

        // Calculate items total
        foreach ($orderItems as $item) {
            $total += $item['price_per_one_unit'] * $item['quantity'];
        }

        // Calculate services total
        foreach ($orderServices as $service) {
            $total += $service['price_per_one_unit'] * $service['quantity'];
        }

        // Apply discount
        $total -= $discount;

        return max(0, $total); // Ensure total is not negative
    }

    private function canModifyOrder(?Order $order): array
    {
        if (! $order) {
            return ['can_modify' => false, 'message' => 'Замовлення не знайдено'];
        }

        if ($order->completed_at) {
            $twoWeeksAgo = now()->subWeeks(2);
            if ($order->completed_at->lessThan($twoWeeksAgo)) {
                return ['can_modify' => false, 'message' => 'Не можна змінювати замовлення, яке було завершено більше 2 тижнів тому'];
            }
        }

        return ['can_modify' => true];
    }

    /**
     * Перевірка можливості зміни статусу замовлення
     * Сувора послідовність: pending → confirmed → in_progress → completed
     * Можна відмінити (cancelled) з будь-якого статусу, включаючи completed
     */
    private function canChangeStatus(string $oldStatus, string $newStatus): array
    {
        // Якщо статус не змінюється - дозволено
        if ($oldStatus === $newStatus) {
            return ['can_change' => true];
        }

        // Cancelled - термінальний статус, з нього не можна нікуди перейти
        if ($oldStatus === 'cancelled') {
            return [
                'can_change' => false,
                'message' => "Не можна змінити статус з термінального статусу 'cancelled'",
            ];
        }

        // З completed можна перейти тільки в cancelled
        if ($oldStatus === 'completed') {
            if ($newStatus === 'cancelled') {
                return ['can_change' => true];
            }

            return [
                'can_change' => false,
                'message' => "З статусу 'completed' можна перейти тільки в статус 'cancelled'",
            ];
        }

        // Дозволені переходи для кожного статусу
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
        ];

        // Перевіряємо чи дозволений перехід
        if (! isset($allowedTransitions[$oldStatus]) || ! in_array($newStatus, $allowedTransitions[$oldStatus])) {
            return [
                'can_change' => false,
                'message' => "Неможливо змінити статус з '{$oldStatus}' на '{$newStatus}'. Дозволені переходи: ".implode(', ', $allowedTransitions[$oldStatus] ?? []),
            ];
        }

        return ['can_change' => true];
    }

    /**
     * Запис зміни статусу в історію
     */
    private function recordStatusChange(int $orderId, ?string $oldStatus, string $newStatus, ?string $comment = null): void
    {
        \App\Models\OrderStatusHistory::create([
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'changed_by' => $this->authAPI->user->id ?? null,
        ]);
    }

    public function read(Request $request): AnonymousResourceCollection|Response
    {
        $rules = [
            'itemsPerPage' => 'required|numeric',
            'page' => 'required|numeric',
            'orderValue' => ['string', Rule::in(['desc', 'asc']), 'nullable'],
            ...$this->getFieldsAndRulesForFilteringSection(),
            ...$this->getFieldRulesForOrderingSection(),
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        $section = Order::query()->with([
            'contact',
            'warehouse.city.country',
            'orderItems.item.type',
            'orderItems.item.color',
            'orderItems.item.size',
            'orderItems.item.gender',
            'orderItems.item.unit',
            'orderItems.item.images',
            'orderServices.service',
            'involvementLevel1User',
            'involvementLevel2User',
            'involvementLevel3User',
        ]);

        // Check for is_null filters first
        $dateFieldsWithNullCheck = ['completion_deadline', 'created_at', 'completed_at', 'fully_payed_at'];
        foreach ($dateFieldsWithNullCheck as $dateField) {
            $isNullFilter = $data["{$dateField}_is_null_filter_value"] ?? null;
            if (in_array($isNullFilter, [true, 'true', '1', 1], true)) {
                $section->whereNull($dateField);
            }
        }

        // Filtering logic
        foreach ($this->getListOfFilteringFields() as $field) {
            // Skip date range filtering if is_null filter is active for this field
            $baseField = null;
            if (in_array($field, ['completion_deadline_from', 'completion_deadline_to'])) {
                $baseField = 'completion_deadline';
            } elseif (in_array($field, ['created_at_from', 'created_at_to'])) {
                $baseField = 'created_at';
            } elseif (in_array($field, ['completed_at_from', 'completed_at_to'])) {
                $baseField = 'completed_at';
            } elseif (in_array($field, ['fully_payed_at_from', 'fully_payed_at_to'])) {
                $baseField = 'fully_payed_at';
            }

            if ($baseField !== null) {
                $isNullFilter = $data["{$baseField}_is_null_filter_value"] ?? null;
                if ($isNullFilter === true || $isNullFilter === 'true' || $isNullFilter === 1) {
                    continue; // Skip date range filters if is_null is active
                }
            }

            $searchValue = $data["{$field}_filter_value"] ?? null;
            $filterMode = match ($field) {
                'completion_deadline_from', 'created_at_from', 'completed_at_from', 'fully_payed_at_from' => 'more',
                'completion_deadline_to', 'created_at_to', 'completed_at_to', 'fully_payed_at_to' => 'less',
                default => $data["{$field}_filter_mode"] ?? null
            };
            $searchOperator = $this->getWhereOperator($filterMode);

            if ($searchValue != null) {
                // Handle date range filters
                if ($field === 'completion_deadline_from' || $field === 'completion_deadline_to') {
                    $field = 'completion_deadline';
                }
                if ($field === 'created_at_from' || $field === 'created_at_to') {
                    $field = 'created_at';
                }
                if ($field === 'completed_at_from' || $field === 'completed_at_to') {
                    $field = 'completed_at';
                }
                if ($field === 'fully_payed_at_from' || $field === 'fully_payed_at_to') {
                    $field = 'fully_payed_at';
                }

                // Handle contact field (search by name or phone)
                if ($field === 'contact') {
                    if (empty($searchValue)) {
                        continue;
                    }
                    if ($searchOperator === 'like') {
                        $section->whereHas('contact', function ($query) use ($searchValue) {
                            $query->where('name', 'like', "%{$searchValue}%")
                                ->orWhere('phone', 'like', "%{$searchValue}%");
                        });
                    } elseif ($searchOperator === 'not like') {
                        // Show orders where contact does NOT contain search value in BOTH name AND phone
                        $section->where(function ($query) use ($searchValue) {
                            $query->whereHas('contact', function ($subQuery) use ($searchValue) {
                                $subQuery->where('name', 'not like', "%{$searchValue}%")
                                    ->where('phone', 'not like', "%{$searchValue}%");
                            })
                                ->orWhereNull('contact_id'); // Include orders without contacts
                        });
                    }

                    continue;
                }

                // Handle involved_users field (search by user name across all involvement levels)
                if ($field === 'involved_users') {
                    if (empty($searchValue)) {
                        continue;
                    }
                    if ($searchOperator === 'like') {
                        $section->where(function ($query) use ($searchValue) {
                            $query->whereHas('involvementLevel1User', function ($subQuery) use ($searchValue) {
                                $subQuery->where('name', 'like', "%{$searchValue}%");
                            })
                                ->orWhereHas('involvementLevel2User', function ($subQuery) use ($searchValue) {
                                    $subQuery->where('name', 'like', "%{$searchValue}%");
                                })
                                ->orWhereHas('involvementLevel3User', function ($subQuery) use ($searchValue) {
                                    $subQuery->where('name', 'like', "%{$searchValue}%");
                                });
                        });
                    } elseif ($searchOperator === 'not like') {
                        // Show orders where involved users do NOT contain search value in any level
                        $section->where(function ($query) use ($searchValue) {
                            $query->whereDoesntHave('involvementLevel1User', function ($subQuery) use ($searchValue) {
                                $subQuery->where('name', 'like', "%{$searchValue}%");
                            })
                                ->whereDoesntHave('involvementLevel2User', function ($subQuery) use ($searchValue) {
                                    $subQuery->where('name', 'like', "%{$searchValue}%");
                                })
                                ->whereDoesntHave('involvementLevel3User', function ($subQuery) use ($searchValue) {
                                    $subQuery->where('name', 'like', "%{$searchValue}%");
                                });
                        });
                    }

                    continue;
                }

                // Handle calculated fields: advance_payment
                if ($field === 'advance_payment') {
                    if (empty($searchValue) && (int) $searchValue !== 0) {
                        continue;
                    }
                    $section->whereRaw(
                        "(amount_of_advance_payment_on_card + amount_of_advance_payment_via_terminal + amount_of_advance_payment_as_cash) {$searchOperator} ?",
                        [$searchValue]
                    );

                    continue;
                }

                // Handle calculated fields: final_payment
                if ($field === 'final_payment') {
                    if (empty($searchValue) && (int) $searchValue !== 0) {
                        continue;
                    }
                    $section->whereRaw(
                        "(amount_of_final_payment_on_card + amount_of_final_payment_via_terminal + amount_of_final_payment_as_cash) {$searchOperator} ?",
                        [$searchValue]
                    );

                    continue;
                }

                // Handle calculated fields: remaining_to_pay
                if ($field === 'remaining_to_pay') {
                    if (empty($searchValue) && (int) $searchValue !== 0) {
                        continue;
                    }
                    $section->whereRaw(
                        "(total_price - (amount_of_advance_payment_on_card + amount_of_advance_payment_via_terminal + amount_of_advance_payment_as_cash + amount_of_final_payment_on_card + amount_of_final_payment_via_terminal + amount_of_final_payment_as_cash)) {$searchOperator} ?",
                        [$searchValue]
                    );

                    continue;
                }

                // Standard filtering
                if ($searchOperator === 'like') {
                    $section->where($field, 'like', "%{$searchValue}%");

                    continue;
                }
                if ($searchOperator === 'not like') {
                    $section->whereNot(function ($query) use ($searchValue, $field) {
                        $query->where($field, 'like', "%{$searchValue}%");
                    });

                    continue;
                }
                $section->where($field, $searchOperator, $searchValue);
            }
        }

        // Map frontend field names to database column names
        $orderField = $data['orderField'] ?? null;
        if ($orderField === 'contact') {
            $orderField = 'contact_id';
        }

        if (! empty($orderField) && ! empty($data['orderValue'])) {
            // Handle calculated fields for ordering
            if ($orderField === 'advance_payment') {
                $section = $section->orderByRaw(
                    '(amount_of_advance_payment_on_card + amount_of_advance_payment_via_terminal + amount_of_advance_payment_as_cash) '.$data['orderValue']
                );
            } elseif ($orderField === 'final_payment') {
                $section = $section->orderByRaw(
                    '(amount_of_final_payment_on_card + amount_of_final_payment_via_terminal + amount_of_final_payment_as_cash) '.$data['orderValue']
                );
            } elseif ($orderField === 'remaining_to_pay') {
                $section = $section->orderByRaw(
                    '(total_price - (amount_of_advance_payment_on_card + amount_of_advance_payment_via_terminal + amount_of_advance_payment_as_cash + amount_of_final_payment_on_card + amount_of_final_payment_via_terminal + amount_of_final_payment_as_cash)) '.$data['orderValue']
                );
            } else {
                $section = $section->orderBy($orderField, $data['orderValue']);
            }
        } else {
            $section = $section->latest();
        }

        $paginatedSection = $section->paginate($data['itemsPerPage']);

        return OrderResource::collection($paginatedSection);
    }

    public function create(Request $request): Response
    {
        $rules = $this->getFieldsAndRulesForCreatingOrUpdatingSection();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        // If there are items, warehouse_id is required
        $orderItems = $data['order_items'] ?? [];
        if (! empty($orderItems) && empty($data['warehouse_id'])) {
            return ErrorHandler::responseWith('Склад є обов\'язковим, якщо в замовленні є товари', 422);
        }

        // Stock availability will be checked when transitioning to 'in_progress' status
        // No stock check for 'pending' or 'confirmed' statuses

        // Calculate total price
        $totalPrice = $this->calculateTotalPrice(
            $data['order_items'] ?? [],
            $data['order_services'] ?? [],
            $data['discount'] ?? 0
        );

        // Calculate total advance payment
        $totalAdvancePayment = ($data['amount_of_advance_payment_on_card'] ?? 0)
            + ($data['amount_of_advance_payment_via_terminal'] ?? 0)
            + ($data['amount_of_advance_payment_as_cash'] ?? 0);

        // Calculate total final payment
        $totalFinalPayment = ($data['amount_of_final_payment_on_card'] ?? 0)
            + ($data['amount_of_final_payment_via_terminal'] ?? 0)
            + ($data['amount_of_final_payment_as_cash'] ?? 0);

        // Calculate total payment (advance + final)
        $totalPayment = $totalAdvancePayment + $totalFinalPayment;

        // Automatically set status based on advance payment
        $status = $totalPayment > 0 ? 'confirmed' : 'pending';

        // Automatically set fully_payed_at if total payment covers the total price
        $fullyPayedAt = null;
        if ($totalPayment >= $totalPrice && $totalPrice > 0) {
            $fullyPayedAt = now();
        }

        try {
            DB::beginTransaction();

            $orderData = [
                'status' => $status,
                'amount_of_advance_payment_on_card' => $data['amount_of_advance_payment_on_card'] ?? 0,
                'amount_of_advance_payment_via_terminal' => $data['amount_of_advance_payment_via_terminal'] ?? 0,
                'amount_of_advance_payment_as_cash' => $data['amount_of_advance_payment_as_cash'] ?? 0,
                'amount_of_final_payment_on_card' => $data['amount_of_final_payment_on_card'] ?? 0,
                'amount_of_final_payment_via_terminal' => $data['amount_of_final_payment_via_terminal'] ?? 0,
                'amount_of_final_payment_as_cash' => $data['amount_of_final_payment_as_cash'] ?? 0,
                'currency' => $data['currency'],
                'discount' => $data['discount'] ?? 0,
                'total_price' => $totalPrice,
                'completion_deadline' => $data['completion_deadline'] ?? null,
                'fully_payed_at' => $fullyPayedAt,
                'contact_id' => $data['contact_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'notes' => $data['notes'] ?? null,
            ];

            $order = Order::create($orderData);

            // Записуємо початковий статус в історію
            $this->recordStatusChange($order->id, null, $status, 'Створення замовлення');

            // Create order items
            if (! empty($data['order_items'])) {
                foreach ($data['order_items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $item['item_id'],
                        'price_per_one_unit' => $item['price_per_one_unit'],
                        'currency' => $data['currency'], // Use order currency
                        'quantity' => $item['quantity'],
                    ]);
                }

                // Товари будуть списані при переході в статус 'in_progress'
            }

            // Create order services
            if (! empty($data['order_services'])) {
                foreach ($data['order_services'] as $service) {
                    OrderService::create([
                        'order_id' => $order->id,
                        'service_id' => $service['service_id'],
                        'price_per_one_unit' => $service['price_per_one_unit'],
                        'currency' => $data['currency'], // Use order currency
                        'quantity' => $service['quantity'],
                    ]);
                }
            }

            DB::commit();

            $order->load([
                'contact',
                'warehouse.city.country',
                'orderItems.item.type',
                'orderItems.item.color',
                'orderItems.item.size',
                'orderItems.item.gender',
                'orderItems.item.unit',
                'orderItems.item.images',
                'orderServices.service',
            ]);

            return response()->json(['order' => OrderResource::make($order)]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при створенні замовлення: '.$e->getMessage());
        }
    }

    public function update(Request $request, int $id): Response
    {
        $order = Order::with(['orderItems', 'orderServices'])->find($id);
        if (! $order) {
            return ErrorHandler::responseWith('Замовлення не знайдено', 404);
        }

        // Check if order can be modified
        $modifyCheck = $this->canModifyOrder($order);
        if (! $modifyCheck['can_modify']) {
            return ErrorHandler::responseWith($modifyCheck['message'], 422);
        }

        // Перевірка що замовлення не скасоване
        if ($order->status === 'cancelled') {
            return ErrorHandler::responseWith('Неможливо редагувати скасоване замовлення', 422);
        }

        $rules = $this->getFieldsAndRulesForCreatingOrUpdatingSection();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        // Заборона зміни статусу через update - використовуйте спеціальні методи (confirm, startWork, complete, cancel)
        $currentStatus = $order->status;

        // Custom валідація involvement полів (тільки якщо статус = 'completed')
        if ($currentStatus === 'completed') {
            $involvementValidation = $this->validateInvolvementLevels(
                $data['involvement_level_1_user_id'] ?? null,
                $data['involvement_level_2_user_id'] ?? null,
                $data['involvement_level_3_user_id'] ?? null
            );

            if (! $involvementValidation['valid']) {
                return ErrorHandler::responseWith($involvementValidation['message'], 422);
            }
        }

        // Перевірка зміни складу
        $warehouseChanged = isset($data['warehouse_id']) && $data['warehouse_id'] !== $order->warehouse_id;
        if ($warehouseChanged) {
            // Дозволити зміну складу тільки для pending та confirmed замовлень
            if (! in_array($currentStatus, ['pending', 'confirmed'])) {
                return ErrorHandler::responseWith('Неможливо змінити склад для замовлення зі статусом "'.$currentStatus.'". Зміна складу дозволена тільки для замовлень зі статусом "pending" або "confirmed"', 422);
            }

            // Stock availability буде перевірено при переході в статус 'in_progress'
        }

        // Store old items
        $oldOrderItems = $order->orderItems->map(function ($item) {
            return [
                'item_id' => $item->item_id,
                'quantity' => $item->quantity,
                'price_per_one_unit' => $item->price_per_one_unit,
            ];
        })->toArray();

        // Store old services
        $oldOrderServices = $order->orderServices->map(function ($service) {
            return [
                'service_id' => $service->service_id,
                'quantity' => $service->quantity,
                'price_per_one_unit' => $service->price_per_one_unit,
            ];
        })->toArray();

        // Перевірка зміни товарів або послуг - дозволено тільки для статусів 'pending' та 'confirmed'
        if (!in_array($currentStatus, ['pending', 'confirmed'])) {
            $newOrderItems = $data['order_items'] ?? [];
            $newOrderServices = $data['order_services'] ?? [];

            $itemsChanged = $this->areItemsOrServicesChanged($oldOrderItems, $newOrderItems, 'item_id');
            $servicesChanged = $this->areItemsOrServicesChanged($oldOrderServices, $newOrderServices, 'service_id');

            if ($itemsChanged) {
                return ErrorHandler::responseWith('Неможливо змінити товари для замовлення зі статусом "'.$currentStatus.'". Зміна товарів дозволена тільки для замовлень зі статусом "pending" або "confirmed"', 422);
            }

            if ($servicesChanged) {
                return ErrorHandler::responseWith('Неможливо змінити послуги для замовлення зі статусом "'.$currentStatus.'". Зміна послуг дозволена тільки для замовлень зі статусом "pending" або "confirmed"', 422);
            }
        }

        // Перевірка зміни контакту - дозволено тільки для статусів 'pending' та 'confirmed'
        if (!in_array($currentStatus, ['pending', 'confirmed'])) {
            $oldContactId = $order->contact_id;
            $newContactId = $data['contact_id'] ?? null;

            if ($oldContactId !== $newContactId) {
                return ErrorHandler::responseWith('Неможливо змінити контакт для замовлення зі статусом "'.$currentStatus.'". Зміна контакту дозволена тільки для замовлень зі статусом "pending" або "confirmed"', 422);
            }
        }

        // Stock availability буде перевірено при переході в статус 'in_progress'

        // Calculate total price
        $totalPrice = $this->calculateTotalPrice(
            $data['order_items'] ?? [],
            $data['order_services'] ?? [],
            $data['discount'] ?? 0
        );

        // Calculate total advance payment
        $totalAdvancePayment = ($data['amount_of_advance_payment_on_card'] ?? 0)
            + ($data['amount_of_advance_payment_via_terminal'] ?? 0)
            + ($data['amount_of_advance_payment_as_cash'] ?? 0);

        // Calculate total final payment
        $totalFinalPayment = ($data['amount_of_final_payment_on_card'] ?? 0)
            + ($data['amount_of_final_payment_via_terminal'] ?? 0)
            + ($data['amount_of_final_payment_as_cash'] ?? 0);

        // Calculate total payment (advance + final)
        $totalPayment = $totalAdvancePayment + $totalFinalPayment;

        // Automatically set fully_payed_at if total payment covers the total price
        if ($totalPayment >= $totalPrice && $totalPrice > 0 && ! $order->fully_payed_at && ! isset($data['fully_payed_at'])) {
            $data['fully_payed_at'] = now();
        }
        if ($totalPrice > $totalPayment && $totalPrice > 0 && $order->fully_payed_at && isset($data['fully_payed_at'])) {
            $data['fully_payed_at'] = null;
        }

        $targetStatus = $currentStatus;
        if ($totalPayment > 0 && $currentStatus === 'pending') {
            $targetStatus = 'confirmed';
            $this->recordStatusChange($order->id, $currentStatus, $targetStatus, 'втоматичне підтвердження при внесенні оплати');
        }

        try {
            DB::beginTransaction();

            $orderData = [
                'status' => $targetStatus,
                'amount_of_advance_payment_on_card' => $data['amount_of_advance_payment_on_card'] ?? 0,
                'amount_of_advance_payment_via_terminal' => $data['amount_of_advance_payment_via_terminal'] ?? 0,
                'amount_of_advance_payment_as_cash' => $data['amount_of_advance_payment_as_cash'] ?? 0,
                'amount_of_final_payment_on_card' => $data['amount_of_final_payment_on_card'] ?? 0,
                'amount_of_final_payment_via_terminal' => $data['amount_of_final_payment_via_terminal'] ?? 0,
                'amount_of_final_payment_as_cash' => $data['amount_of_final_payment_as_cash'] ?? 0,
                'currency' => $data['currency'],
                'discount' => $data['discount'] ?? 0,
                'total_price' => $totalPrice,
                'completion_deadline' => $data['completion_deadline'] ?? null,
                'fully_payed_at' => $data['fully_payed_at'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'notes' => $data['notes'] ?? null,
                'involvement_level_1_user_id' => $data['involvement_level_1_user_id'] ?? null,
                'involvement_level_2_user_id' => $data['involvement_level_2_user_id'] ?? null,
                'involvement_level_3_user_id' => $data['involvement_level_3_user_id'] ?? null,
            ];

            $order->update($orderData);

            // Update order items - delete old ones and create new ones
            OrderItem::where('order_id', $order->id)->delete();
            if (! empty($data['order_items'])) {
                foreach ($data['order_items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $item['item_id'],
                        'price_per_one_unit' => $item['price_per_one_unit'],
                        'currency' => $data['currency'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            // Update order services - delete old ones and create new ones
            OrderService::where('order_id', $order->id)->delete();
            if (! empty($data['order_services'])) {
                foreach ($data['order_services'] as $service) {
                    OrderService::create([
                        'order_id' => $order->id,
                        'service_id' => $service['service_id'],
                        'price_per_one_unit' => $service['price_per_one_unit'],
                        'currency' => $data['currency'],
                        'quantity' => $service['quantity'],
                    ]);
                }
            }

            DB::commit();

            $order->load([
                'contact',
                'warehouse.city.country',
                'orderItems.item.type',
                'orderItems.item.color',
                'orderItems.item.size',
                'orderItems.item.gender',
                'orderItems.item.unit',
                'orderItems.item.images',
                'orderServices.service',
                'involvementLevel1User',
                'involvementLevel2User',
                'involvementLevel3User',
            ]);

            return response()->json(['order' => OrderResource::make($order)]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при оновленні замовлення: '.$e->getMessage(), 500);
        }
    }

    public function cancel(Request $request, int $id): Response
    {
        $order = Order::with(['orderItems'])->find($id);
        if (! $order) {
            return ErrorHandler::responseWith('Замовлення не знайдено', 404);
        }

        // Check if order can be modified
        $modifyCheck = $this->canModifyOrder($order);
        if (! $modifyCheck['can_modify']) {
            return ErrorHandler::responseWith($modifyCheck['message'], 422);
        }

        // Перевірка що замовлення ще не відмінено
        if ($order->status === 'cancelled') {
            return ErrorHandler::responseWith('Замовлення вже відмінено', 422);
        }

        // Перевірка можливості зміни статусу на 'cancelled'
        $oldStatus = $order->status;
        $statusCheck = $this->canChangeStatus($oldStatus, 'cancelled');
        if (! $statusCheck['can_change']) {
            return ErrorHandler::responseWith($statusCheck['message'], 422);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'return_items' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        try {
            DB::beginTransaction();

            $inventoryService = new InventoryService();

            if (
                $data['return_items']
                && in_array($oldStatus, ['in_progress', 'completed'])
                && ! empty($order->orderItems)
            ) {
                $orderItems = $order->orderItems->map(function ($item) {
                    return [
                        'item_id' => $item->item_id,
                        'quantity' => $item->quantity,
                        'price_per_one_unit' => $item->price_per_one_unit,
                    ];
                })->toArray();

                $inventoryService->recordIncome($order, $orderItems, $order->currency);
            }

            // Оновлюємо статус замовлення
            $order->update(['status' => 'cancelled']);

            // Записуємо зміну статусу в історію
            $comment = $data['return_items'] ? 'Замовлення відмінено з поверненням товарів' : 'Замовлення відмінено без повернення товарів';
            $this->recordStatusChange($order->id, $oldStatus, 'cancelled', $comment);

            DB::commit();

            $order->load([
                'contact',
                'warehouse.city.country',
                'orderItems.item.type',
                'orderItems.item.color',
                'orderItems.item.size',
                'orderItems.item.gender',
                'orderItems.item.unit',
                'orderItems.item.images',
                'orderServices.service',
            ]);

            return response()->json(['order' => OrderResource::make($order)]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при відміні замовлення: '.$e->getMessage(), 500);
        }
    }

    public function confirm(Request $request, int $id): Response
    {
        $order = Order::with(['orderItems'])->find($id);
        if (! $order) {
            return ErrorHandler::responseWith('Замовлення не знайдено', 404);
        }

        // Check if order can be modified
        $modifyCheck = $this->canModifyOrder($order);
        if (! $modifyCheck['can_modify']) {
            return ErrorHandler::responseWith($modifyCheck['message'], 422);
        }

        // Перевірка можливості зміни статусу на 'confirmed'
        $oldStatus = $order->status;
        $statusCheck = $this->canChangeStatus($oldStatus, 'confirmed');
        if (! $statusCheck['can_change']) {
            return ErrorHandler::responseWith($statusCheck['message'], 422);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'amount_of_advance_payment_on_card' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_advance_payment_via_terminal' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_advance_payment_as_cash' => ['nullable', 'numeric', 'gte:0'],
        ]);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        try {
            DB::beginTransaction();

            // Оновлюємо суми авансу та статус замовлення
            $updateData = ['status' => 'confirmed'];
            if (isset($data['amount_of_advance_payment_on_card'])) {
                $updateData['amount_of_advance_payment_on_card'] = $data['amount_of_advance_payment_on_card'];
            }
            if (isset($data['amount_of_advance_payment_via_terminal'])) {
                $updateData['amount_of_advance_payment_via_terminal'] = $data['amount_of_advance_payment_via_terminal'];
            }
            if (isset($data['amount_of_advance_payment_as_cash'])) {
                $updateData['amount_of_advance_payment_as_cash'] = $data['amount_of_advance_payment_as_cash'];
            }

            $order->update($updateData);

            // Записуємо зміну статусу в історію
            $totalAdvance = ($data['amount_of_advance_payment_on_card'] ?? 0)
                + ($data['amount_of_advance_payment_via_terminal'] ?? 0)
                + ($data['amount_of_advance_payment_as_cash'] ?? 0);
            $comment = $totalAdvance > 0
                ? "Замовлення підтверджено з авансом {$totalAdvance} {$order->currency}"
                : 'Замовлення підтверджено без авансу';
            $this->recordStatusChange($order->id, $oldStatus, 'confirmed', $comment);

            // Товари будуть списані при переході в статус 'in_progress'

            DB::commit();

            $order->load([
                'contact',
                'warehouse.city.country',
                'orderItems.item.type',
                'orderItems.item.color',
                'orderItems.item.size',
                'orderItems.item.gender',
                'orderItems.item.unit',
                'orderItems.item.images',
                'orderServices.service',
            ]);

            return response()->json(['order' => OrderResource::make($order)]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при підтвердженні замовлення: '.$e->getMessage(), 500);
        }
    }

    public function payment(Request $request, int $id): Response
    {
        $order = Order::find($id);
        if (! $order) {
            return ErrorHandler::responseWith('Замовлення не знайдено', 404);
        }

        // Check if order can be modified
        $modifyCheck = $this->canModifyOrder($order);
        if (! $modifyCheck['can_modify']) {
            return ErrorHandler::responseWith($modifyCheck['message'], 422);
        }

        // Перевірка що замовлення не в статусі pending або cancelled
        if (in_array($order->status, ['pending', 'cancelled'])) {
            return ErrorHandler::responseWith('Не можна внести оплату для замовлення зі статусом "'.$order->status.'"', 422);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'amount_of_final_payment_on_card' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_final_payment_via_terminal' => ['nullable', 'numeric', 'gte:0'],
            'amount_of_final_payment_as_cash' => ['nullable', 'numeric', 'gte:0'],
        ]);
        if ($validator->fails()) {
            return ErrorHandler::responseWith(json_encode($validator->errors()));
        }

        try {
            $data = $validator->validated();
        } catch (ValidationException $e) {
            return ErrorHandler::responseWith($e->getMessage());
        }

        try {
            DB::beginTransaction();

            // Додаємо нові платежі до існуючих
            $updateData = [];
            if (isset($data['amount_of_final_payment_on_card'])) {
                $updateData['amount_of_final_payment_on_card'] = $order->amount_of_final_payment_on_card + $data['amount_of_final_payment_on_card'];
            }
            if (isset($data['amount_of_final_payment_via_terminal'])) {
                $updateData['amount_of_final_payment_via_terminal'] = $order->amount_of_final_payment_via_terminal + $data['amount_of_final_payment_via_terminal'];
            }
            if (isset($data['amount_of_final_payment_as_cash'])) {
                $updateData['amount_of_final_payment_as_cash'] = $order->amount_of_final_payment_as_cash + $data['amount_of_final_payment_as_cash'];
            }

            // Розраховуємо загальну суму всіх платежів
            $totalPayments = $order->amount_of_advance_payment_on_card
                + $order->amount_of_advance_payment_via_terminal
                + $order->amount_of_advance_payment_as_cash
                + ($updateData['amount_of_final_payment_on_card'] ?? $order->amount_of_final_payment_on_card)
                + ($updateData['amount_of_final_payment_via_terminal'] ?? $order->amount_of_final_payment_via_terminal)
                + ($updateData['amount_of_final_payment_as_cash'] ?? $order->amount_of_final_payment_as_cash);

            // Якщо оплата повна - встановлюємо дату повної оплати
            if ($totalPayments >= $order->total_price && ! $order->fully_payed_at) {
                $updateData['fully_payed_at'] = now();
            }

            $order->update($updateData);

            DB::commit();

            $order->load([
                'contact',
                'warehouse.city.country',
                'orderItems.item.type',
                'orderItems.item.color',
                'orderItems.item.size',
                'orderItems.item.gender',
                'orderItems.item.unit',
                'orderItems.item.images',
                'orderServices.service',
            ]);

            return response()->json(['order' => OrderResource::make($order)]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при внесенні оплати: '.$e->getMessage(), 500);
        }
    }

    public function startWork(Request $request, int $id): Response
    {
        $order = Order::find($id);
        if (! $order) {
            return ErrorHandler::responseWith('Замовлення не знайдено', 404);
        }

        // Check if order can be modified
        $modifyCheck = $this->canModifyOrder($order);
        if (! $modifyCheck['can_modify']) {
            return ErrorHandler::responseWith($modifyCheck['message'], 422);
        }

        // Перевірка можливості зміни статусу на 'in_progress'
        $oldStatus = $order->status;
        $statusCheck = $this->canChangeStatus($oldStatus, 'in_progress');
        if (! $statusCheck['can_change']) {
            return ErrorHandler::responseWith($statusCheck['message'], 422);
        }

        // Завантажуємо товари замовлення
        $order->load(['orderItems']);

        // Перевіряємо наявність товарів на складі перед початком роботи
        if (! empty($order->orderItems->toArray())) {
            $orderItems = $order->orderItems->map(function ($item) {
                return [
                    'item_id' => $item->item_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray();

            $inventoryService = new InventoryService();
            $stockErrors = $inventoryService->checkStockAvailability($orderItems, $order->warehouse_id);
            if (! empty($stockErrors)) {
                return ErrorHandler::responseWith('Неможливо почати роботу над замовленням. '.implode('; ', $stockErrors), 422);
            }
        }

        try {
            DB::beginTransaction();

            $inventoryService = new InventoryService();

            // Оновлюємо статус замовлення
            $order->update(['status' => 'in_progress']);

            // Записуємо зміну статусу в історію
            $this->recordStatusChange($order->id, $oldStatus, 'in_progress', 'Замовлення прийнято в роботу');

            // Списуємо товари зі складу
            if (! empty($order->orderItems)) {
                $orderItems = $order->orderItems->map(function ($item) {
                    return [
                        'item_id' => $item->item_id,
                        'quantity' => $item->quantity,
                        'price_per_one_unit' => $item->price_per_one_unit,
                    ];
                })->toArray();

                $inventoryService->recordOutcome($order, $orderItems);
            }

            DB::commit();

            $order->load([
                'contact',
                'warehouse.city.country',
                'orderItems.item.type',
                'orderItems.item.color',
                'orderItems.item.size',
                'orderItems.item.gender',
                'orderItems.item.unit',
                'orderItems.item.images',
                'orderServices.service',
            ]);

            return response()->json(['order' => OrderResource::make($order)]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при прийнятті замовлення в роботу: '.$e->getMessage(), 500);
        }
    }

    public function complete(Request $request, int $id): Response
    {
        $order = Order::find($id);
        if (! $order) {
            return ErrorHandler::responseWith('Замовлення не знайдено', 404);
        }

        // Check if order can be modified
        $modifyCheck = $this->canModifyOrder($order);
        if (! $modifyCheck['can_modify']) {
            return ErrorHandler::responseWith($modifyCheck['message'], 422);
        }

        // Перевірка можливості зміни статусу на 'completed'
        $oldStatus = $order->status;
        $statusCheck = $this->canChangeStatus($oldStatus, 'completed');
        if (! $statusCheck['can_change']) {
            return ErrorHandler::responseWith($statusCheck['message'], 422);
        }

        // Валідація involvement полів
        $validator = Validator::make($request->all(), [
            'involvement_level_1_user_id' => 'nullable|integer|exists:users,id',
            'involvement_level_2_user_id' => 'nullable|integer|exists:users,id',
            'involvement_level_3_user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return ErrorHandler::responseWith($validator->errors()->first(), 422);
        }

        // Custom валідація involvement полів
        $involvementValidation = $this->validateInvolvementLevels(
            $request->input('involvement_level_1_user_id'),
            $request->input('involvement_level_2_user_id'),
            $request->input('involvement_level_3_user_id')
        );

        if (! $involvementValidation['valid']) {
            return ErrorHandler::responseWith($involvementValidation['message'], 422);
        }

        try {
            DB::beginTransaction();

            // Оновлюємо статус замовлення, дату завершення та involvement поля
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'involvement_level_1_user_id' => $request->input('involvement_level_1_user_id'),
                'involvement_level_2_user_id' => $request->input('involvement_level_2_user_id'),
                'involvement_level_3_user_id' => $request->input('involvement_level_3_user_id'),
            ]);

            // Записуємо зміну статусу в історію
            $this->recordStatusChange($order->id, $oldStatus, 'completed', 'Замовлення виконано');

            DB::commit();

            $order->load([
                'contact',
                'warehouse.city.country',
                'orderItems.item.type',
                'orderItems.item.color',
                'orderItems.item.size',
                'orderItems.item.gender',
                'orderItems.item.unit',
                'orderItems.item.images',
                'orderServices.service',
                'involvementLevel1User',
                'involvementLevel2User',
                'involvementLevel3User',
            ]);

            return response()->json(['order' => OrderResource::make($order)]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при виконанні замовлення: '.$e->getMessage(), 500);
        }
    }

    public function delete(Request $request, int $id): Response
    {
        return ErrorHandler::responseWith('Неможливо видалити замовлення', 404);

        $order = Order::with(['orderItems'])->find($id);
        if ($order == null) {
            return ErrorHandler::responseWith('Замовлення не знайдено', 404);
        }

        // Check if order can be modified
        $modifyCheck = $this->canModifyOrder($order);
        if (! $modifyCheck['can_modify']) {
            return ErrorHandler::responseWith($modifyCheck['message'], 422);
        }

        try {
            DB::beginTransaction();

            $inventoryService = new InventoryService();

            // Якщо замовлення має статус 'confirmed' - створюємо income для повернення товарів
            if ($order->status === 'confirmed' && ! empty($order->orderItems)) {
                $orderItems = $order->orderItems->map(function ($item) {
                    return [
                        'item_id' => $item->item_id,
                        'quantity' => $item->quantity,
                        'price_per_one_unit' => $item->price_per_one_unit,
                    ];
                })->toArray();

                $inventoryService->recordIncome($order, $orderItems, $order->currency);
            }

            $order->delete();

            DB::commit();

            return response('OK', 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::responseWith('Помилка при видаленні замовлення: '.$e->getMessage(), 500);
        }
    }

    private function validateInvolvementLevels($level1UserId, $level2UserId, $level3UserId): array
    {
        // Перевірка: якщо заповнено level_1, то level_2 і level_3 мають бути null
        if ($level1UserId !== null) {
            if ($level2UserId !== null || $level3UserId !== null) {
                return [
                    'valid' => false,
                    'message' => 'Якщо вказано користувача з повною залученістю (8%), не можна вказувати користувачів з частковою (5%) або дотичною (3%) залученістю',
                ];
            }
        }

        // Перевірка: якщо заповнено level_2 або level_3, то level_1 має бути null
        if (($level2UserId !== null || $level3UserId !== null) && $level1UserId !== null) {
            return [
                'valid' => false,
                'message' => 'Якщо вказано користувачів з частковою (5%) або дотичною (3%) залученістю, не можна вказувати користувача з повною залученістю (8%)',
            ];
        }

        // Перевірка: один і той же користувач не може бути в кількох полях
        $userIds = array_filter([$level1UserId, $level2UserId, $level3UserId]);
        if (count($userIds) !== count(array_unique($userIds))) {
            return [
                'valid' => false,
                'message' => 'Один і той же користувач не може бути вказаний у кількох рівнях залученості',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Порівняння масивів товарів або послуг для виявлення змін
     * Порівнюємо id елементу, кількість та ціну
     */
    private function areItemsOrServicesChanged(array $oldItems, array $newItems, string $idField): bool
    {
        // Сортуємо масиви за id для коректного порівняння
        usort($oldItems, fn ($a, $b) => $a[$idField] <=> $b[$idField]);
        usort($newItems, fn ($a, $b) => $a[$idField] <=> $b[$idField]);

        // Якщо різна кількість елементів - це зміна
        if (count($oldItems) !== count($newItems)) {
            return true;
        }

        // Порівнюємо кожен елемент
        foreach ($oldItems as $index => $oldItem) {
            $newItem = $newItems[$index];

            // Перевіряємо id елементу
            if ($oldItem[$idField] !== $newItem[$idField]) {
                return true;
            }

            // Перевіряємо кількість
            if ($oldItem['quantity'] !== $newItem['quantity']) {
                return true;
            }

            // Перевіряємо ціну (порівнюємо як float з точністю до 2 знаків)
            if (abs((float) $oldItem['price_per_one_unit'] - (float) $newItem['price_per_one_unit']) > 0.001) {
                return true;
            }
        }

        return false;
    }

    private function getWhereOperator(string $operatorName = null): string
    {
        $equality = [
            'include' => 'like',
            'exclude' => 'not like',
            'more' => '>',
            'less' => '<',
            'equal' => '=',
            'notequal' => '<>',
        ];

        return $equality[$operatorName] ?? '=';
    }
}
