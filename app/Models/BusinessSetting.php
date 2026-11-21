<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value store for company-wide settings (company profile, invoice
 * numbering, default due days, reminder schedule...).
 */
class BusinessSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /** @var array<string, string|int> */
    public const DEFAULTS = [
        'company_name' => 'Alazzazi InvoiceShelf',
        'company_email' => '',
        'company_phone' => '',
        'company_address' => '',
        'company_tax_number' => '',
        'currency' => 'USD',
        'invoice_prefix' => 'INV-',
        'estimate_prefix' => 'EST-',
        'invoice_due_days' => 15,
        'estimate_valid_days' => 30,
        'invoice_terms' => '',
        'reminder_days' => '1,7,14,30',
        'reminder_channels' => 'email,whatsapp',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('business_settings', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('business_settings');
    }
}
