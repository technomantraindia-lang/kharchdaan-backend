<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('order.user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('inv_num', 'like', "%{$search}%");
        }

        $invoices = $query->paginate(15)->withQueryString();
        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('order.user', 'order.items.product');
        return view('admin.invoices.show', compact('invoice'));
    }
}
