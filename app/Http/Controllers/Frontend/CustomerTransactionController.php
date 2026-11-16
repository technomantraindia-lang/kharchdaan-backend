<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Mlm\CustomerTransactionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerTransactionController extends Controller
{
    public function __construct(private readonly CustomerTransactionService $transactions) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $payload = $this->transactions->forUser($request->user(), $filters);

        return view('frontend.mlm.transactions.index', [
            'summary' => $payload['summary'],
            'transactions' => $payload['transactions'],
            'filters' => $filters,
        ]);
    }
}
