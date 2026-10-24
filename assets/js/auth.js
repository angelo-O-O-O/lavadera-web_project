document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const inputId = toggle.getAttribute('aria-controls');
        const input = inputId ? document.getElementById(inputId) : null;

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        const icon = toggle.querySelector('.password-icon');
        icon.classList.toggle('bi-eye', !showPassword);
        icon.classList.toggle('bi-eye-slash', showPassword);

        const fieldLabel = input.name.replace(/_/g, ' ');
        toggle.setAttribute('aria-label', `${showPassword ? 'Hide' : 'Show'} ${fieldLabel}`);
        toggle.setAttribute('aria-pressed', String(showPassword));
    });
});