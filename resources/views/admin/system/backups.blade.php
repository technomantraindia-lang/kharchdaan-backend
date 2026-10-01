@extends('admin.layouts.app')

@section('title', 'System Backups')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0"><i class="fas fa-database me-2 text-primary"></i>System Backups</h3>
    <form action="{{ admin_route('system.backups.create') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-download me-1"></i> Create Backup
        </button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td class="fw-semibold"><code>{{ $backup['filename'] }}</code></td>
                            <td>{{ $backup['size'] }}</td>
                            <td>{{ $backup['created_at'] }}</td>
                            <td class="text-end">
                                <a href="{{ admin_route('system.backups.download', $backup['filename']) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <form action="{{ admin_route('system.backups.destroy', $backup['filename']) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete backup file?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No backups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
