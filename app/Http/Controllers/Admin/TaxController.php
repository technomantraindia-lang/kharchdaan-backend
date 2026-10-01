<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index()
    {
        $taxes = Tax::latest()->paginate(15);
        return view('admin.taxes.index', compact('taxes'));
    }

    public function create()
    {
        return view('admin.taxes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'desc' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        Tax::create($data);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax rate created successfully.');
    }

    public function edit(Tax $tax)
    {
        return view('admin.taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'desc' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $tax->update($data);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax rate updated successfully.');
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();
        return redirect()->route('admin.taxes.index')->with('success', 'Tax rate deleted successfully.');
    }
}
