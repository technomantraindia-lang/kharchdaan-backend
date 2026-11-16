<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Mlm\SponsorPlacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SponsorPlacementController extends Controller
{
    public function __construct(private readonly SponsorPlacementService $placement) {}

    public function index(Request $request): View
    {
        return view('frontend.mlm.network.placement', $this->placement->page($request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'joinee_member_id' => ['required', 'integer', 'exists:mlm_members,id'],
            'new_parent_id' => ['required', 'integer', 'exists:mlm_members,id'],
            'new_position' => ['required', 'in:'.implode(',', Member::POSITIONS)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->placement->place($request->user(), $data);

        return redirect()
            ->route('network.placement')
            ->with('success', 'Joinee placed successfully in your network.');
    }
}
