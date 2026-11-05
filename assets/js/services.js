document.addEventListener('DOMContentLoaded', function() {
    const labels = document.querySelectorAll('.service-card-label');
    const searchInput = document.getElementById('serviceSearch');
    const sortSelect = document.getElementById('serviceSort');
    const chips = document.querySelectorAll('.chip');
    const grid = document.getElementById('serviceGrid');
    
    const sumPlaceholder = document.getElementById('summaryPlaceholder');
    const sumDetails = document.getElementById('summaryDetails');
    const sumName = document.getElementById('sumName');
    const sumTime = document.getElementById('sumTime');
    const sumPrice = document.getElementById('sumPrice');
    const continueBtn = document.getElementById('continueBtn');

    let currentCategory = 'All';

    function updateSummary(label) {
        if (!label) {
            sumPlaceholder.style.display = 'block';
            sumDetails.style.display = 'none';
            continueBtn.disabled = true;
            return;
        }
        
        sumPlaceholder.style.display = 'none';
        sumDetails.style.display = 'flex';
        
        sumName.textContent = label.dataset.name;
        sumTime.innerHTML = label.dataset.time;
        sumPrice.textContent = '₱' + parseFloat(label.dataset.price).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' / ' + label.dataset.unit;
        continueBtn.disabled = false;
    }

    const prechecked = document.querySelector('.service-radio:checked');
    if (prechecked) {
        const parentLabel = prechecked.closest('.service-card-label');
        if (parentLabel) {
            parentLabel.classList.add('selected');
            updateSummary(parentLabel);
        }
    }

    labels.forEach(label => {
        const radio = label.querySelector('.service-radio');
        label.addEventListener('click', function() {
            labels.forEach(l => l.classList.remove('selected'));
            label.classList.add('selected');
            radio.checked = true;
            updateSummary(label);
        });
    });

    function filterAndSortCards() {
        if (!grid) return;
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const sortValue = sortSelect ? sortSelect.value : 'name';

        const cardArray = Array.from(labels);

        cardArray.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const category = card.dataset.category;
            
            const matchesSearch = name.includes(searchTerm);
            const matchesCategory = (currentCategory === 'All' || category === currentCategory);

            if (matchesSearch && matchesCategory) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        cardArray.sort((a, b) => {
            if (sortValue === 'price-low') {
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            } else if (sortValue === 'fastest') {
                return parseInt(a.dataset.mins) - parseInt(b.dataset.mins);
            } else {
                return a.dataset.name.localeCompare(b.dataset.name);
            }
        });

        cardArray.forEach(card => grid.appendChild(card));
    }

    if (searchInput) searchInput.addEventListener('input', filterAndSortCards);
    if (sortSelect) sortSelect.addEventListener('change', filterAndSortCards);

    chips.forEach(chip => {
        chip.addEventListener('click', function() {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentCategory = chip.dataset.category;
            filterAndSortCards();
        });
    });
});