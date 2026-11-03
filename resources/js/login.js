document.querySelectorAll('[data-password-toggle]').forEach((passwordToggle) => {
    const passwordInput = document.getElementById(passwordToggle.getAttribute('aria-controls'));

    if (!passwordInput) return;

    passwordToggle.addEventListener('click', () => {
        const isVisible = passwordInput.type === 'password';

        passwordInput.type = isVisible ? 'text' : 'password';
        passwordToggle.textContent = isVisible ? 'Ocultar' : 'Mostrar';
        passwordToggle.setAttribute('aria-pressed', String(isVisible));
        passwordInput.focus();
    });
});
