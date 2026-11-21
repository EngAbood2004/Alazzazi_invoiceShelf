<?php

namespace App\Services\Invoicing;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EstimateService
{
    public function __construct(private readonly DocumentCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?Estimate $estimate = null, ?User $user = null): Estimate
    {
        return DB::transaction(function () use ($data, $estimate, $user) {
            $estimate ??= new Estimate([
                'number' => DocumentNumber::next(Estimate::class, 'estimate_prefix'),
                'status' => 'draft',
                'created_by' => $user?->id,
            ]);

            $estimate->fill(array_intersect_key($data, array_flip([
                'customer_id', 'issue_date', 'expiry_date', 'currency', 'discount_type', 'discount_value', 'notes', 'terms',
            ])));

            $result = $this->calculator->calculate($data['items'] ?? [], $data['discount_type'] ?? 'fixed', (float) ($data['discount_value'] ?? 0));
            $estimate->fill([
                'subtotal' => $result['subtotal'],
                'discount_total' => $result['discount_total'],
                'tax_total' => $result['tax_total'],
                'total' => $result['total'],
            ])->save();

            $estimate->items()->delete();
            $estimate->items()->createMany($result['items']);

            return $estimate->load('items');
        });
    }
}
