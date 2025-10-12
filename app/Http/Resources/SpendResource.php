<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpendResource extends JsonResource
{
    public static bool $isUserAllowedToSeeHidden = false;
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
            "id" => $this->id,
            "title" => $this->title,
            "unconverted_price" => (float)$this->price,
            "converted_price_to_uah" => match ($this->currency) {
                "USD" => $this->price * self::$dollarCurrencyExchangeCoefficient,
                "EUR" => $this->price * self::$euroCurrencyExchangeCoefficient,
                default => null
            },
            "currency" => $this->currency,
            "created_by_user" => $this->whenLoaded('user', $this->user),
            "is_hidden" => $this->when(self::$isUserAllowedToSeeHidden, $this->is_hidden),
            "happened_at" => $this->happened_at->format('Y-m-d H:i'),
            "created_at" => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
