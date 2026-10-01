<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::withCount('products')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $brands = $query->paginate(15)->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:brands,slug'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('brands', 'public');
        }

        $brand = Brand::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'status' => $validated['status'],
        ]);

        ActivityLogService::log(
            'create',
            'brands',
            "Created brand {$brand->name}",
            $brand,
            null,
            $brand->only(['name', 'slug', 'status'])
        );

        return redirect()->route('admin.brands.index')->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand->id)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $slug = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $oldValues = $brand->only(['name', 'slug', 'description', 'status', 'image']);

        if ($request->hasFile('image')) {
            $brand->image = $request->file('image')->store('brands', 'public');
        }

        $brand->name = $validated['name'];
        $brand->slug = $slug;
        $brand->description = $validated['description'] ?? null;
        $brand->status = $validated['status'];
        $brand->save();

        ActivityLogService::log(
            'update',
            'brands',
            "Updated brand {$brand->name}",
            $brand,
            $oldValues,
            $brand->only(['name', 'slug', 'description', 'status', 'image'])
        );

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->count() > 0) {
            $brand->status = 'inactive';
            $brand->save();

            ActivityLogService::log(
                'status_change',
                'brands',
                "Deactivated brand {$brand->name} because it is referenced by products",
                $brand
            );

            return redirect()->route('admin.brands.index')->with('warning', 'Brand is linked to existing products. It has been deactivated instead of deleted.');
        }

        $brandName = $brand->name;
        $brand->delete();

        ActivityLogService::log(
            'delete',
            'brands',
            "Deleted brand {$brandName}",
            null
        );

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted successfully.');
    }
}
