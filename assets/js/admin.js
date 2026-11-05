// assets/js/admin.js
document.addEventListener("DOMContentLoaded", function() {
    // Sidebar toggle para sa mobile view
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarNav = document.getElementById('sidebarNav');
    
    if(sidebarToggle && sidebarNav) {
        sidebarToggle.addEventListener('click', function() {
            sidebarNav.classList.toggle('show');
        });
    }

    // Show/Hide Password Toggle Logic
    const passwordToggles = document.querySelectorAll('[data-password-toggle]');
    passwordToggles.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('aria-controls');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('.password-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                this.setAttribute('aria-pressed', 'true');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
                this.setAttribute('aria-pressed', 'false');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });
});