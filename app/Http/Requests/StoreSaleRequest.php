<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'sale_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => __('The selected product is unavailable.'),
            'warehouse_id.exists' => __('The selected warehouse is unavailable.'),
            'quantity.min' => __('Quantity must be at least one.'),
            'unit_price.decimal' => __('The sale price may have up to two decimal places.'),
            'sale_date.before_or_equal' => __('The sale date cannot be in the future.'),
        ];
    }
}
