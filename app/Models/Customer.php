<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'company_name', 'email', 'phone', 'whatsapp', 'tax_number',
        'address', 'city', 'country', 'currency', 'notes', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    /** Number used for WhatsApp: the dedicated field, else the phone. */
    public function whatsappNumber(): ?string
    {
        $number = $this->whatsapp ?: $this->phone;

        return $number ? preg_replace('/[^\d+]/', '', $number) : null;
    }

    public function displayName(): string
    {
        return $this->company_name ? "{$this->name} ({$this->company_name})" : $this->name;
    }
}
