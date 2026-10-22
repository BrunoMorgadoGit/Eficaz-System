<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar | Portal Eficaz B2B</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/login.js'])
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-intro" aria-labelledby="login-intro-title">
            <div class="login-intro-brand">
                <span class="login-intro-icon" aria-hidden="true">
                    <img src="{{ asset('image/icon.png') }}" alt="">
                </span>
                <span>PORTAL B2B</span>
            </div>

            <div class="login-intro-hero">
                <p class="login-intro-eyebrow">Eficaz para negócios</p>
                <h2 id="login-intro-title">Sua operação comercial, <span>do catálogo ao pedido.</span></h2>
                <p>Consulte disponibilidade, prepare orçamentos e acompanhe pedidos com clareza.</p>
            </div>

            <p class="login-intro-footer">EFICAZ <span aria-hidden="true">/</span> MARKETING E TECNOLOGIA</p>
        </section>

        <section class="login-content" aria-labelledby="login-title">
            <div class="login-content-inner">
                <div class="login-logo">
                    <img src="{{ asset('image/logo.png') }}" alt="Eficaz Marketing e Tecnologia" width="2172" height="724">
                </div>

                <div class="login-form-wrap">
                    <header class="login-heading">
                        <p class="login-eyebrow">Portal Eficaz B2B</p>
                        <h1 id="login-title">Acesse sua conta</h1>
                        <p>Entre com o e-mail e a senha cadastrados.</p>
                    </header>

                    @if (session('success'))
                        <div class="login-notice login-notice--success" role="status">
                            <x-icon name="check" class="h-4 w-4 shrink-0 text-emerald-700" stroke-width="2" />
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="login-notice login-notice--error" role="alert">
                            Não foi possível entrar. Confira os campos abaixo.
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
                                autocomplete="username"
                                inputmode="email"
                                autocapitalize="none"
                                spellcheck="false"
                                placeholder="nome@empresa.com.br"
                                required
                                autofocus
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                class="login-input @error('email') login-input--invalid @enderror"
                            >
                            <x-input-error id="email-error" :messages="$errors->get('email')" class="login-field-error" />
                        </div>

                        <div class="login-field">
                            <label for="password">Senha</label>
                            <div class="login-password-field">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                    class="login-input login-input--password @error('password') login-input--invalid @enderror"
                                >
                                <button type="button" class="login-password-toggle" data-password-toggle aria-controls="password" aria-pressed="false">Mostrar</button>
                            </div>
                            <x-input-error id="password-error" :messages="$errors->get('password')" class="login-field-error" />
                        </div>

                        <label class="login-remember" for="remember">
                            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                            <span>Manter conectado</span>
                        </label>

                        <button type="submit" class="login-submit">Entrar no portal</button>
                    </form>

                    <p class="login-access-note">Acesso para administradores e revendedores cadastrados.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
