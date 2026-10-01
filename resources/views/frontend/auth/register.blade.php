@extends('frontend.layouts.app')

@section('title', 'Register')

@section('content')
<div class="row justify-content-center my-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-gradient" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Create Account</h2>
                    <p class="text-muted">Register to start shopping fresh groceries</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus>
                        </div>
                        @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control bg-light border-start-0 @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" required>
                        </div>
                        @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-phone text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0 @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="9876543210" required>
                        </div>
                        @error('phone')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control bg-light border-start-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="••••••••" required>
                        </div>
                        @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control bg-light border-start-0" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold text-white shadow-sm mb-3">Register</button>

                    <div class="text-center">
                        <span class="text-muted">Already have an account?</span>
                        <a href="{{ route('login') }}" class="text-decoration-none fw-bold" style="color: var(--primary);">Login here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
