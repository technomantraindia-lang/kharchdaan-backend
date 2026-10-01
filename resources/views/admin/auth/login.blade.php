<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KharchDaan.Com - {{ $portalType === 'sub-admin' ? 'Sub-Admin Staff Portal' : 'Super Admin Portal' }} Login</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
        body {
            background: radial-gradient(circle at top, {{ $portalType === 'sub-admin' ? '#1e1b4b 0%, #0f172a 100%' : '#1e1b18 0%, #0c0a09 100%' }});
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            padding: 24px 16px;
        }
        .login-container {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7);
            padding: 36px 32px;
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
        }
        .portal-switcher {
            display: flex;
            background: #f1f5f9;
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 24px;
            gap: 4px;
        }
        .portal-tab {
            flex: 1;
            text-align: center;
            padding: 8px 12px;
            font-size: 12.5px;
            font-weight: 700;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .portal-tab.active {
            background: #ffffff;
            color: {{ $portalType === 'sub-admin' ? '#4f46e5' : '#ea580c' }};
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .portal-tab:hover:not(.active) {
            color: #1e293b;
            background: #e2e8f0;
        }
        .login-brand-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 12px;
            background: {{ $portalType === 'sub-admin' ? 'linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #8b5cf6 100%)' : 'linear-gradient(135deg, #ea580c 0%, #f97316 50%, #f59e0b 100%)' }};
            box-shadow: 0 10px 25px -5px {{ $portalType === 'sub-admin' ? 'rgba(79, 70, 229, 0.4)' : 'rgba(234, 88, 12, 0.4)' }};
        }
        .login-header { text-align: center; margin-bottom: 22px; }
        .login-header h1 {
            color: #0c0a09;
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 2px;
            letter-spacing: -0.02em;
        }
        .login-header .brand-accent { color: {{ $portalType === 'sub-admin' ? '#4f46e5' : '#ea580c' }}; }
        .login-header .tagline {
            color: {{ $portalType === 'sub-admin' ? '#4f46e5' : '#ea580c' }};
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: 0.02em;
        }
        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            background: {{ $portalType === 'sub-admin' ? '#eef2ff' : '#fff7ed' }};
            color: {{ $portalType === 'sub-admin' ? '#4338ca' : '#c2410c' }};
            border: 1px solid {{ $portalType === 'sub-admin' ? '#c7d2fe' : '#ffedd5' }};
        }
        .form-control {
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 12px 16px;
            font-size: 13.5px;
            transition: all 0.15s ease-in-out;
            background-color: #f8fafc;
        }
        .form-control:focus {
            background-color: #ffffff;
            box-shadow: 0 0 0 3px {{ $portalType === 'sub-admin' ? 'rgba(79, 70, 229, 0.18)' : 'rgba(234, 88, 12, 0.18)' }};
            border-color: {{ $portalType === 'sub-admin' ? '#4f46e5' : '#ea580c' }};
        }
        .password-wrapper { position: relative; }
        .password-toggle-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            font-size: 15px;
        }
        .password-toggle-btn:hover { color: #1e293b; }
        .btn-login {
            background: {{ $portalType === 'sub-admin' ? 'linear-gradient(135deg, #4f46e5 0%, #6366f1 100%)' : 'linear-gradient(135deg, #ea580c 0%, #d97706 100%)' }};
            border: none;
            color: white;
            padding: 13px;
            font-weight: 700;
            border-radius: 12px;
            width: 100%;
            font-size: 14px;
            box-shadow: 0 6px 20px {{ $portalType === 'sub-admin' ? 'rgba(79, 70, 229, 0.35)' : 'rgba(234, 88, 12, 0.35)' }};
            transition: all 0.15s ease;
        }
        .btn-login:hover {
            color: white;
            opacity: 0.96;
            transform: translateY(-1px);
        }
        .alert { border-radius: 12px; font-size: 12.5px; }
        .form-group { margin-bottom: 16px; }
        label {
            color: #334155;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 12.5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        
        <!-- Portal Selector Tabs -->
        <div class="portal-switcher">
            <a href="{{ url('/super-admin/login') }}" class="portal-tab {{ $portalType !== 'sub-admin' ? 'active' : '' }}">
                <i class="fas fa-crown"></i> Super Admin
            </a>
            <a href="{{ url('/sub-admin/login') }}" class="portal-tab {{ $portalType === 'sub-admin' ? 'active' : '' }}">
                <i class="fas fa-user-shield"></i> Sub-Admin Staff
            </a>
        </div>

        <div class="login-header">
            <div class="login-brand-icon">
                <i class="fas {{ $portalType === 'sub-admin' ? 'fa-user-shield' : 'fa-crown' }}"></i>
            </div>
            <h1>KharchDaan<span class="brand-accent">.Com</span></h1>
            <div class="tagline">"तेरा तुझको अर्पण"</div>
            <div class="mt-1">
                <span class="portal-badge">
                    <i class="fas {{ $portalType === 'sub-admin' ? 'fa-id-badge' : 'fa-shield-halved' }}"></i>
                    {{ $portalType === 'sub-admin' ? 'Sub-Admin Staff Portal' : 'Super Admin Master Portal' }}
                </span>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2.5 px-3 mb-3">
                <i class="fas fa-circle-exclamation me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger py-2.5 px-3 mb-3">
                <i class="fas fa-triangle-exclamation me-1"></i> {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success py-2.5 px-3 mb-3">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ request()->url() }}">
            @csrf
            <div class="form-group">
                <label for="login">
                    <i class="fas fa-id-card me-1 text-slate-400"></i> 
                    {{ $portalType === 'sub-admin' ? 'Staff Code (e.g. SUB-1001) or Email' : 'Super Admin Email / Login ID' }}
                </label>
                <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" placeholder="{{ $portalType === 'sub-admin' ? 'Enter Staff Code or Email' : 'admin@example.com' }}" required autofocus>
                @error('login')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="mb-0"><i class="fas fa-lock me-1 text-slate-400"></i> Password</label>
                </div>
                <div class="password-wrapper">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter password" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" title="Show/Hide Password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                <label class="form-check-label text-slate-600 text-xs" for="remember">
                    Keep me signed in
                </label>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-arrow-right-to-bracket me-1"></i> Sign In to {{ $portalType === 'sub-admin' ? 'Sub-Admin Portal' : 'Super Admin Portal' }}
            </button>
        </form>

    </div>

    <script>
        function togglePasswordVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
