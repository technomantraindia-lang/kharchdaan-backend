<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmCalculationRule;
use App\Models\MlmCalculationRun;
use Illuminate\Contracts\View\View;

class MlmCalculationController extends Controller
{
    public function index(): View
    {
        return view('admin.mlm.calculations.index', [
            'activeRule' => MlmCalculationRule::query()
                ->where('status', 'active')
                ->orderByDesc('effective_from')
                ->first(),
            'runs' => MlmCalculationRun::query()
                ->with(['purchasingMember.user', 'ruleVersion'])
                ->latest()
                ->paginate(15),
        ]);
    }
}
