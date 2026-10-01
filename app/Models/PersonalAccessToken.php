<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PersonalAccessToken extends Model
{
    protected $table = 'personal_access_tokens';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'json',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function generateToken(Model $tokenable, string $name, array $abilities = ['*']): array
    {
        $plainText = Str::random(40);
        $hashedToken = hash('sha256', $plainText);

        $expiration = config('auth.api_token_expiration', 43200);
        $expiresAt = $expiration ? now()->addMinutes($expiration) : null;

        $accessToken = $tokenable->tokens()->create([
            'name' => $name,
            'token' => $hashedToken,
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [
            'accessToken' => $accessToken,
            'plainTextToken' => $accessToken->id . '|' . $plainText,
        ];
    }

    public static function findToken(string $token): ?static
    {
        $tokenModel = null;
        if (! str_contains($token, '|')) {
            $hashed = hash('sha256', $token);
            $tokenModel = static::where('token', $hashed)->first();
        } else {
            [$id, $plainText] = explode('|', $token, 2);
            $hashed = hash('sha256', $plainText);
            $tokenModel = static::where('id', $id)->where('token', $hashed)->first();
        }

        if (! $tokenModel) {
            return null;
        }

        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            return null;
        }

        return $tokenModel;
    }
}
