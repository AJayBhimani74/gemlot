<?php

namespace App\Http\Resources;

use App\Models\Stone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Stone */
class StoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'shape' => $this->shape,
            'carat' => (float) $this->carat,
            'color' => $this->color,
            'clarity' => $this->clarity,
            'cut' => $this->cut,
            'lab' => $this->lab,
            'certificate_no' => $this->certificate_no,
            'price_per_carat' => (float) $this->price_per_carat,
            'total_price' => (float) $this->total_price,
            'status' => $this->status,
            'image_url' => $this->image_url,
            'notes' => $this->notes,
            'seller' => $this->whenLoaded('seller', fn () => [
                'company_name' => $this->seller->company_name,
                'country' => $this->seller->country,
            ]),
        ];
    }
}
