<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceSyncLog extends Model
{
    protected $table = 'woocommerce_sync_logs';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'woocommerce_id',
        'direction',
        'action',
        'status',
        'request_payload',
        'response_payload',
        'error_message',
        'attempts',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'attempts' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function log(
        string $entityType,
        ?int $entityId,
        ?int $woocommerceId,
        string $direction,
        string $action,
        string $status,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $errorMessage = null
    ): self {
        // Sanitize payloads to ensure Consumer Key/Secret or tokens are never logged
        $sanitizedRequest = static::sanitizePayload($requestPayload);
        $sanitizedResponse = static::sanitizePayload($responsePayload);

        return static::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'woocommerce_id' => $woocommerceId,
            'direction' => $direction,
            'action' => $action,
            'status' => $status,
            'request_payload' => $sanitizedRequest,
            'response_payload' => $sanitizedResponse,
            'error_message' => $errorMessage,
            'started_at' => now(),
            'completed_at' => in_array($status, ['success', 'failed']) ? now() : null,
        ]);
    }

    protected static function sanitizePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $sensitiveKeys = ['consumer_key', 'consumer_secret', 'password', 'secret', 'authorization', 'api_key'];

        array_walk_recursive($payload, function (&$value, $key) use ($sensitiveKeys) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $value = '******** [REDACTED]';
            }
        });

        return $payload;
    }
}
