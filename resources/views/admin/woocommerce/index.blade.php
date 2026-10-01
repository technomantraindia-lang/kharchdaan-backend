@extends('admin.layouts.app')

@section('title', 'WooCommerce Connection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0"><i class="fab fa-wordpress me-2 text-primary"></i>WooCommerce Integration</h3>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="fw-bold m-0"><i class="fas fa-plug me-2 text-secondary"></i>Connection Configuration</h5>
                @if($config['is_configured'])
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> Configured</span>
                @else
                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle me-1"></i> Not Configured</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Store URL</label>
                        <div class="form-control bg-light"><code>{{ $config['url'] }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">API Version</label>
                        <div class="form-control bg-light"><code>{{ $config['api_version'] }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Consumer Key (Masked)</label>
                        <div class="form-control bg-light"><code>{{ $config['consumer_key_masked'] }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Consumer Secret (Masked)</label>
                        <div class="form-control bg-light"><code>{{ $config['consumer_secret_masked'] }}</code></div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between">
                    <small class="text-muted"><i class="fas fa-lock me-1"></i> Credentials are safely read from <code>.env</code> file.</small>
                    @if(auth()->user()->hasPermission('woocommerce.manage'))
                        <form action="{{ admin_route('woocommerce.testConnection') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt me-1"></i> Test Connection
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if(!empty($testResult))
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0"><i class="fas fa-info-circle me-2 text-info"></i>Test Connection Diagnostics</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Result:</strong> {{ $testResult['message'] }}</p>
                    @if(!empty($testResult['details']))
                        <pre class="bg-dark text-light p-3 rounded small mb-0" style="max-height: 250px; overflow-y: auto;">{{ json_encode($testResult['details'], JSON_PRETTY_PRINT) }}</pre>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-shield-alt text-success me-2"></i>Security Note</h6>
                <p class="small text-muted mb-2">
                    Consumer Keys and Secrets are securely processed on the backend server and are <strong>never</strong> transmitted to the browser or embedded in Blade templates.
                </p>
                <hr>
                <h6 class="fw-bold text-dark mb-2">Setup Instructions</h6>
                <ol class="small text-muted ps-3 mb-0">
                    <li class="mb-1">Generate API credentials in WooCommerce Admin: <em>Settings &rarr; Advanced &rarr; REST API</em>.</li>
                    <li class="mb-1">Copy Consumer Key & Secret into server <code>.env</code> file.</li>
                    <li class="mb-1">Click <strong>Test Connection</strong> to verify authorization.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
