<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar | Eficaz B2B</title>
    @vite('resources/css/app.css')
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-intro" aria-labelledby="login-intro-title">
            <a href="{{ route('login') }}" class="login-brand" aria-label="Eficaz B2B">
                <span class="login-brand-mark" aria-hidden="true">
                    <span></span><span></span><span></span><span></span>
                </span>
                <span class="login-brand-copy">
                    <span class="login-brand-name">Eficaz B2B</span>
                    <span class="login-brand-caption">Plataforma B2B</span>
                </span>
            </a>

            <div class="login-intro-hero">
                <h1 id="login-intro-title">Reposição de estoque simples e eficiente.</h1>
                <p>Gerencie pedidos, orçamentos e controle de crédito em uma única plataforma B2B industrial.</p>
            </div>

            <ul class="login-highlights" aria-label="Recursos da plataforma">
                <li><span class="login-highlight-icon" aria-hidden="true">01</span> Catálogo de produtos</li>
                <li><span class="login-highlight-icon" aria-hidden="true">02</span> Cotações e pedidos</li>
                <li><span class="login-highlight-icon" aria-hidden="true">03</span> Controle de crédito</li>
            </ul>
        </section>

        <section class="login-content" aria-labelledby="login-title">
            <div class="login-form-wrap">
                <header class="login-heading">
                    <h2 id="login-title">Bem-vindo</h2>
                    <p>Acesse com suas credenciais de revendedor</p>
                </header>

                @if ($errors->any())
                    <div class="login-error" role="alert">
                        <p>Confira os dados informados e tente novamente.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="login-form">
                    @csrf
                    <div class="login-field">
                        <label for="email">E-mail</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            placeholder="nome@empresa.com.br"
                            required
                            autofocus
                            class="login-input"
                        >
                        <x-input-error :messages="$errors->get('email')" />
                    </div>

                    <div class="login-field">
                        <label for="password">Senha</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Sua senha"
                            required
                            class="login-input"
                        >
                        <x-input-error :messages="$errors->get('password')" />
                    </div>

                    <button type="submit" class="login-submit">Entrar como Revendedor</button>
                </form>

                <div class="login-divider" aria-hidden="true"><span></span><span>ou</span><span></span></div>

                <a href="{{ route('admin.dashboard') }}" class="login-admin-link">Acessar Área Administrativa</a>
            </div>
        </section>
    </main>
</body>
</html>
