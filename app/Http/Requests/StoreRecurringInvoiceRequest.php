<?php

namespace App\Http\Requests;

use App\Models\RecurringInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['items' => collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['description'] ?? '')) !== '')
            ->values()->all()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'frequency' => ['required', Rule::in(RecurringInvoice::FREQUENCIES)],
            'next_run_date' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after:next_run_date'],
            'max_runs' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'due_in_days' => ['required', 'integer', 'min:0', 'max:365'],
            'auto_send' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
