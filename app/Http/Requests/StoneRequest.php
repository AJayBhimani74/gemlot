<?php

namespace App\Http\Requests;

use App\Models\Stone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSeller() ?? false;
    }

    public function rules(): array
    {
        return [
            'shape' => ['required', Rule::in(Stone::SHAPES)],
            'carat' => ['required', 'numeric', 'min:0.05', 'max:100'],
            'color' => ['required', Rule::in(Stone::COLORS)],
            'clarity' => ['required', Rule::in(Stone::CLARITIES)],
            'cut' => ['nullable', Rule::in(Stone::CUTS)],
            'lab' => ['nullable', Rule::in(Stone::LABS)],
            'certificate_no' => ['nullable', 'string', 'max:40'],
            'price_per_carat' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'status' => ['sometimes', Rule::in([Stone::STATUS_AVAILABLE, Stone::STATUS_ON_HOLD])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }
}
