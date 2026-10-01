<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customerRoleId = Role::where('name', 'Customer')->value('id');

        $query = User::where('role_id', $customerRoleId)->withCount('orders')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();
        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        $customer->load(['orders' => fn ($q) => $q->latest()->limit(10), 'addresses']);
        return view('admin.customers.show', compact('customer'));
    }
}
