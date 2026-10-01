@extends('admin.layouts.app')

@section('title', 'Edit Attribute: ' . $attribute->name)

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-pen-to-square text-blue-600"></i> Edit Attribute: <span class="text-slate-700 font-normal">{{ $attribute->name }}</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Update attribute name, status, and associated option values.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('attributes.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Attributes
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <form action="{{ admin_route('attributes.update', $attribute) }}" method="POST" id="attrEditForm" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Attribute Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $attribute->name) }}" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
                <select name="status" class="form-select">
                    <option value="active" @selected($attribute->status === 'active')>Active</option>
                    <option value="inactive" @selected($attribute->status === 'inactive')>Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Option Values (one per line)</label>
                <textarea class="form-control font-mono" rows="6" name="values_text" id="valuesText">{{ $attribute->values->pluck('value')->implode("\n") }}</textarea>
                <div class="text-[11px] text-slate-400 mt-1.5">Enter each value on a new line or separated by commas.</div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ admin_route('attributes.index') }}" class="px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-check"></i> Update Attribute
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('attrEditForm').addEventListener('submit', function(e) {
    const text = document.getElementById('valuesText').value;
    const values = text.split(/[\n,]+/).map(v => v.trim()).filter(v => v);
    values.forEach((v, i) => {
        const input = document.createElement('input');
        input.type = 'hidden'; 
        input.name = 'values[' + i + ']'; 
        input.value = v;
        this.appendChild(input);
    });
});
</script>
@endsection
