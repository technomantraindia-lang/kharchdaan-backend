@extends('admin.layouts.app')

@section('title', 'Add Attribute')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-plus-circle text-blue-600"></i> Add Product Attribute
            </h1>
            <p class="text-sm text-slate-500 mt-1">Create a global product attribute and define its allowed values.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('attributes.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Attributes
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <form action="{{ admin_route('attributes.store') }}" method="POST" id="attrForm" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Attribute Name *</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Size, Weight, Flavor, Pack Size" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Option Values (one per line)</label>
                <textarea class="form-control font-mono" rows="5" id="valuesText" placeholder="Small&#10;Medium&#10;Large&#10;500g&#10;1kg"></textarea>
                <div class="text-[11px] text-slate-400 mt-1.5">Enter each value on a new line. You can also separate values by commas.</div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ admin_route('attributes.index') }}" class="px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-check"></i> Save Attribute
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('attrForm').addEventListener('submit', function(e) {
    const values = document.getElementById('valuesText').value.split(/[\n,]+/).map(v => v.trim()).filter(v => v);
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
