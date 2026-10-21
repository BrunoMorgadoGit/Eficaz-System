<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso não autorizado | Eficaz B2B</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
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
                <x-icon name="alert-triangle" class="h-7 w-7" />
            </span>
            <p class="mt-6 text-xs font-medium uppercase tracking-[0.16em] text-red-700">Erro 403</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-navy-950 sm:text-3xl">Você não tem acesso a esta área.</h1>
            <p class="mt-4 text-sm leading-6 text-slate-500">Seu perfil não possui permissão para realizar esta ação. Volte ao seu painel ou entre com uma conta autorizada.</p>
            <a href="{{ $returnRoute }}" class="btn btn-cyan mt-7">{{ $returnLabel }}</a>
        </section>
    </main>
</body>
</html>
