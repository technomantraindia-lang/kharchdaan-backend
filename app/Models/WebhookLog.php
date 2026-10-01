<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $fillable = [
        'provider',
        'delivery_id',
        'topic',
        'resource',
        'event',
        'signature_valid',
        'payload',
        'status',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public static function isDuplicate(string $provider, ?string $deliveryId): bool
    {
        if (empty($deliveryId)) {
            return false;
        }

        return static::where('provider', $provider)
            ->where('delivery_id', $deliveryId)
            ->whereIn('status', ['processed', 'processing', 'duplicate', 'received'])
            ->exists();
    }

    public static function sanitizePayload(?array $payload): ?array
    {
        if (empty($payload)) {
            return $payload;
        }

        $sanitized = $payload;
        $sensitiveKeys = ['password', 'secret', 'token', 'credit_card', 'card_number', 'cvv', 'auth_code', 'api_key'];

        array_walk_recursive($sanitized, function (&$value, $key) use ($sensitiveKeys) {
            if (is_string($key)) {
                $lowerKey = strtolower($key);
                foreach ($sensitiveKeys as $sensitive) {
                    if (str_contains($lowerKey, $sensitive)) {
                        $value = '[REDACTED]';
                        break;
                    }
                }
            }
        });

        return $sanitized;
    }
}
