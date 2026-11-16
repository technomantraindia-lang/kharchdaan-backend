<?php

namespace App\Services\Delivery;

class DeliveryAreaService
{
    public function allows(?string $city = null, ?string $pincode = null, ?string $addressBlob = null): bool
    {
        $pincode = $this->extractPincode($pincode, $addressBlob);
        $city = strtolower(trim((string) $city));

        $allowedCities = array_map('strtolower', config('delivery.allowed_cities', []));
        $allowedPincodes = config('delivery.allowed_pincodes', []);

        if ($pincode && in_array($pincode, $allowedPincodes, true)) {
            return true;
        }

        if ($city !== '' && in_array($city, $allowedCities, true)) {
            return true;
        }

        if ($addressBlob) {
            $haystack = strtolower($addressBlob);
            foreach ($allowedCities as $allowedCity) {
                if ($allowedCity !== '' && str_contains($haystack, $allowedCity)) {
                    return true;
                }
            }
            foreach ($allowedPincodes as $code) {
                if (str_contains($haystack, $code)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function assertAllowed(?string $city = null, ?string $pincode = null, ?string $addressBlob = null): void
    {
        if (! $this->allows($city, $pincode, $addressBlob)) {
            abort(422, config('delivery.block_message'));
        }
    }

    public function extractPincode(?string $pincode, ?string $addressBlob = null): ?string
    {
        if ($pincode && preg_match('/\b(\d{6})\b/', $pincode, $m)) {
            return $m[1];
        }

        if ($addressBlob && preg_match('/\b(\d{6})\b/', $addressBlob, $m)) {
            return $m[1];
        }

        return null;
    }

    public function blockMessage(): string
    {
        return (string) config('delivery.block_message');
    }
}
