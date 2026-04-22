<div class="space-y-3">
    <a href="{{ route('oauth.redirect', ['provider' => 'google']) }}"
        class="btn btn-outline btn-sm w-full justify-center gap-3 border-base-300 bg-base-100 hover:border-base-content/20 hover:bg-base-200">
        <svg viewBox="0 0 24 24" aria-hidden="true" class="size-4 shrink-0">
            <path fill="#EA4335"
                d="M12 10.2v3.9h5.42c-.24 1.25-.95 2.3-2.02 3.01l3.27 2.54c1.9-1.75 2.99-4.32 2.99-7.37 0-.71-.06-1.4-.18-2.07H12Z" />
            <path fill="#34A853"
                d="M12 22c2.7 0 4.96-.9 6.61-2.45l-3.27-2.54c-.9.61-2.06.97-3.34.97-2.57 0-4.75-1.73-5.53-4.06H3.09v2.62A9.98 9.98 0 0 0 12 22Z" />
            <path fill="#4A90E2"
                d="M6.47 13.92A5.97 5.97 0 0 1 6.16 12c0-.67.11-1.32.31-1.92V7.46H3.09A9.98 9.98 0 0 0 2 12c0 1.61.39 3.13 1.09 4.54l3.38-2.62Z" />
            <path fill="#FBBC05"
                d="M12 6.02c1.47 0 2.79.51 3.82 1.51l2.86-2.86C16.95 3.05 14.7 2 12 2a9.98 9.98 0 0 0-8.91 5.46l3.38 2.62c.78-2.33 2.96-4.06 5.53-4.06Z" />
        </svg>
        <span>Continue with Google</span>
    </a>

    <a href="{{ route('oauth.redirect', ['provider' => 'github']) }}"
        class="btn btn-outline btn-sm w-full justify-center gap-3 border-base-300 bg-base-100 hover:border-base-content/20 hover:bg-base-200">
        <svg viewBox="0 0 24 24" aria-hidden="true" class="size-4 shrink-0 fill-current">
            <path
                d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.1.79-.25.79-.56 0-.28-.01-1.19-.02-2.15-3.2.7-3.88-1.36-3.88-1.36-.52-1.33-1.28-1.68-1.28-1.68-1.05-.71.08-.69.08-.69 1.16.08 1.78 1.19 1.78 1.19 1.03 1.77 2.71 1.26 3.37.97.1-.75.4-1.26.72-1.55-2.55-.29-5.24-1.27-5.24-5.67 0-1.25.45-2.27 1.18-3.07-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.17A10.9 10.9 0 0 1 12 6.03c.97 0 1.95.13 2.87.38 2.19-1.48 3.15-1.17 3.15-1.17.63 1.58.24 2.75.12 3.04.73.8 1.18 1.82 1.18 3.07 0 4.41-2.69 5.38-5.25 5.66.41.36.78 1.07.78 2.17 0 1.57-.01 2.84-.01 3.23 0 .31.21.67.8.56A11.5 11.5 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5Z" />
        </svg>
        <span>Continue with GitHub</span>
    </a>
</div>