@extends('admin.layouts.app')

@section('title', 'Edit Staff / Sub-Admin: ' . $user->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-user-gear text-blue-600"></i> Edit Staff: {{ $user->name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Modify staff profile, role, and granular section permissions.</p>
        </div>
        <div>
            <a href="{{ admin_route('users.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Staff Directory
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Staff Profile & Access Controls</h2>
            <span class="text-xs text-slate-500">User ID: #{{ $user->id }}</span>
        </div>

        <form method="POST" action="{{ admin_route('users.update', $user) }}" class="p-6 space-y-6 text-xs" id="userEditForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition @error('name') border-rose-400 @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition @error('email') border-rose-400 @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Phone Number</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition @error('phone') border-rose-400 @enderror" value="{{ old('phone', $user->phone) }}">
                    @error('phone')<div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">System Role <span class="text-rose-500">*</span></label>
                    <select name="role_id" id="roleSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition @error('role_id') border-rose-400 @enderror" required onchange="handleRoleChange(this)">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-name="{{ $role->name }}" @selected(old('role_id', $user->role_id) == $role->id)>
                                {{ $role->name }} {{ $role->name === 'Super Admin' ? '(Unrestricted Full Access)' : ($role->name === 'Sub Admin' ? '(Custom Permission Sections)' : '') }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')<div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1.5">Account Status <span class="text-rose-500">*</span></label>
                    <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition @error('status') border-rose-400 @enderror" required>
                        <option value="active" @selected(old('status', $user->status) === 'active')>Active (Can Login)</option>
                        <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive (Login Blocked)</option>
                        <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option>
                    </select>
                    @error('status')<div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Password Change Section -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 mb-4">
                    Change Password (Leave blank to keep current password)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">New Password</label>
                        <input type="password" name="password" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition @error('password') border-rose-400 @enderror" placeholder="Enter new password (optional)">
                        @error('password')<div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" placeholder="Confirm new password">
                    </div>
                </div>
            </div>

            <!-- Super Admin Notice Banner -->
            <div id="superAdminNotice" class="hidden p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-900">
                <div class="flex items-center gap-2 font-bold text-xs mb-1">
                    <i class="fas fa-crown text-amber-600"></i> Super Admin Role Selected
                </div>
                <p class="text-[11px] text-amber-700">
                    Super Admins have unrestricted, full permissions across every module and section of the platform.
                </p>
            </div>

            <!-- Permissions Matrix Section -->
            <div id="permissionsSection" class="pt-4 border-t border-slate-100 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fas fa-key text-blue-600"></i> Module Access & Section Permissions
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Toggle which modules and actions this user is permitted to access.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="toggleAllPermissions(true)" class="px-2.5 py-1 text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200 transition">
                            <i class="fas fa-check-double mr-1"></i> Select All
                        </button>
                        <button type="button" onclick="toggleAllPermissions(false)" class="px-2.5 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded border border-slate-200 transition">
                            <i class="fas fa-times mr-1"></i> Deselect All
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($groupedPermissions as $groupTitle => $groupData)
                    <div class="bg-slate-50/70 rounded-xl border border-slate-200/90 p-4 space-y-3 hover:border-slate-300 transition">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2 font-bold text-slate-800">
                                <span class="w-6 h-6 rounded-md bg-white border border-slate-200 flex items-center justify-center text-blue-600 text-xs shadow-2xs">
                                    <i class="{{ $groupData['icon'] }}"></i>
                                </span>
                                <span>{{ $groupTitle }}</span>
                            </div>
                            <button type="button" onclick="toggleGroupCheckboxes(this)" class="text-[10px] font-semibold text-blue-600 hover:text-blue-800">
                                Toggle
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-500 leading-tight">{{ $groupData['description'] }}</p>
                        
                        <div class="space-y-2 pt-1">
                            @foreach($groupData['permissions'] as $permKey => $permLabel)
                            <label class="flex items-start gap-2 cursor-pointer hover:text-blue-700 select-none group">
                                <input type="checkbox" name="permissions[]" value="{{ $permKey }}" class="perm-checkbox mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20" @checked(is_array(old('permissions')) ? in_array($permKey, old('permissions')) : in_array($permKey, $userPermissions))>
                                <span class="text-[11px] text-slate-700 group-hover:text-slate-900 font-medium leading-tight">
                                    {{ $permLabel }}
                                    <code class="block text-[9px] text-slate-400 font-mono">{{ $permKey }}</code>
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-5 border-t border-slate-100 flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-save"></i> Update User & Permissions
                </button>
                <a href="{{ admin_route('users.index') }}" class="inline-flex items-center px-4 py-2.5 font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function handleRoleChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const roleName = selectedOption ? (selectedOption.getAttribute('data-name') || '').toLowerCase() : '';
    const permSection = document.getElementById('permissionsSection');
    const superNotice = document.getElementById('superAdminNotice');

    if (roleName === 'super admin') {
        permSection.classList.add('opacity-40', 'pointer-events-none');
        superNotice.classList.remove('hidden');
    } else {
        permSection.classList.remove('opacity-40', 'pointer-events-none');
        superNotice.classList.add('hidden');
    }
}

function toggleAllPermissions(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = checked;
    });
}

function toggleGroupCheckboxes(btn) {
    const card = btn.closest('.bg-slate-50\\/70');
    if (!card) return;
    const checkboxes = card.querySelectorAll('.perm-checkbox');
    const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
    checkboxes.forEach(cb => {
        cb.checked = anyUnchecked;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const roleSelect = document.getElementById('roleSelect');
    if (roleSelect) {
        handleRoleChange(roleSelect);
    }
});
</script>
@endsection
