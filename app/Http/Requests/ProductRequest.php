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
            'unit'            => ['required', 'string', 'max:30'],
            'subtitle'        => ['nullable', 'string', 'max:120'],
            'photo'           => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp,gif,bmp,avif', 'max:10240'],
            'description'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.uploaded' => 'The photo failed to upload. Please try a smaller PNG or JPG image.',
            'photo.image'    => 'The photo must be an image (PNG, JPG, JPEG, WEBP, GIF or BMP).',
            'photo.mimes'    => 'The photo must be a PNG, JPG, JPEG, WEBP, GIF or BMP file.',
            'photo.max'      => 'The photo may not be larger than 10MB.',
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
