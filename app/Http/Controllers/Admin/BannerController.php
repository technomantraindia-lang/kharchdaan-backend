<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(private ImageUploadService $uploader) {}

    public function index()
    {
        $banners = Banner::orderBy('sort_order')->paginate(15);
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $data['image'] = $this->uploader->upload($request->file('image'), 'banners');
        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $this->uploader->delete($banner->image);
            $data['image'] = $this->uploader->upload($request->file('image'), 'banners');
        } else {
            unset($data['image']);
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner)
    {
        $this->uploader->delete($banner->image);
        $banner->delete();
        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted successfully.');
    }
}
