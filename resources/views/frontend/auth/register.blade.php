@extends('frontend.layouts.app')

@section('title', 'Register')

@section('content')
<section class="relative flex-1 px-4 py-10 sm:py-14">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(249,115,22,0.12),_transparent_55%)]"></div>

    <div class="relative mx-auto w-full max-w-md">
        <div class="rounded-3xl border border-orange-100/80 bg-white p-6 shadow-[0_20px_50px_-24px_rgba(234,88,12,0.35)] sm:p-8">
            <div class="mb-7 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-lg shadow-orange-500/30">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-[1.65rem]">Create Account</h1>
                <p class="mt-2 text-sm font-medium text-slate-500">Register to start shopping and earning with KharchDaan</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 flex items-start gap-2.5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" role="alert">
                    <i class="fas fa-circle-exclamation mt-0.5 text-rose-500"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Full Name</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                            <i class="fas fa-user text-sm"></i>
                        </span>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Madhav Sharma" required autofocus autocomplete="name"
                            class="w-full rounded-xl border {{ $errors->has('name') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50' }} py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:ring-4 focus:ring-orange-500/15">
                    </div>
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Email Address</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email"
                            class="w-full rounded-xl border {{ $errors->has('email') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50' }} py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:ring-4 focus:ring-orange-500/15">
                    </div>
                </div>

                <div>
                    <label for="phone" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Mobile Number</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                            <i class="fas fa-phone text-sm"></i>
                        </span>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="98XXXXXXXX" required autocomplete="tel"
                            class="w-full rounded-xl border {{ $errors->has('phone') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50' }} py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:ring-4 focus:ring-orange-500/15">
                    </div>
                </div>

                <div>
                    <label for="referral_code" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
                        Sponsor / Referral Member ID <span class="normal-case tracking-normal text-slate-400">(Optional)</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                            <i class="fas fa-user-friends text-sm"></i>
                        </span>
                        <input type="text" id="referral_code" name="referral_code" value="{{ old('referral_code', request('ref')) }}" placeholder="e.g. C22020013" autocomplete="off"
                            class="w-full rounded-xl border {{ $errors->has('referral_code') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50' }} py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:ring-4 focus:ring-orange-500/15">
                    </div>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Password</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password"
                            class="w-full rounded-xl border {{ $errors->has('password') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50' }} py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:ring-4 focus:ring-orange-500/15">
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Confirm Password</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:ring-4 focus:ring-orange-500/15">
                    </div>
                </div>

                <button
                    type="submit"
                    class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 px-4 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/25 transition hover:from-orange-700 hover:to-amber-600 hover:shadow-orange-500/40 focus:outline-none focus:ring-4 focus:ring-orange-500/25"
                >
                    <span>Create Account</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-bold text-orange-600 hover:text-orange-700 hover:underline">
                    Login here
                </a>
            </p>
        </div>
    </div>
</section>
@endsection
