<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer', 'exists:products,id'],
            'warehouse_id'     => ['required', 'integer', 'exists:warehouses,id'],
            'type'             => ['required', 'string', 'in:in,out,adjustment'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'movement_date'    => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
<<<<<<< HEAD
            'type.in'           => __('Movement type must be stock in, stock out, or adjustment.'),
            'quantity.min'      => __('Quantity must be at least one.'),
            'product_id.exists' => __('The selected product is unavailable.'),
            'warehouse_id.exists' => __('The selected warehouse is unavailable.'),
=======
            'type.in'           => 'Tipe pergerakan harus salah satu dari: masuk (in), keluar (out), atau penyesuaian (adjustment).',
            'quantity.min'      => 'Jumlah harus minimal 1.',
            'product_id.exists' => 'Produk tidak ditemukan.',
            'warehouse_id.exists' => 'Gudang tidak ditemukan.',
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        ];
    }
}
