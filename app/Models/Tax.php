<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = ['name', 'rate', 'is_active'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:3', 'is_active' => 'boolean'];
    }

    public function label(): string
    {
        return $this->name.' ('.rtrim(rtrim((string) $this->rate, '0'), '.').'%)';
    }
}
