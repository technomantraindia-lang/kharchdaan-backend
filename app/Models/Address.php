<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = ['user_id', 'type', 'fname', 'lname', 'address', 'apt', 'city', 'state', 'zip', 'country', 'phone', 'default'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddress(): string
    {
        return "{$this->address}".($this->apt ? ", {$this->apt}" : '').", {$this->city}, {$this->state} {$this->zip}, {$this->country}";
    }
}
