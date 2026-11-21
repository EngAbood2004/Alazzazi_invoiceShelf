<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordInvoicePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
