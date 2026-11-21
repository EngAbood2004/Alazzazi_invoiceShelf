<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => ['required_without:new_category', 'nullable', 'integer', 'exists:expense_categories,id'],
            'new_category' => ['nullable', 'string', 'max:120'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
