<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['title', 'image', 'link', 'button_text', 'sort_order', 'status'];
}
