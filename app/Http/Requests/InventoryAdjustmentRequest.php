<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasPermission('inventory.adjust');
    }

    public function rules(): array
    {
        return [
            'stock_qty' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
        ];
    }
}
