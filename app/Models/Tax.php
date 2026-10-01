<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = ['name', 'percentage', 'status', 'desc'];

    protected $casts = ['percentage' => 'decimal:2'];
}
