<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public static function getGroupedPermissions(): array
    {
        return [
            'Dashboard & Overview' => [
                'icon' => 'fas fa-chart-line',
                'description' => 'Access to Direct Selling analytics & executive summary',
                'permissions' => [
                    'dashboard.view' => 'View Dashboard & KPI Analytics',
                ],
            ],
            'MLM / Direct Selling' => [
                'icon' => 'fas fa-sitemap',
                'description' => 'Tree placement, genealogy, sponsor network & calculations',
                'permissions' => [
                    'mlm.view' => 'View Direct Selling Network & Tree',
                    'mlm.manage' => 'Manage Hierarchy & Tree Movements',
                    'mlm.create' => 'Register / Add Direct Selling Members',
                    'mlm.edit' => 'Edit Member Profiles',
                    'mlm.move' => 'Execute 1:3 Placement Matrix Movements',
                    'mlm.block' => 'Block / Suspend Member Accounts',
                    'mlm.kyc.view' => 'View Member KYC Documents',
                    'mlm.kyc.review' => 'Approve / Reject KYC Submissions',
                    'mlm.activity.view' => 'View Member Activity Timeline',
                    'mlm.export' => 'Export Direct Selling Network Data',
                ],
            ],
            '100% Cashback Program' => [
                'icon' => 'fas fa-hand-holding-dollar',
                'description' => 'Profit pools, batch allocation, approvals & payout scheduling',
                'permissions' => [
                    'cashback.view' => 'View Cashback Pools & Batches',
                    'cashback.pool.manage' => 'Create / Modify Profit Pools',
                    'cashback.select' => 'Run Member Batch Selection',
                    'cashback.approve' => 'Approve Cashback Batches',
                    'cashback.schedule' => 'Schedule Payout Dates',
                    'cashback.pay' => 'Process & Mark Cashback Paid',
                    'cashback.hold' => 'Hold / Pause Cashback Records',
                    'cashback.reverse' => 'Reverse Cashback Disbursals',
                ],
            ],
            'Products & Catalog' => [
                'icon' => 'fas fa-boxes-stacked',
                'description' => 'Store catalog, product variations & bulk imports',
                'permissions' => [
                    'products.view' => 'View Product Catalog',
                    'products.create' => 'Add New Products',
                    'products.edit' => 'Edit Products & Pricing',
                    'products.delete' => 'Delete Products',
                    'products.import' => 'Import Products via CSV',
                    'products.bulk_manage' => 'Bulk Product Operations',
                ],
            ],
            'Categories, Brands & Attributes' => [
                'icon' => 'fas fa-folder-tree',
                'description' => 'Taxonomy, brands and custom variation attributes',
                'permissions' => [
                    'categories.view' => 'View Categories',
                    'categories.create' => 'Create Categories',
                    'categories.edit' => 'Edit Categories',
                    'categories.delete' => 'Delete Categories',
                    'brands.view' => 'View Brands',
                    'brands.manage' => 'Manage Brands',
                    'attributes.view' => 'View Attributes',
                    'attributes.manage' => 'Manage Attributes & Variations',
                ],
            ],
            'Stock & Inventory' => [
                'icon' => 'fas fa-warehouse',
                'description' => 'Stock tracking, low stock alerts & manual adjustments',
                'permissions' => [
                    'inventory.view' => 'View Stock Levels & Logs',
                    'inventory.adjust' => 'Adjust Stock & Log Adjustments',
                ],
            ],
            'Orders, Payments & Returns' => [
                'icon' => 'fas fa-bag-shopping',
                'description' => 'Customer orders, invoices, payment verification & RTO/returns',
                'permissions' => [
                    'orders.view' => 'View Orders & Invoices',
                    'orders.edit' => 'Edit Orders & Verify Payment',
                    'orders.status' => 'Update Order Status & Dispatch',
                    'orders.cancel' => 'Cancel Orders',
                    'payments.view' => 'View Payment Transactions',
                    'payments.manage' => 'Manage Manual Payment Proofs',
                    'invoices.view' => 'View & Print Invoices',
                    'refunds.view' => 'View Returns & RTO Shipments',
                    'refunds.manage' => 'Process Returns & Status Updates',
                ],
            ],
            'Customers & Support Inquiries' => [
                'icon' => 'fas fa-users-gear',
                'description' => 'Customer accounts and lead/support inquiries',
                'permissions' => [
                    'customers.view' => 'View Registered Customers',
                    'inquiries.view' => 'View Inquiries & Messages',
                    'inquiries.manage' => 'Update & Resolve Inquiries',
                ],
            ],
            'Coupons, Shipping & Taxes' => [
                'icon' => 'fas fa-ticket',
                'description' => 'Promotional discounts, delivery zones and GST rates',
                'permissions' => [
                    'coupons.view' => 'View Coupons',
                    'coupons.manage' => 'Create & Manage Coupons',
                    'shipping.view' => 'View Shipping Methods',
                    'shipping.manage' => 'Configure Delivery Rates',
                    'taxes.view' => 'View GST Tax Rules',
                    'taxes.manage' => 'Configure GST Rates',
                ],
            ],
            'Reporting Suite' => [
                'icon' => 'fas fa-chart-pie',
                'description' => 'Revenue, Direct Selling network, profit margins and GST reports',
                'permissions' => [
                    'reports.view' => 'View Reports & Charts',
                    'reports.export' => 'Export Reports (Excel/CSV/PDF)',
                ],
            ],
            'WooCommerce Integration' => [
                'icon' => 'fas fa-rotate',
                'description' => 'Two-way synchronization and conflict management',
                'permissions' => [
                    'woocommerce.view' => 'View WooCommerce Sync Status',
                    'woocommerce.manage' => 'Trigger Sync & Resolve Conflicts',
                ],
            ],
            'CMS & Settings' => [
                'icon' => 'fas fa-sliders',
                'description' => 'Homepage banners, static pages and platform config',
                'permissions' => [
                    'cms.view' => 'View Banners & Pages',
                    'cms.manage' => 'Edit Banners & Pages',
                    'settings.view' => 'View Platform Settings',
                    'settings.manage' => 'Edit Platform Settings',
                    'activity_logs.view' => 'View System Audit Trail',
                ],
            ],
            'Admin Staff Management' => [
                'icon' => 'fas fa-user-shield',
                'description' => 'Manage internal administrative staff & sub-admin permissions',
                'permissions' => [
                    'users.view' => 'View Staff Directory',
                    'users.manage' => 'Create & Edit Sub-Admin Access',
                    'roles.view' => 'View System Roles',
                    'roles.manage' => 'Manage Role Definitions',
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $query = User::with(['role', 'permissions'])
            ->where(function ($q) {
                $q->whereHas('role', function ($rq) {
                    $rq->whereIn('name', ['Super Admin', 'Admin', 'Sub Admin', 'Accounts', 'Staff', 'Manager']);
                })->orWhereDoesntHave('role', function ($rq) {
                    $rq->where('name', 'Customer');
                });
            })
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $authUser = Auth::user();
        $rolesQuery = Role::whereIn('name', ['Super Admin', 'Sub Admin', 'Admin', 'Accounts']);
        if (! $authUser->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->get();
        $groupedPermissions = self::getGroupedPermissions();

        return view('admin.users.create', compact('roles', 'groupedPermissions'));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $targetRole = Role::findOrFail($validated['role_id']);

        if ($targetRole->name === 'Super Admin' && ! $authUser->isSuperAdmin()) {
            return back()->withInput()->with('error', 'Only Super Admin can assign the Super Admin role.');
        }

        $staffCode = $request->input('staff_code');
        if (empty($staffCode)) {
            $latestId = (int) (User::max('id') ?? 0) + 1;
            $staffCode = 'STAFF-' . str_pad((string) $latestId, 4, '0', STR_PAD_LEFT);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'staff_code' => $staffCode,
            'phone' => $validated['phone'] ?? null,
            'role_id' => $validated['role_id'],
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
            'plain_password' => $targetRole->name !== 'Super Admin' ? $validated['password'] : null,
        ]);

        // If not Super Admin, attach selected individual permissions
        if ($targetRole->name !== 'Super Admin' && ! empty($request->input('permissions'))) {
            $permissionIds = Permission::whereIn('name', $request->input('permissions'))->pluck('id');
            $user->permissions()->sync($permissionIds);
        }

        ActivityLogService::log(
            'create',
            'users',
            "Created admin staff user {$user->name} ({$user->email} / {$user->staff_code}) with role {$targetRole->name}",
            $user,
            null,
            $user->only(['name', 'email', 'staff_code', 'phone', 'role_id', 'status'])
        );

        return redirect()->route('admin.users.index')->with('success', "Staff user {$user->name} created successfully with ID: {$user->staff_code}.");
    }

    public function edit(User $user)
    {
        $authUser = Auth::user();

        // Prevent normal admin from editing a super admin
        if ($user->isSuperAdmin() && ! $authUser->isSuperAdmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Only Super Admin can edit Super Admin users.');
        }

        $rolesQuery = Role::whereIn('name', ['Super Admin', 'Sub Admin', 'Admin', 'Accounts']);
        if (! $authUser->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->get();

        $user->load('permissions');
        $userPermissions = $user->permissions->pluck('name')->toArray();
        $groupedPermissions = self::getGroupedPermissions();

        return view('admin.users.edit', compact('user', 'roles', 'groupedPermissions', 'userPermissions'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        if ($user->isSuperAdmin() && ! $authUser->isSuperAdmin()) {
            return back()->withInput()->with('error', 'Only Super Admin can edit Super Admin users.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'staff_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'staff_code')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $targetRole = Role::findOrFail($validated['role_id']);

        if ($targetRole->name === 'Super Admin' && ! $authUser->isSuperAdmin()) {
            return back()->withInput()->with('error', 'Only Super Admin can assign the Super Admin role.');
        }

        // Prevent regular Admin from deactivating their own account
        if ($authUser->id === $user->id && $validated['status'] !== 'active' && ! $authUser->isSuperAdmin()) {
            return back()->withInput()->with('error', 'You cannot deactivate your own admin account.');
        }

        // Prevent disabling the only active Super Admin account
        if ($user->isSuperAdmin() && ($validated['status'] !== 'active' || $targetRole->name !== 'Super Admin')) {
            $activeSuperAdminsCount = User::whereHas('role', fn ($q) => $q->where('name', 'Super Admin'))
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();

            if ($activeSuperAdminsCount === 0) {
                return back()->withInput()->with('error', 'Cannot disable or reassign role for the only active Super Admin.');
            }
        }

        $oldValues = $user->only(['name', 'email', 'staff_code', 'phone', 'role_id', 'status']);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (! empty($validated['staff_code'])) {
            $user->staff_code = $validated['staff_code'];
        }
        $user->phone = $validated['phone'] ?? null;
        $user->role_id = $validated['role_id'];
        $user->status = $validated['status'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->plain_password = $targetRole->name !== 'Super Admin' ? $validated['password'] : null;
        }

        $user->save();

        // Sync permissions if not Super Admin
        if ($targetRole->name !== 'Super Admin') {
            $selectedPerms = $request->input('permissions', []);
            $permissionIds = Permission::whereIn('name', $selectedPerms)->pluck('id');
            $user->permissions()->sync($permissionIds);
        } else {
            // Super Admin has unrestricted access to all sections
            $user->permissions()->detach();
        }

        ActivityLogService::log(
            'update',
            'users',
            "Updated staff user {$user->name} ({$user->email}) and permission privileges",
            $user,
            $oldValues,
            $user->only(['name', 'email', 'phone', 'role_id', 'status'])
        );

        return redirect()->route('admin.users.index')->with('success', "Staff user {$user->name} updated successfully.");
    }

    public function toggleStatus(User $user)
    {
        $authUser = Auth::user();

        if ($authUser->id === $user->id && $user->status === 'active' && ! $authUser->isSuperAdmin()) {
            return back()->with('error', 'You cannot deactivate your own admin account.');
        }

        if ($user->isSuperAdmin() && ! $authUser->isSuperAdmin()) {
            return back()->with('error', 'Only Super Admin can modify Super Admin accounts.');
        }

        if ($user->isSuperAdmin() && $user->status === 'active') {
            $activeSuperAdminsCount = User::whereHas('role', fn ($q) => $q->where('name', 'Super Admin'))
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();

            if ($activeSuperAdminsCount === 0) {
                return back()->with('error', 'Cannot deactivate the only active Super Admin.');
            }
        }

        $oldStatus = $user->status;
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        ActivityLogService::log(
            'status_change',
            'users',
            "Toggled status for {$user->email} from {$oldStatus} to {$user->status}",
            $user,
            ['status' => $oldStatus],
            ['status' => $user->status]
        );

        return back()->with('success', 'User status updated successfully.');
    }
}
