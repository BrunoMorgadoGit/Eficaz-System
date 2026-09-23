<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar | Eficaz B2B</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-navy-950">
    <main class="grid min-h-screen lg:grid-cols-[1.1fr_0.9fr]">
        <section class="relative hidden overflow-hidden bg-navy-950 p-12 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 20% 20%, #10c8d4 0, transparent 28%), linear-gradient(135deg, transparent 0 48%, rgba(67, 220, 229, .18) 48% 50%, transparent 50% 100%);"></div>
            <a href="{{ route('login') }}" class="brand-lockup relative z-10 w-fit">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="2.2"><path d="M4 19V9l8-5 8 5v10M8 19v-6h8v6M4 9h16" stroke-linejoin="round"/></svg></span>
                <span><span class="brand-name block">Eficaz B2B</span><span class="brand-caption block">Gestão industrial</span></span>
            </a>
            <div class="relative z-10 max-w-xl">
                <p class="eyebrow text-cyan-400">Portal de operações</p>
                <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-white xl:text-5xl">Relações comerciais mais claras, do catálogo ao pedido.</h1>
                <p class="mt-6 max-w-lg text-base leading-7 text-slate-300">Centralize solicitações, cotações e acompanhamento em uma experiência criada para a rotina B2B industrial.</p>
            </div>
            <p class="relative z-10 text-xs text-slate-500">Eficaz B2B · ambiente corporativo</p>
        </section>

        <section class="flex items-center justify-center bg-slate-50 p-5 sm:p-8">
            <div class="w-full max-w-md">
                <a href="{{ route('login') }}" class="brand-lockup mb-10 lg:hidden">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="2.2"><path d="M4 19V9l8-5 8 5v10M8 19v-6h8v6M4 9h16" stroke-linejoin="round"/></svg></span>
                    <span><span class="brand-name block text-navy-950">Eficaz B2B</span><span class="brand-caption block">Gestão industrial</span></span>
                </a>
                <div class="panel p-6 sm:p-8">
                    <p class="eyebrow">Acesso seguro</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-navy-950">Entre na sua conta</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Use suas credenciais corporativas para continuar.</p>

                    @if ($errors->any())
                        <div class="flash flash-error mt-6 mb-0" role="alert">
                            <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 fill-none stroke-current" stroke-width="2"><path d="M12 9v4m0 4h.01M5.1 19h13.8c1.2 0 1.95-1.3 1.35-2.3L13.35 4.3a1.55 1.55 0 0 0-2.7 0L3.75 16.7C3.15 17.7 3.9 19 5.1 19Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <p>Confira os dados informados e tente novamente.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                        @csrf
                        <div>
                            <label for="email" class="field-label">E-mail corporativo</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="field-control" placeholder="nome@empresa.com.br">
                            <x-input-error :messages="$errors->get('email')" />
                        </div>
                        <div>
                            <label for="password" class="field-label">Senha</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required class="field-control" placeholder="••••••••">
                            <x-input-error :messages="$errors->get('password')" />
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            Manter sessão neste dispositivo
                        </label>
                        <button type="submit" class="btn btn-cyan w-full">Entrar no portal</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
