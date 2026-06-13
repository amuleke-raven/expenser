@if(app('impersonate')->isImpersonating())
    <div class="flex items-center justify-between gap-4 bg-warning-500 px-6 py-2 text-sm font-medium text-white">
        <span>
            You are impersonating <strong>{{ auth()->user()->name }}</strong>
        </span>
        <a href="{{ route('impersonate.leave') }}" class="rounded bg-white/20 px-3 py-1 text-white hover:bg-white/30">
            Stop Impersonating
        </a>
    </div>
@endif
