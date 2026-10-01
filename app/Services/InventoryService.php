<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function increaseStock(
        Product|ProductVariation $item,
        int $qty,
        string $reason = 'purchase',
        ?Model $reference = null,
        ?User $byUser = null,
        ?array $metadata = null
    ): InventoryTransaction {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $qty, $reason, $reference, $byUser, $metadata) {
            $model = $this->lockItem($item);
            $previousStock = (int) $model->stock_qty;
            $newStock = $previousStock + $qty;

            $model->stock_qty = $newStock;
            $model->save();

            return $this->recordTransaction($model, 'purchase', $qty, $previousStock, $newStock, $reason, $reference, $byUser, $metadata);
        });
    }

    public function decreaseStock(
        Product|ProductVariation $item,
        int $qty,
        string $reason = 'sale',
        ?Model $reference = null,
        ?User $byUser = null,
        ?array $metadata = null
    ): InventoryTransaction {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $qty, $reason, $reference, $byUser, $metadata) {
            $model = $this->lockItem($item);
            $previousStock = (int) $model->stock_qty;
            $newStock = $previousStock - $qty;

            if ($newStock < 0) {
                throw new InvalidArgumentException("Insufficient stock for product #{$model->id}. Available physical stock is {$previousStock}, requested reduction is {$qty}.");
            }

            $model->stock_qty = $newStock;
            $model->save();

            return $this->recordTransaction($model, 'sale', -$qty, $previousStock, $newStock, $reason, $reference, $byUser, $metadata);
        });
    }

    public function reserveStock(
        Product|ProductVariation $item,
        int $qty,
        string $reason = 'reservation',
        ?Model $reference = null,
        ?User $byUser = null,
        ?array $metadata = null
    ): InventoryTransaction {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $qty, $reason, $reference, $byUser, $metadata) {
            if (! ($item instanceof Product)) {
                throw new InvalidArgumentException('Reserved stock applies to Product model.');
            }

            $product = Product::where('id', $item->id)->lockForUpdate()->firstOrFail();
            $previousReserved = (int) ($product->reserved_stock ?? 0);
            $newReserved = $previousReserved + $qty;

            if ($newReserved > (int) $product->stock_qty) {
                throw new InvalidArgumentException("Reserved stock ({$newReserved}) cannot exceed physical stock ({$product->stock_qty}).");
            }

            $product->reserved_stock = $newReserved;
            $product->save();

            return $this->recordTransaction($product, 'reservation', $qty, $product->stock_qty, $product->stock_qty, $reason, $reference, $byUser, $metadata);
        });
    }

    public function releaseReservedStock(
        Product|ProductVariation $item,
        int $qty,
        string $reason = 'reservation_release',
        ?Model $reference = null,
        ?User $byUser = null,
        ?array $metadata = null
    ): InventoryTransaction {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $qty, $reason, $reference, $byUser, $metadata) {
            if (! ($item instanceof Product)) {
                throw new InvalidArgumentException('Reserved stock applies to Product model.');
            }

            $product = Product::where('id', $item->id)->lockForUpdate()->firstOrFail();
            $previousReserved = (int) ($product->reserved_stock ?? 0);
            $newReserved = max($previousReserved - $qty, 0);

            $product->reserved_stock = $newReserved;
            $product->save();

            return $this->recordTransaction($product, 'reservation_release', -$qty, $product->stock_qty, $product->stock_qty, $reason, $reference, $byUser, $metadata);
        });
    }

    public function adjustStock(
        Product|ProductVariation $item,
        int $newQty,
        string $reason = 'adjustment',
        ?Model $reference = null,
        ?User $byUser = null,
        ?array $metadata = null
    ): InventoryTransaction {
        if ($newQty < 0) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }

        return DB::transaction(function () use ($item, $newQty, $reason, $reference, $byUser, $metadata) {
            $model = $this->lockItem($item);
            $previousStock = (int) $model->stock_qty;
            $changeQty = $newQty - $previousStock;

            $model->stock_qty = $newQty;
            $model->save();

            return $this->recordTransaction($model, 'adjustment', $changeQty, $previousStock, $newQty, $reason, $reference, $byUser, $metadata);
        });
    }

    public function returnStock(
        Product|ProductVariation $item,
        int $qty,
        string $reason = 'return',
        ?Model $reference = null,
        ?User $byUser = null,
        ?array $metadata = null
    ): InventoryTransaction {
        return $this->increaseStock($item, $qty, $reason, $reference, $byUser, $metadata);
    }

    private function lockItem(Product|ProductVariation $item): Product|ProductVariation
    {
        $class = get_class($item);
        return $class::where('id', $item->id)->lockForUpdate()->firstOrFail();
    }

    private function recordTransaction(
        Product|ProductVariation $item,
        string $type,
        int $quantity,
        int $previousStock,
        int $newStock,
        ?string $reason,
        ?Model $reference,
        ?User $byUser,
        ?array $metadata
    ): InventoryTransaction {
        $userId = $byUser?->id ?? Auth::id();
        $productId = $item instanceof Product ? $item->id : $item->product_id;
        $variationId = $item instanceof ProductVariation ? $item->id : null;

        $transaction = InventoryTransaction::create([
            'product_id' => $productId,
            'variation_id' => $variationId,
            'type' => $type,
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->getKey(),
            'reason' => $reason,
            'created_by' => $userId,
            'metadata' => $metadata,
        ]);

        // Sync with legacy inventory_logs table for backward compatibility
        try {
            InventoryLog::create([
                'product_id' => $productId,
                'old_qty' => $previousStock,
                'change_qty' => $quantity,
                'new_qty' => $newStock,
                'type' => $quantity >= 0 ? 'add' : 'reduce',
                'note' => $reason,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        ActivityLogService::log(
            'inventory_adjust',
            'inventory',
            "Adjusted stock for Product #{$productId} ({$previousStock} -> {$newStock}, Change: {$quantity}, Reason: {$reason})",
            $item,
            ['stock_qty' => $previousStock],
            ['stock_qty' => $newStock]
        );

        // Dispatch WooCommerce Inventory Sync after local transaction
        try {
            app(\App\Services\WooCommerce\InventorySyncService::class)->dispatchInventorySync($item);
        } catch (\Throwable $e) {
            report($e);
        }

        return $transaction;
    }
}
