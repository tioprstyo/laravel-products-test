<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000000', 'decimal:0,2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_name' => 'product name',
            'quantity' => 'quantity in stock',
            'price' => 'price per item',
        ];
    }
}
