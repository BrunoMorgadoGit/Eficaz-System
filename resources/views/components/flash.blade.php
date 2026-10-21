@if (session('success'))
    <div class="flash flash-success" role="status">
        <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2" />
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="flash flash-error" role="alert">
        <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2" />
        <p>{{ session('error') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="flash flash-error" role="alert">
        <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2" />
        <div>
            <p class="font-medium">Não foi possível concluir a ação.</p>
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
        <x-icon name="info-circle" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2" />
        <p>{{ session('status') }}</p>
    </div>
@endif
