<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessAccountTransactionResource extends JsonResource
{
    public static float $dollarCurrencyExchangeCoefficient = 1;
    public static float $euroCurrencyExchangeCoefficient = 1;

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
            'title' => $this->title,
            'total_price' => (float) $this->total_price,
            'converted_price_to_uah' => match ($this->currency) {
                'USD' => $this->total_price * self::$dollarCurrencyExchangeCoefficient,
                'EUR' => $this->total_price * self::$euroCurrencyExchangeCoefficient,
                default => null
            },
            'amount_on_card' => (float) $this->amount_on_card,
            'amount_via_terminal' => (float) $this->amount_via_terminal,
            'amount_as_cash' => (float) $this->amount_as_cash,
            'currency' => $this->currency,
            'type' => $this->type,
            'created_by_user' => $this->whenLoaded('user', $this->user),
            'happened_at' => $this->happened_at->format('Y-m-d H:i'),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
