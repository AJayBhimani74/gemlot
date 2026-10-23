<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'agreed_price' => (float) $this->agreed_price,
            'stone' => new StoneResource($this->whenLoaded('stone')),
            'buyer' => $this->whenLoaded('buyer', fn () => [
                'id' => $this->buyer->id,
                'company_name' => $this->buyer->company_name,
            ]),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id,
                'company_name' => $this->seller->company_name,
            ]),
            // Lets the app show only the buttons this user can actually press.
            'available_actions' => $this->actionsFor($request->user()),
            'confirmed_at' => $this->confirmed_at,
            'shipped_at' => $this->shipped_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
