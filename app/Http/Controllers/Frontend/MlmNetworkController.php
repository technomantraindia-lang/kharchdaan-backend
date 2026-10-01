<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Mlm\MlmCustomerNetworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MlmNetworkController extends Controller
{
    public function __construct(private readonly MlmCustomerNetworkService $networkService) {}

    public function index(Request $request): View
    {
        return view('frontend.mlm.network.index', $this->networkService->dashboard($request->user()));
    }

    public function children(Request $request, Member $member): JsonResponse
    {
        $tree = $request->validate([
            'tree' => ['required', 'in:sponsor,placement'],
        ])['tree'];

        return response()->json($this->networkService->children($request->user(), $member, $tree));
    }

    public function levels(Request $request): View
    {
        return view('frontend.mlm.network.levels', $this->networkService->levels($request->user()));
    }
}
