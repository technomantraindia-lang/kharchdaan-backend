@extends('admin.layouts.app')
@section('title', 'Pages')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>CMS Pages</h2>
    <a href="{{ admin_route('pages.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Page</a>
</div>
<div class="card"><div class="card-body">
    <table class="table table-hover">
        <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($pages as $page)
            <tr>
                <td><strong>{{ $page->title }}</strong></td>
                <td><code>{{ $page->slug }}</code></td>
                <td><span class="badge bg-{{ $page->status==='active'?'success':'secondary' }}">{{ ucfirst($page->status) }}</span></td>
                <td>{{ $page->updated_at->format('M d, Y') }}</td>
                <td>
                    <a href="{{ admin_route('pages.edit', $page) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    <form action="{{ admin_route('pages.destroy', $page) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">No pages found</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $pages->links() }}
</div></div>
@endsection
