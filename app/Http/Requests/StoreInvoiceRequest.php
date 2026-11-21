<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Shared by invoices and estimates (create and update). */
class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['description'] ?? '')) !== '')
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isEstimate = $this->routeIs('estimates.*');

        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => [$isEstimate ? 'prohibited' : 'nullable', 'date', 'after_or_equal:issue_date'],
            'expiry_date' => [$isEstimate ? 'nullable' : 'prohibited', 'date', 'after_or_equal:issue_date'],
            'warehouse_id' => [$isEstimate ? 'prohibited' : 'nullable', 'integer', 'exists:warehouses,id'],
            'currency' => ['required', 'string', 'size:3'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => ['nullable', 'numeric', 'min:0', $this->input('discount_type') === 'percent' ? 'max:100' : 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['items.required' => __('Add at least one line item.')];
    }
}
