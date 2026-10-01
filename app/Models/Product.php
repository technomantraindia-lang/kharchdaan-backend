<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'sku', 'hsn_code', 'category_id', 'sub_category_id', 'sub_sub_category_id', 'brand_id',
        'price', 'sale_price', 'cost_price', 'gst_percentage', 'stock_qty', 'reserved_stock', 'low_stock_qty',
        'unit', 'min_order_qty', 'weight', 'image', 'short_desc', 'description',
        'seo_title', 'seo_desc', 'seo_keywords', 'status', 'featured',
        'woocommerce_id', 'woocommerce_sync_status', 'woocommerce_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'featured' => 'boolean',
        'woocommerce_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'cost_price',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function subSubCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_sub_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class)->latest();
    }

    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return asset('images/default-product.svg');
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return route('media.file', ['path' => ltrim($this->image, '/')]);
    }

    public function getAvailableStockAttribute(): int
    {
        return max((int) $this->stock_qty - (int) ($this->reserved_stock ?? 0), 0);
    }

    public function isInStock(): bool
    {
        return $this->stock_qty > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->low_stock_qty;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
