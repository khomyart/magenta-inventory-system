<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount_of_advance_payment_on_card' => (float) $this->amount_of_advance_payment_on_card,
            'amount_of_advance_payment_via_terminal' => (float) $this->amount_of_advance_payment_via_terminal,
            'amount_of_advance_payment_as_cash' => (float) $this->amount_of_advance_payment_as_cash,
            'amount_of_final_payment_on_card' => (float) $this->amount_of_final_payment_on_card,
            'amount_of_final_payment_via_terminal' => (float) $this->amount_of_final_payment_via_terminal,
            'amount_of_final_payment_as_cash' => (float) $this->amount_of_final_payment_as_cash,
            'currency' => $this->currency,
            'discount' => (float) $this->discount,
            'total_price' => (float) $this->total_price,
            'remaining_to_pay' => (float) (
                $this->total_price -
                ($this->amount_of_advance_payment_on_card +
                 $this->amount_of_advance_payment_via_terminal +
                 $this->amount_of_advance_payment_as_cash +
                 $this->amount_of_final_payment_on_card +
                 $this->amount_of_final_payment_via_terminal +
                 $this->amount_of_final_payment_as_cash)
            ),
            'completion_deadline' => $this->completion_deadline?->format('Y-m-d H:i:s'),
            'fully_payed_at' => $this->fully_payed_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'contact_id' => $this->contact_id,
            'warehouse_id' => $this->warehouse_id,
            'contact' => $this->contact,
            'warehouse' => $this->warehouse,
            'involvement_level_1_user_id' => $this->involvement_level_1_user_id,
            'involvement_level_2_user_id' => $this->involvement_level_2_user_id,
            'involvement_level_3_user_id' => $this->involvement_level_3_user_id,
            'involvement_level_1_user' => $this->involvementLevel1User ? [
                'id' => $this->involvementLevel1User->id,
                'name' => $this->involvementLevel1User->name,
            ] : null,
            'involvement_level_2_user' => $this->involvementLevel2User ? [
                'id' => $this->involvementLevel2User->id,
                'name' => $this->involvementLevel2User->name,
            ] : null,
            'involvement_level_3_user' => $this->involvementLevel3User ? [
                'id' => $this->involvementLevel3User->id,
                'name' => $this->involvementLevel3User->name,
            ] : null,
            'order_items' => $this->orderItems ? $this->orderItems->map(function ($orderItem) {
                return [
                    'id' => $orderItem->id,
                    'item_id' => $orderItem->item_id,
                    'item' => $orderItem->item,
                    'price_per_one_unit' => (float) $orderItem->price_per_one_unit,
                    'currency' => $orderItem->currency,
                    'quantity' => $orderItem->quantity,
                ];
            }) : [],
            'order_services' => $this->orderServices ? $this->orderServices->map(function ($orderService) {
                return [
                    'id' => $orderService->id,
                    'service_id' => $orderService->service_id,
                    'service' => $orderService->service,
                    'price_per_one_unit' => (float) $orderService->price_per_one_unit,
                    'currency' => $orderService->currency,
                    'quantity' => $orderService->quantity,
                ];
            }) : [],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
