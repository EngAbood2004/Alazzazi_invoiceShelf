<?php

namespace App\Services\Invoicing;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Model;

/** Next sequential number such as INV-000042, per prefix. */
class DocumentNumber
{
    /**
     * @param  class-string<Model>  $model
     */
    public static function next(string $model, string $settingKey): string
    {
        $prefix = (string) BusinessSetting::get($settingKey);

        $last = $model::query()
            ->where('number', 'like', $prefix.'%')
            ->pluck('number')
            ->map(fn (string $number) => (int) preg_replace('/\D/', '', substr($number, strlen($prefix))))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }
}
