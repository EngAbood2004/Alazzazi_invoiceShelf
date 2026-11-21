<?php

namespace App\Support;

final class Money
{
    public static function format(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency ??= (string) \App\Models\BusinessSetting::get('currency');
        $symbols = ['USD' => '$', 'ILS' => '₪', 'JOD' => 'JD ', 'EUR' => '€', 'EGP' => 'E£ ', 'SAR' => 'SAR '];

        $formatted = number_format((float) $amount, 2);

        return isset($symbols[$currency]) ? $symbols[$currency].$formatted : $formatted.' '.$currency;
    }
}
