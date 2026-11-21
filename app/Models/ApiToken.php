<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token', 'last_used_at', 'expires_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Issues a new token and returns the plain-text value (shown only once).
     */
    public static function issue(User $user, string $name, int $days = 90): string
    {
        $plain = $user->id.'|'.Str::random(48);

        static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
            'expires_at' => now()->addDays($days),
        ]);

        return $plain;
    }

    public static function findByPlain(string $plain): ?self
    {
        return static::query()->where('token', hash('sha256', $plain))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }
}
