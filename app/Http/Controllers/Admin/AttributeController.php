<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = ProductAttribute::withCount('values')->latest()->paginate(15);
        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:product_attributes,name',
            'status' => 'required|in:active,inactive',
            'values' => 'nullable|array',
            'values.*' => 'nullable|string|max:100',
        ]);

        $attribute = ProductAttribute::create([
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        $this->syncValues($attribute, $data['values'] ?? []);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created successfully.');
    }

    public function edit(ProductAttribute $attribute)
    {
        $attribute->load('values');
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, ProductAttribute $attribute)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:product_attributes,name,' . $attribute->id,
            'status' => 'required|in:active,inactive',
            'values' => 'nullable|array',
            'values.*' => 'nullable|string|max:100',
        ]);

        $attribute->update(['name' => $data['name'], 'status' => $data['status']]);
        $this->syncValues($attribute, $data['values'] ?? []);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated successfully.');
    }

    public function destroy(ProductAttribute $attribute)
    {
        $attribute->values()->delete();
        $attribute->delete();
        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted successfully.');
    }

    private function syncValues(ProductAttribute $attribute, array $values): void
    {
        $attribute->values()->delete();
        foreach (array_filter($values) as $val) {
            $attribute->values()->create([
                'value' => $val,
                'slug' => Str::slug($val),
                'status' => 'active',
            ]);
        }
    }
}
