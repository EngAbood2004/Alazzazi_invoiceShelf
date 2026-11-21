<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends Model
{
    public const FREQUENCIES = ['weekly', 'monthly', 'quarterly', 'yearly'];

    protected $fillable = [
        'customer_id', 'frequency', 'next_run_date', 'ends_on', 'max_runs', 'runs', 'due_in_days',
        'auto_send', 'is_active', 'currency', 'items', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'next_run_date' => 'date', 'ends_on' => 'date', 'auto_send' => 'boolean',
            'is_active' => 'boolean', 'items' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function nextDateAfter(CarbonInterface $date): CarbonInterface
    {
        return match ($this->frequency) {
            'weekly' => $date->copy()->addWeek(),
            'quarterly' => $date->copy()->addMonthsNoOverflow(3),
            'yearly' => $date->copy()->addYearNoOverflow(),
            default => $date->copy()->addMonthNoOverflow(),
        };
    }

    public function isFinished(): bool
    {
        return ($this->max_runs !== null && $this->runs >= $this->max_runs)
            || ($this->ends_on !== null && $this->next_run_date->gt($this->ends_on));
    }
}
