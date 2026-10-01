<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManualMlmCalculationRequest;
use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmCalculationRun;
use App\Services\Mlm\MlmCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class MlmCalculationController extends Controller
{
    public function __construct(private readonly MlmCalculationService $calculationService) {}

    public function index(): View
    {
        return view('admin.mlm.calculations.index', $this->pageData());
    }

    public function preview(ManualMlmCalculationRequest $request): View
    {
        $data = $request->validated();
        $member = Member::query()->with('user')->findOrFail($data['purchasing_member_id']);
        $rule = MlmCalculationRule::query()->findOrFail($data['rule_version_id']);

        $reference = trim($data['transaction_reference']);
        $existing = MlmCalculationRun::query()
            ->where('source_transaction_reference', $reference)
            ->first();

        $preview = $this->calculationService->preview(
            $member,
            $data['eligible_amount'],
            $data['transaction_reference'],
            $data['transaction_date'],
            $rule
        );

        if ($existing) {
            $preview['already_exists'] = true;
            $preview['existing_run_id'] = $existing->id;
        }

        return view('admin.mlm.calculations.index', [
            ...$this->pageData(),
            'preview' => $preview,
            'formData' => $data,
        ]);
    }

    public function store(ManualMlmCalculationRequest $request): RedirectResponse
    {
        if (! $request->boolean('confirmed')) {
            throw ValidationException::withMessages([
                'confirmed' => 'Review the calculation preview and confirm before saving.',
            ]);
        }

        $data = $request->validated();
        $member = Member::query()->findOrFail($data['purchasing_member_id']);
        $rule = MlmCalculationRule::query()->findOrFail($data['rule_version_id']);
        $reference = trim($data['transaction_reference']);

        $existing = MlmCalculationRun::query()
            ->where('source_transaction_reference', $reference)
            ->first();

        if ($existing) {
            return redirect()->route('admin.mlm.calculations.index')
                ->with('warning', "Transaction reference '{$reference}' already exists (Run #{$existing->id}). Duplicate income was not created.");
        }

        $run = $this->calculationService->calculate(
            $member,
            $data['eligible_amount'],
            $data['transaction_reference'],
            $data['transaction_date'],
            $rule,
            $request->user()
        );

        return redirect()->route('admin.mlm.calculations.index')
            ->with('success', "Calculation #{$run->id} saved successfully.");
    }

    private function pageData(): array
    {
        return [
            'members' => Member::query()->with('user')->orderBy('id')->get(),
            'rules' => MlmCalculationRule::query()->where('status', 'active')->orderByDesc('effective_from')->get(),
            'runs' => MlmCalculationRun::query()
                ->with(['purchasingMember.user', 'ruleVersion'])
                ->latest()
                ->paginate(15),
            'preview' => null,
            'formData' => [],
        ];
    }
}
