<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'price'           => ['required', 'numeric', 'min:0', 'max:9999999'],
            'stock'           => ['required', 'integer', 'min:0', 'max:1000000'],
            'expiration_date' => ['required', 'date'],
            'category'        => ['required', 'in:otc,rx,both'],
            'photo'           => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'description'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'stock' => 'stock quantity',
            'expiration_date' => 'expiration date',
        ];
    }
}
