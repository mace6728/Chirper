@php
    $turnstileSiteKey = config('services.turnstile.site_key');
    $turnstileConfigured = filled($turnstileSiteKey) && filled(config('services.turnstile.secret_key'));
@endphp

@push('head')
    @if ($turnstileConfigured)
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush

<x-layout>
    <x-slot:title>
        Sign In
    </x-slot:title>

    <div class="hero min-h-[calc(100vh-16rem)]">
        <div class="hero-content flex-col">
            <div class="card w-96 bg-base-100">
                <div class="card-body">
                    @error('oauth')
                        <div role="alert" class="alert alert-error mb-4">
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <h1 class="text-3xl font-bold text-center mb-6">Welcome Back</h1>

                    <form method="POST" action="/login">
                        @csrf

                        <!-- Email -->
                        <label class="floating-label mb-6">
                            <input type="email" name="email"
                                placeholder="[mail@example.com](<mailto:mail@example.com>)" value="{{ old('email') }}"
                                class="input input-bordered @error('email')
input-error
@enderror" required autofocus>
                            <span>Email</span>
                        </label>
                        @error('email')
                            <div class="label -mt-4 mb-2">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </div>
                        @enderror

                        <!-- Password -->
                        <label class="floating-label mb-6">
                            <input type="password" name="password" placeholder="••••••••"
                                class="input input-bordered @error('password') input-error @enderror" required>
                            <span>Password</span>
                        </label>
                        @error('password')
                            <div class="label -mt-4 mb-2">
                                <span class="label-text-alt text-error">{{ $message }}</span>
                            </div>
                        @enderror

                        <!-- Remember Me -->
                        <div class="form-control mt-4">
                            <label class="label cursor-pointer justify-start">
                                <input type="checkbox" name="remember" class="checkbox">
                                <span class="label-text ml-2">Remember me</span>
                            </label>
                        </div>

                        <div class="mt-6 space-y-2">
                            @if ($turnstileConfigured)
                                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-action="login"
                                    data-theme="auto" data-size="flexible"></div>
                            @else
                                <div role="alert" class="alert alert-error">
                                    <span>Security verification is unavailable. Set your Turnstile keys to enable sign
                                        in.</span>
                                </div>
                            @endif

                            @error('cf-turnstile-response')
                                <div class="label px-1">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="form-control mt-8">
                            <button type="submit" class="btn btn-primary btn-sm w-full" @disabled(!$turnstileConfigured)>
                                Sign In
                            </button>
                        </div>
                    </form>

                    <div class="divider">OR</div>

                    <x-social-auth-buttons />

                    <div class="divider">OR</div>
                    <p class="text-center text-sm">
                        Don't have an account?
                        <a href="/register" class="link link-primary">Register</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layout>
