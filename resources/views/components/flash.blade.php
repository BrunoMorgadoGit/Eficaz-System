@if (session('success'))
    <div class="flash flash-success" role="status">
        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="flash flash-error" role="alert">
        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2"><path d="M12 9v4m0 4h.01M5.1 19h13.8c1.2 0 1.95-1.3 1.35-2.3L13.35 4.3a1.55 1.55 0 0 0-2.7 0L3.75 16.7C3.15 17.7 3.9 19 5.1 19Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <p>{{ session('error') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="flash flash-error" role="alert">
        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2"><path d="M12 9v4m0 4h.01M5.1 19h13.8c1.2 0 1.95-1.3 1.35-2.3L13.35 4.3a1.55 1.55 0 0 0-2.7 0L3.75 16.7C3.15 17.7 3.9 19 5.1 19Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div>
            <p class="font-semibold">Não foi possível concluir a ação.</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4 text-sm">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if (session('status'))
    <div class="flash flash-info" role="status">
        <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2"><path d="M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round"/></svg>
        <p>{{ session('status') }}</p>
    </div>
@endif
