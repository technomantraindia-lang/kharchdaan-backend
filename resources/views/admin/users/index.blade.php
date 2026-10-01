@extends('admin.layouts.app')

@section('title', 'Admin Staff & Sub-Admins')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-user-shield text-blue-600"></i> Platform Staff & Sub-Admins
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage Sub-Admin accounts, view credentials (ID & Passwords), and configure section permissions.</p>
        </div>
        @if(auth()->user()->hasPermission('users.manage') || auth()->user()->isSuperAdmin())
            <div>
                <a href="{{ admin_route('users.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-user-plus"></i> Add Sub-Admin / Staff
                </a>
            </div>
        @endif
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ admin_route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 text-xs">
            <div class="sm:col-span-2">
                <label class="block font-semibold text-slate-600 mb-1">Search Staff</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Search name, email, staff ID..." value="{{ request('search') }}">
                </div>
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Account Status</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition shadow-xs">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ admin_route('users.index') }}" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Administrator Accounts & Access Credentials</h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Super Admin can view and copy Sub-Admin login IDs and assigned passwords.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full">{{ $users->total() }} Total Staff</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Staff Member</th>
                        <th class="py-3 px-4">Login ID / Email</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Login Password (ID/Pass)</th>
                        <th class="py-3 px-4">Permission Privileges</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg {{ $user->isSuperAdmin() ? 'bg-amber-600' : 'bg-blue-600' }} text-white font-bold flex items-center justify-center text-xs shadow-xs">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-[11px] font-mono text-slate-400">ID: {{ $user->staff_code ?? ('#'.$user->id) }}</div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Login ID / Email -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-slate-800 font-semibold text-[11px]">{{ $user->email }}</span>
                                    <button type="button" onclick="copyToClipboard('{{ $user->email }}', this)" class="text-slate-400 hover:text-blue-600 transition" title="Copy Login Email">
                                        <i class="fas fa-copy text-[10px]"></i>
                                    </button>
                                </div>
                                @if(!empty($user->staff_code))
                                    <div class="text-[10px] text-slate-400 font-mono">Code: {{ $user->staff_code }}</div>
                                @endif
                            </td>

                            <!-- Role -->
                            <td class="py-3 px-4">
                                @if($user->isSuperAdmin())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-crown text-[10px]"></i> Super Admin
                                    </span>
                                @elseif($user->role?->name === 'Sub Admin')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        <i class="fas fa-user-gear text-[10px]"></i> Sub Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $user->role?->name ?? 'Staff' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Password Column (Visible for Super Admin) -->
                            <td class="py-3 px-4">
                                @if(auth()->user()->isSuperAdmin())
                                    @if($user->isSuperAdmin())
                                        <span class="text-[11px] text-slate-400 italic">
                                            <i class="fas fa-shield-alt text-amber-500 me-1"></i> (Super Admin Hash)
                                        </span>
                                    @elseif(!empty($user->plain_password))
                                        <div class="inline-flex items-center gap-2 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/80 font-mono text-[11px]">
                                            <span class="password-text" data-pass="{{ $user->plain_password }}">••••••••</span>
                                            <button type="button" onclick="togglePassMask(this)" class="text-slate-500 hover:text-slate-800 transition" title="Show / Hide Password">
                                                <i class="fas fa-eye text-[11px]"></i>
                                            </button>
                                            <button type="button" onclick="copyToClipboard('{{ $user->plain_password }}', this)" class="text-slate-500 hover:text-blue-600 transition" title="Copy Password">
                                                <i class="fas fa-copy text-[11px]"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">Encrypted</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 font-mono">••••••••</span>
                                @endif
                            </td>

                            <!-- Permission Privileges -->
                            <td class="py-3 px-4">
                                @if($user->isSuperAdmin())
                                    <span class="inline-flex items-center gap-1 text-[11px] text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                        <i class="fas fa-unlock"></i> Full Access (All Sections)
                                    </span>
                                @else
                                    @php
                                        $permCount = $user->permissions->count();
                                    @endphp
                                    @if($permCount > 0)
                                        <span class="inline-flex items-center gap-1 text-[11px] text-blue-700 font-semibold bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">
                                            <i class="fas fa-shield-halved"></i> {{ $permCount }} Permission{{ $permCount > 1 ? 's' : '' }} Granted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-500 font-medium bg-slate-100 px-2 py-0.5 rounded-md">
                                            <i class="fas fa-lock"></i> Role Default
                                        </span>
                                    @endif
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }} mr-1"></span>
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-right">
                                @if(auth()->user()->hasPermission('users.manage') || auth()->user()->isSuperAdmin())
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ admin_route('users.edit', $user) }}" class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-200 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 text-slate-600 flex items-center justify-center transition" title="Edit Staff & Permissions">
                                            <i class="fas fa-pen-to-square text-[11px]"></i>
                                        </a>
                                        <form action="{{ admin_route('users.toggleStatus', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-7 h-7 rounded-lg bg-slate-50 border border-slate-200 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-600 text-slate-600 flex items-center justify-center transition" title="{{ $user->status === 'active' ? 'Deactivate Account' : 'Activate Account' }}">
                                                <i class="fas {{ $user->status === 'active' ? 'fa-ban' : 'fa-check' }} text-[11px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-400">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                No admin user accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function togglePassMask(btn) {
    const parent = btn.closest('.inline-flex');
    const textSpan = parent.querySelector('.password-text');
    const icon = btn.querySelector('i');
    const realPass = textSpan.getAttribute('data-pass');

    if (textSpan.innerText === '••••••••') {
        textSpan.innerText = realPass;
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        textSpan.innerText = '••••••••';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-emerald-600 text-[10px]"></i>';
        setTimeout(() => {
            btn.innerHTML = origHtml;
        }, 1500);
    });
}
</script>
@endsection
