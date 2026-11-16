<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Mlm\CustomerTransactionService;
use Illuminate\Http\Request;

class CustomerTransactionApiController extends Controller
{
    public function __construct(private readonly CustomerTransactionService $transactions) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transactions->forUser($request->user(), $filters),
        ]);
    }
}
