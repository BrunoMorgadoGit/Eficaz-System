<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Definir senha | Portal Eficaz B2B</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/login.js'])
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-intro" aria-label="Portal Eficaz B2B">
            <div class="login-intro-brand">
                <span class="login-intro-icon" aria-hidden="true"><img src="{{ asset('image/icon.png') }}" alt=""></span>
                <span>PORTAL B2B</span>
            </div>
            <div class="login-intro-hero">
                <p class="login-intro-eyebrow">Acesso seguro</p>
                <h2>Crie sua senha para acessar o <span>portal Eficaz B2B.</span></h2>
                <p>Use uma senha pessoal e não compartilhe seu link de acesso.</p>
            </div>
            <p class="login-intro-footer">EFICAZ <span aria-hidden="true">/</span> MARKETING E TECNOLOGIA</p>
        </section>

        <section class="login-content" aria-labelledby="password-title">
            <div class="login-content-inner">
                <div class="login-logo">
                    <img src="{{ asset('image/logo.png') }}" alt="Eficaz Marketing e Tecnologia" width="2172" height="724">
                </div>
                <div class="login-form-wrap">
                    <header class="login-heading">
                        <p class="login-eyebrow">Primeiro acesso</p>
                        <h1 id="password-title">Defina sua senha</h1>
                        <p>Crie uma senha com pelo menos 8 caracteres.</p>
                    </header>

                    @if ($errors->any())
                        <div class="login-notice login-notice--error" role="alert">
                            @foreach ($errors->all() as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" class="login-form">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="login-field">
                            <label for="email">E-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" class="login-input" autocomplete="username" required autofocus>
                        </div>

                        <div class="login-field">
                            <label for="password">Nova senha</label>
                            <div class="login-password-field">
                                <input id="password" name="password" type="password" class="login-input login-input--password" autocomplete="new-password" required>
                                <button type="button" class="login-password-toggle" data-password-toggle aria-controls="password" aria-pressed="false">Mostrar</button>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="password_confirmation">Confirme a senha</label>
                            <div class="login-password-field">
                                <input id="password_confirmation" name="password_confirmation" type="password" class="login-input login-input--password" autocomplete="new-password" required>
                                <button type="button" class="login-password-toggle" data-password-toggle aria-controls="password_confirmation" aria-pressed="false">Mostrar</button>
                            </div>
                        </div>

                        <button type="submit" class="login-submit">Salvar senha e entrar</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
