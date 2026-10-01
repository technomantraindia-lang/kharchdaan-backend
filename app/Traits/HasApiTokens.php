<?php

namespace App\Traits;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApiTokens
{
    protected ?PersonalAccessToken $currentAccessToken = null;

    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    public function createToken(string $name, array $abilities = ['*']): object
    {
        $result = PersonalAccessToken::generateToken($this, $name, $abilities);

        return new class($result['accessToken'], $result['plainTextToken']) {
            public PersonalAccessToken $accessToken;
            public string $plainTextToken;

            public function __construct(PersonalAccessToken $accessToken, string $plainTextToken)
            {
                $this->accessToken = $accessToken;
                $this->plainTextToken = $plainTextToken;
            }
        };
    }

    public function withAccessToken(?PersonalAccessToken $accessToken): static
    {
        $this->currentAccessToken = $accessToken;

        return $this;
    }

    public function currentAccessToken(): ?PersonalAccessToken
    {
        return $this->currentAccessToken;
    }
}
