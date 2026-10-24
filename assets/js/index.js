document.documentElement.classList.add('home-js-enabled');

const homeNavigation = document.querySelector('#homeNavigation');

if (homeNavigation && window.bootstrap) {
    homeNavigation.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', () => {
            if (homeNavigation.classList.contains('show')) {
                window.bootstrap.Collapse.getOrCreateInstance(homeNavigation).hide();
            }
        });
    });
}

const serviceList = document.querySelector('#services-list');
const addonList = document.querySelector('#service-addons-list');
const phpCurrency = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});

function showCatalogMessage(container, message, isError = false) {
    const paragraph = document.createElement('p');
    paragraph.className = `home-catalog-status${isError ? ' is-error' : ''}`;
    paragraph.textContent = message;
    container.replaceChildren(paragraph);
}

async function loadServices() {
    if (!serviceList) return;

    try {
        const response = await fetch('api/get-services.php', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Services request failed');

        const services = await response.json();
        if (!Array.isArray(services) || services.length === 0) {
            showCatalogMessage(serviceList, 'No services are listed right now. Please check back soon.');
            return;
        }

        const fragment = document.createDocumentFragment();
        const categoryLabels = {
            clothes_pillowcases: 'Clothes & pillowcases',
            blankets_comforters_bedcovers: 'Blankets & bedcovers',
        };
        const unitLabels = { per_7kg: 'per 7 kg', per_5kg: 'per 5 kg', per_kg: 'per kg' };

        services.forEach((service) => {
            const column = document.createElement('div');
            column.className = 'col-md-6 col-xl-4';

            const card = document.createElement('article');
            card.className = 'home-service-card home-catalog-card home-service-card-featured';

            const icon = document.createElement('span');
            icon.className = 'home-service-icon service-icon-white';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<i class="bi bi-water"></i>';

            const category = document.createElement('span');
            category.className = 'home-service-number';
            category.textContent = categoryLabels[service.laundry_category] || 'Laundry service';

            const name = document.createElement('h3');
            name.textContent = service.service_name || 'Laundry service';

            const description = document.createElement('p');
            description.className = 'home-catalog-description';
            description.textContent = service.description || 'Carefully handled by the Lavadera team.';

            const price = document.createElement('div');
            price.className = 'home-catalog-price';
            const amount = document.createElement('strong');
            amount.textContent = phpCurrency.format(Number(service.price) || 0);
            const unit = document.createElement('span');
            unit.textContent = unitLabels[service.pricing_unit] || '';
            price.append(amount, unit);

            card.append(icon, category, name, description, price);
            column.append(card);
            fragment.append(column);
        });

        serviceList.replaceChildren(fragment);
    } catch (error) {
        showCatalogMessage(serviceList, 'Sorry, we couldn’t show the services right now. Please try again later.', true);
    }
}

async function loadServiceAddons() {
    if (!addonList) return;

    try {
        const response = await fetch('api/get-services_addons.php', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Add-ons request failed');

        const addons = await response.json();
        if (!Array.isArray(addons) || addons.length === 0) {
            showCatalogMessage(addonList, 'No extras are listed right now.');
            return;
        }

        const fragment = document.createDocumentFragment();
        addons.forEach((addon) => {
            const item = document.createElement('article');
            item.className = 'home-addon-item';

            const name = document.createElement('h4');
            name.textContent = addon.addon_name || 'Laundry add-on';

            const price = document.createElement('span');
            price.textContent = phpCurrency.format(Number(addon.price) || 0);

            item.append(name, price);
            fragment.append(item);
        });

        addonList.replaceChildren(fragment);
    } catch (error) {
        showCatalogMessage(addonList, 'Sorry, we couldn’t show the extras right now. Please try again later.', true);
    }
}

loadServices();
loadServiceAddons();
