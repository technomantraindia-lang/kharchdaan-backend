@extends('admin.layouts.app')

@section('title', 'Platform Settings')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-sliders text-blue-600"></i> Platform Settings
            </h1>
            <p class="text-sm text-slate-500 mt-1">Configure company identity, branding, taxation numbers, and social contact channels.</p>
        </div>
    </div>

    <!-- Settings Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-sm font-bold text-slate-900">General Platform Configuration</h2>
        </div>

        <form action="{{ admin_route('settings.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6 text-xs">
            @csrf
            @method('PUT')

            <!-- Section 1: Brand & Contact -->
            <div>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 mb-4 flex items-center gap-1.5">
                    <i class="fas fa-building text-blue-500"></i> Company Information
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Website / Company Name</label>
                        <input type="text" name="company_name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ $settings['company_name'] ?? 'KharchDaan.Com' }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Contact Email</label>
                        <input type="email" name="company_email" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ $settings['company_email'] ?? 'support@kharchdaan.com' }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Contact Phone</label>
                        <input type="text" name="company_phone" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ $settings['company_phone'] ?? '+91 9900000000' }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">GST Registration Number</label>
                        <input type="text" name="gst_number" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono uppercase" value="{{ $settings['gst_number'] ?? '' }}" placeholder="22AAAAA0000A1Z5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Currency Code</label>
                        <input type="text" name="currency" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono" value="{{ $settings['currency'] ?? 'INR' }}">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Order Number Prefix</label>
                        <input type="text" name="order_prefix" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition font-mono" value="{{ $settings['order_prefix'] ?? 'KD' }}">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block font-semibold text-slate-700 mb-1.5">Registered Office Address</label>
                        <textarea name="company_address" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition">{{ $settings['company_address'] ?? '' }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1.5">Company Logo</label>
                        <input type="file" name="logo" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" accept="image/*">
                        @if(!empty($settings['logo']))
                            <img src="{{ asset('storage/'.$settings['logo']) }}" class="mt-2 h-10 object-contain rounded border border-slate-200 p-1">
                        @endif
                    </div>
                </div>
            </div>

            <!-- Section 2: Social Links -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100 mb-4 flex items-center gap-1.5">
                    <i class="fas fa-share-nodes text-indigo-500"></i> Social & Communication Channels
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">WhatsApp Support Number</label>
                        <input type="text" name="whatsapp_number" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ $settings['whatsapp_number'] ?? '' }}" placeholder="+91 9900000000">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Facebook Page URL</label>
                        <input type="url" name="facebook_url" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ $settings['facebook_url'] ?? '' }}" placeholder="https://facebook.com/kharchdaan">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Instagram Profile URL</label>
                        <input type="url" name="instagram_url" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ $settings['instagram_url'] ?? '' }}" placeholder="https://instagram.com/kharchdaan">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Twitter / X Handle URL</label>
                        <input type="url" name="twitter_url" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 outline-none transition" value="{{ $settings['twitter_url'] ?? '' }}" placeholder="https://x.com/kharchdaan">
                    </div>
                </div>
            </div>

            <!-- Save Action Button -->
            <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">
                    <i class="fas fa-save"></i> Save Platform Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
