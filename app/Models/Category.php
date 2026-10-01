<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id', 'image', 'description', 'seo_title', 'seo_description', 'status'];

    protected $casts = ['status' => 'string'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function mainProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function subProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_category_id');
    }

    public function subSubProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_sub_category_id');
    }

    public function getParentPathAttribute(): string
    {
        $path = [];
        $current = $this;
        while ($current) {
            $path[] = $current->name;
            $current = $current->parent;
        }
        return implode(' → ', array_reverse($path));
    }

    public function getDescendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        return $ids;
    }
}
