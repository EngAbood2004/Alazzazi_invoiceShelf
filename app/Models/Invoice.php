<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_VIEWED = 'viewed';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_VIEWED, self::STATUS_PARTIALLY_PAID, self::STATUS_PAID, self::STATUS_CANCELLED];

    protected $fillable = [
        'number', 'customer_id', 'estimate_id', 'recurring_invoice_id', 'warehouse_id', 'status',
        'issue_date', 'due_date', 'currency', 'discount_type', 'discount_value', 'subtotal',
        'discount_total', 'tax_total', 'total', 'amount_paid', 'amount_due', 'notes', 'terms',
        'public_token', 'stock_deducted', 'sent_at', 'viewed_at', 'paid_at', 'last_reminder_at',
        'reminders_sent', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date', 'due_date' => 'date', 'discount_value' => 'decimal:2',
            'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2',
            'total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'amount_due' => 'decimal:2',
            'stock_deducted' => 'boolean', 'sent_at' => 'datetime', 'viewed_at' => 'datetime',
            'paid_at' => 'datetime', 'last_reminder_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            $invoice->public_token ??= Str::random(48);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->latest('paid_on');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class)->latest();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Invoices that still expect money (sent, not cancelled, balance left). */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_DRAFT, self::STATUS_CANCELLED])->where('amount_due', '>', 0);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->open()->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());
    }

    /** Invoices that count as revenue (everything except drafts and cancelled). */
    public function scopeIssued(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_DRAFT, self::STATUS_CANCELLED]);
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CANCELLED, self::STATUS_PAID], true)
            && (float) $this->amount_due > 0
            && $this->due_date !== null
            && $this->due_date->lt(today());
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(today()) : 0;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_VIEWED], true)
            && (float) $this->amount_paid === 0.0;
    }

    public function publicUrl(): string
    {
        return route('public.invoices.show', $this->public_token);
    }

    /** Display status, with "overdue" derived from the due date. */
    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }
}
