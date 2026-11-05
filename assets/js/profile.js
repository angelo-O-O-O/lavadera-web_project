document.addEventListener('DOMContentLoaded', function() {
    // Toast auto-hide
    const toast = document.getElementById('successToast');
    if (toast) {
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }

    // Password strength meter
    const newPassword = document.getElementById('newPassword');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    if (newPassword && strengthBar) {
        newPassword.addEventListener('input', function() {
            let val = this.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            let innerDiv = strengthBar.querySelector('div');
            if (score === 1) {
                innerDiv.style.width = '25%';
                innerDiv.style.backgroundColor = '#dc2626';
                strengthText.textContent = 'Weak password';
            } else if (score === 2) {
                innerDiv.style.width = '50%';
                innerDiv.style.backgroundColor = '#d97706';
                strengthText.textContent = 'Fair password';
            } else if (score === 3) {
                innerDiv.style.width = '75%';
                innerDiv.style.backgroundColor = '#2563eb';
                strengthText.textContent = 'Good password';
            } else if (score >= 4) {
                innerDiv.style.width = '100%';
                innerDiv.style.backgroundColor = '#16a34a';
                strengthText.textContent = 'Strong password';
            } else {
                innerDiv.style.width = '0%';
                strengthText.textContent = 'Use at least 8 characters with letters and numbers.';
            }
        });
    }

    // Address Modal handling
    const addressModal = document.getElementById('addressModal');
    const openAddBtn = document.getElementById('openAddAddressModal');
    const closeAddBtn = document.getElementById('closeAddressModal');
    const editBtns = document.querySelectorAll('.edit-addr-btn');

    const modalTitle = document.getElementById('modalTitle');
    const modalAddressId = document.getElementById('modalAddressId');
    const modalLabel = document.getElementById('modalLabel');
    const modalFullAddress = document.getElementById('modalFullAddress');

    if (openAddBtn && addressModal) {
        openAddBtn.addEventListener('click', () => {
            modalTitle.textContent = 'Add New Address';
            modalAddressId.value = '0';
            modalLabel.value = 'Home';
            modalFullAddress.value = '';
            addressModal.showModal();
        });
    }

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modalTitle.textContent = 'Edit Address';
            modalAddressId.value = this.dataset.id;
            modalLabel.value = this.dataset.label;
            modalFullAddress.value = this.dataset.address;
            addressModal.showModal();
        });
    });

    if (closeAddBtn && addressModal) {
        closeAddBtn.addEventListener('click', () => {
            addressModal.close();
        });
    }
});