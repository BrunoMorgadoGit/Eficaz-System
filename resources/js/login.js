const passwordInput = document.querySelector('#password');
const passwordToggle = document.querySelector('[data-password-toggle]');

if (passwordInput && passwordToggle) {
    passwordToggle.addEventListener('click', () => {
        const isVisible = passwordInput.type === 'password';

        passwordInput.type = isVisible ? 'text' : 'password';
        passwordToggle.textContent = isVisible ? 'Ocultar' : 'Mostrar';
        passwordToggle.setAttribute('aria-pressed', String(isVisible));
        passwordInput.focus();
    });
}
