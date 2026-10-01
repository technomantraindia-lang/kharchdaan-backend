<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index()
    {
        $methods = ShippingMethod::latest()->paginate(15);
        return view('admin.shipping.index', compact('methods'));
    }

    public function create()
    {
        return view('admin.shipping.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'charge' => 'required|numeric|min:0',
            'min_free_order' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        ShippingMethod::create($data);

        return redirect()->route('admin.shipping.index')->with('success', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $shipping)
    {
        return view('admin.shipping.edit', ['method' => $shipping]);
    }

    public function update(Request $request, ShippingMethod $shipping)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'charge' => 'required|numeric|min:0',
            'min_free_order' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $shipping->update($data);

        return redirect()->route('admin.shipping.index')->with('success', 'Shipping method updated successfully.');
    }

    public function destroy(ShippingMethod $shipping)
    {
        $shipping->delete();
        return redirect()->route('admin.shipping.index')->with('success', 'Shipping method deleted successfully.');
    }
}
