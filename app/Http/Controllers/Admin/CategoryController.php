<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private ImageUploadService $uploader) {}

    public function index()
    {
        $categories = Category::with('parent')->withCount(['mainProducts', 'subProducts', 'subSubProducts'])->latest()->paginate(15);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $categoryOptions = $this->buildCategoryOptions();

        return view('admin.categories.create', compact('categoryOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data['slug'] = Str::slug($data['name']);
        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->upload($request->file('image'), 'categories');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $excludeIds = array_merge([$category->id], $category->getDescendantIds());
        $categoryOptions = $this->buildCategoryOptions($excludeIds);

        return view('admin.categories.edit', compact('category', 'categoryOptions'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data['slug'] = Str::slug($data['name']);
        if ($request->hasFile('image')) {
            $this->uploader->delete($category->image);
            $data['image'] = $this->uploader->upload($request->file('image'), 'categories');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->uploader->delete($category->image);
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }

    private function buildCategoryOptions(array $excludeIds = []): array
    {
        $categories = Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->when(! empty($excludeIds), fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->orderBy('name')
            ->get();

        $childrenByParent = $categories->groupBy(fn ($category) => $category->parent_id ?? 0);

        return $this->flattenCategoryOptions($childrenByParent);
    }

    private function flattenCategoryOptions($childrenByParent, int $parentId = 0, int $depth = 0): array
    {
        $options = [];

        foreach ($childrenByParent->get($parentId, collect()) as $category) {
            $options[] = [
                'id' => $category->id,
                'label' => str_repeat('-- ', $depth) . $category->name,
            ];

            $options = array_merge(
                $options,
                $this->flattenCategoryOptions($childrenByParent, $category->id, $depth + 1)
            );
        }

        return $options;
    }
}
