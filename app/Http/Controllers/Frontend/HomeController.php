<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Member;
use App\Models\MlmPayoutCycle;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::active()->featured()->with(['category', 'brand'])->latest()->limit(8)->get();
        if ($featuredProducts->isEmpty()) {
            $featuredProducts = Product::active()->with(['category', 'brand'])->latest()->limit(8)->get();
        }

        $latestProducts = Product::active()->with(['category', 'brand'])->latest()->limit(8)->get();
        $categories = Category::where('status', 'active')->whereNull('parent_id')->withCount('products')->get();
        $banners = Banner::where('status', 'active')->orderBy('sort_order')->get();
        $settings = Setting::pluck('value', 'key');

        $stats = [
            'members' => max(Member::where('status', 'active')->count(), 100),
            'orders' => Order::where('status', 'delivered')->count(),
            'levels' => 20,
            'payouts' => MlmPayoutCycle::count(),
        ];

        return view('frontend.home', compact('featuredProducts', 'latestProducts', 'categories', 'banners', 'settings', 'stats'));
    }
}
