<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariationAttributeValue extends Model
{
    protected $table = 'variation_attribute_values';

    protected $fillable = [
        'product_variation_id',
        'attribute_id',
        'attribute_value_id',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }
}
