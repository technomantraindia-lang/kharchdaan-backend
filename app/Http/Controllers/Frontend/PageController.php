<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('status', 'active')->firstOrFail();

        return view('frontend.pages.show', compact('page'));
    }
}
