<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private ImageUploadService $uploader) {}

    public function index()
    {
        $settings = Setting::orderBy('key')->get()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'gst_number' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'order_prefix' => 'nullable|string|max:20',
            'min_order_amount' => 'nullable|numeric|min:0',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'whatsapp_number' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $old = Setting::get('logo');
            $this->uploader->delete($old);
            Setting::set('logo', $this->uploader->upload($request->file('logo'), 'settings'));
        }

        unset($data['logo']);
        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings saved successfully.');
    }
}
