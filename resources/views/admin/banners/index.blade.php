@extends('admin.layouts.app')
@section('title', 'Banners')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Banners</h2>
    <a href="{{ admin_route('banners.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Banner</a>
</div>
<div class="card"><div class="card-body">
    <table class="table table-hover">
        <thead><tr><th>Title</th><th>Image URL</th><th>Link</th><th>Sort</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($banners as $banner)
            <tr>
                <td><strong>{{ $banner->title }}</strong></td>
                <td><small>{{ \Illuminate\Support\Str::limit($banner->image, 40) }}</small></td>
                <td>{{ $banner->link ? \Illuminate\Support\Str::limit($banner->link, 30) : '-' }}</td>
                <td>{{ $banner->sort_order }}</td>
                <td><span class="badge bg-{{ $banner->status==='active'?'success':'secondary' }}">{{ ucfirst($banner->status) }}</span></td>
                <td>
                    <a href="{{ admin_route('banners.edit', $banner) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    <form action="{{ admin_route('banners.destroy', $banner) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No banners found</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $banners->links() }}
</div></div>
@endsection
