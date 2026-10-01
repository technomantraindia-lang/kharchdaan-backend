<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('order.user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(15)->withQueryString();
        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment->load('order.user', 'order.items.product');
        return view('admin.payments.show', compact('payment'));
    }
}
