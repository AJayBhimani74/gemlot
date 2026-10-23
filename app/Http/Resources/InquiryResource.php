<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Inquiry */
class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'message' => $this->message,
            'offered_price' => $this->offered_price === null ? null : (float) $this->offered_price,
            'responded_at' => $this->responded_at,
            'created_at' => $this->created_at,
            'stone' => new StoneResource($this->whenLoaded('stone')),
            'buyer' => $this->whenLoaded('buyer', fn () => [
                'id' => $this->buyer->id,
                'company_name' => $this->buyer->company_name,
            ]),
            'order_id' => $this->whenLoaded('order', fn () => $this->order?->id),
        ];
    }
}
