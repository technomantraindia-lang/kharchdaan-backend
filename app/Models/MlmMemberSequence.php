<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MlmMemberSequence extends Model
{
    protected $table = 'mlm_member_sequences';

    protected $fillable = [
        'period',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
        ];
    }
}
