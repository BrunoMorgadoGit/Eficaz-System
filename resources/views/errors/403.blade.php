<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso não autorizado | Eficaz B2B</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-navy-950">
    @php
        $errorUser = auth()->user();
        $returnRoute = $errorUser ? (($errorUser->isAdmin() ?? false) ? route('admin.dashboard') : route('reseller.dashboard')) : route('login');
        $returnLabel = $errorUser ? 'Voltar ao painel' : 'Ir para o acesso';
    @endphp
    <main class="grid min-h-screen place-items-center p-5">
        <section class="w-full max-w-lg rounded-3xl bg-white p-8 text-center shadow-2xl shadow-black/20 sm:p-12">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-red-50 text-red-700" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-7 w-7 fill-none stroke-current" stroke-width="1.8"><path d="M12 9v4m0 4h.01M5.1 19h13.8c1.2 0 1.95-1.3 1.35-2.3L13.35 4.3a1.55 1.55 0 0 0-2.7 0L3.75 16.7C3.15 17.7 3.9 19 5.1 19Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <p class="mt-6 text-xs font-bold uppercase tracking-[0.16em] text-red-700">Erro 403</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-navy-950 sm:text-3xl">Você não tem acesso a esta área.</h1>
            <p class="mt-4 text-sm leading-6 text-slate-500">Seu perfil não possui permissão para realizar esta ação. Volte ao seu painel ou entre com uma conta autorizada.</p>
            <a href="{{ $returnRoute }}" class="btn btn-cyan mt-7">{{ $returnLabel }}</a>
        </section>
    </main>
</body>
</html>
