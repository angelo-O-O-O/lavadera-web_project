<?php
// pages/home.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$fullName = $_SESSION['full_name'];
$firstName = explode(' ', trim($fullName))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f8fb">
    <title>Dashboard | Lavadera Laundry Shop</title>
    
    <!-- Bootstrap CSS & Icons -->
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-navy: #1e4f8f;
            --primary-dark: #153a6a;
            --accent-blue: #89b4f8;
            --bg-color: #f4f8fb;
            --text-main: #2d3748;
            --text-muted: #718096;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --spacing-1: 8px;
            --spacing-2: 16px;
            --spacing-3: 24px;
            --spacing-4: 32px;
            --spacing-5: 48px;
        }

        body {
            background-color: var(--bg-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
            margin: 0;
        }

        .app-container {
            max-width: 1200px;
            margin: 0 auto;
            padding-left: var(--spacing-3);
            padding-right: var(--spacing-3);
        }

        @media (min-width: 768px) {
            .app-container {
                padding-left: var(--spacing-4);
                padding-right: var(--spacing-4);
            }
        }

        /* Top Navigation */
        .navbar-custom {
            padding: var(--spacing-2) 0;
            background-color: transparent;
        }
        .brand-logo {
            height: 40px;
            width: 40px;
            object-fit: cover;
            border-radius: 10px;
        }
        .nav-link-custom {
            color: #273b7a;
            font-weight: 600;
            text-decoration: none;
            padding: 10px var(--spacing-2);
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            min-height: 44px;
        }
        .nav-link-custom:hover {
            background-color: var(--border-color);
            color: var(--primary-navy);
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--primary-dark) 100%);
            border-radius: 24px;
            padding: var(--spacing-5) var(--spacing-4);
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(30, 79, 143, 0.15);
            margin-bottom: var(--spacing-4);
        }
        .banner-circles {
            position: absolute;
            bottom: -60px;
            right: -60px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.05);
            pointer-events: none;
        }
        .banner-circles::before {
            content: '';
            position: absolute;
            top: 20px; left: 20px; right: 20px; bottom: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.05);
        }
        .banner-title {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: var(--spacing-2);
        }
        .banner-subtitle {
            font-size: clamp(0.95rem, 2vw, 1.15rem);
            color: #e2e8f0;
            max-width: 550px;
            margin-bottom: 0;
        }
        .sparkle-icon {
            color: var(--accent-blue);
            font-size: 1.1rem;
            margin-right: var(--spacing-1);
        }

        /* Layout Grid System */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--spacing-4);
            align-items: start;
            margin-bottom: var(--spacing-5);
        }

        @media (min-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 340px 1fr;
            }
        }

        .sticky-column {
            position: static;
        }
        @media (min-width: 1024px) {
            .sticky-column {
                position: sticky;
                top: var(--spacing-3);
            }
        }

        /* Cards Design */
        .smooth-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            padding: var(--spacing-4);
            border: 1px solid rgba(0, 0, 0, 0.02);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            height: 100%;
            box-sizing: border-box;
        }
        
        .icon-wrapper {
            background-color: var(--bg-color);
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--spacing-3);
            color: var(--primary-navy);
            font-size: 1.75rem;
            transition: all 0.3s ease;
        }
        .smooth-card:hover .icon-wrapper {
            background-color: var(--primary-navy);
            color: #ffffff;
        }

        .card-title-main {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: var(--spacing-1);
        }
        .card-text-main {
            color: var(--text-muted);
            font-size: 0.925rem;
            line-height: 1.5;
            margin-bottom: var(--spacing-3);
        }

        /* Buttons */
        .btn-custom {
            background-color: #273b7a;
            color: white;
            border-radius: 12px;
            padding: 12px var(--spacing-3);
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            width: 100%;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px rgba(39, 59, 122, 0.2);
        }
        .btn-custom:hover {
            background-color: #1b2956;
            color: white;
            transform: translateY(-1px);
        }

        .btn-outline-custom {
            background-color: transparent;
            color: #273b7a;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 10px var(--spacing-3);
            font-weight: 600;
            font-size: 0.9rem;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .btn-outline-custom:hover {
            border-color: #273b7a;
            color: #273b7a;
            background-color: #f8fafc;
        }

        /* Services List */
        .section-header {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: var(--spacing-3);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .services-list-wrapper {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-2);
        }

        .service-item {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: var(--spacing-3);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            border-left: 5px solid var(--primary-navy);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--spacing-2);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            width: 100%;
            box-sizing: border-box;
        }

        @media (min-width: 576px) {
            .service-item {
                flex-direction: row;
                align-items: center;
            }
        }

        .service-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .service-info {
            flex: 1;
            padding-right: var(--spacing-2);
        }
        .service-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .service-desc {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0;
            line-height: 1.4;
        }

        .service-pricing-block {
            display: flex;
            flex-direction: row;
            align-items: baseline;
            gap: 6px;
            width: 100%;
            justify-content: space-between;
            border-top: 1px solid var(--border-color);
            padding-top: var(--spacing-2);
            margin-top: 4px;
        }

        @media (min-width: 576px) {
            .service-pricing-block {
                flex-direction: column;
                align-items: flex-end;
                justify-content: center;
                border-top: none;
                padding-top: 0;
                margin-top: 0;
                min-width: 110px;
            }
        }

        .service-price {
            font-size: 1.15rem;
            font-weight: 800;
            color: #273b7a;
            white-space: nowrap;
        }
        .service-unit {
            font-size: 0.775rem;
            color: var(--text-muted);
            background-color: var(--bg-color);
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: capitalize;
            white-space: nowrap;
        }

        /* COOL INTERACTIVE SERVICE SELECTION CARDS IN MODAL */
        .service-option-card {
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #fff;
            position: relative;
        }
        .service-option-card:hover {
            border-color: var(--accent-blue);
            background-color: #f8fafc;
        }
        .service-option-card.selected {
            border-color: var(--primary-navy);
            background-color: #f0f4fd;
            box-shadow: 0 4px 12px rgba(30, 79, 143, 0.1);
        }
        .service-option-card input[type="radio"] {
            position: absolute;
            top: 16px;
            right: 16px;
            accent-color: var(--primary-navy);
        }

        /* Live Price Preview Box */
        .price-preview-box {
            background-color: #f8fafc;
            border-radius: 14px;
            padding: 16px;
            border: 1px dashed var(--border-color);
        }
    </style>
</head>
<body>

    <div class="app-container">

        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-md navbar-custom d-flex justify-content-between align-items-center">
            <a href="home.php" class="text-decoration-none d-flex align-items-center gap-2">
                <img src="../img/logo.jpg" alt="Lavadera Logo" class="brand-logo shadow-sm">
                <span class="fs-5 fw-bold" style="color: var(--primary-navy);">Lavadera</span>
            </a>
            
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#topMenu" aria-controls="topMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="topMenu">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 mt-3 mt-md-0">
                    <a href="my-orders.php" class="nav-link-custom">
                        <i class="bi bi-clock-history me-2"></i> My Orders
                    </a>
                    <a href="#" class="nav-link-custom">
                        <i class="bi bi-person-gear me-2"></i> Profile Settings
                    </a>
                    <a href="../login.php" class="btn btn-outline-custom rounded-pill px-4 mt-2 mt-md-0">
                        Sign out <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Welcome Banner -->
        <section class="welcome-banner">
            <div class="banner-circles"></div>
            <div class="position-relative" style="z-index: 2;">
                <span class="text-uppercase tracking-wider small fw-semibold mb-2 d-block" style="color: var(--accent-blue);">
                    <i class="bi bi-stars sparkle-icon"></i> A fresh start, every time
                </span>
                <h1 class="banner-title">Welcome back, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>!</h1>
                <p class="banner-subtitle">
                    Manage your laundry, track your orders, and enjoy a little more time for the things you love.
                </p>
            </div>
        </section>

        <!-- Dashboard Grid -->
        <main class="dashboard-grid">
            
            <!-- Left Sticky Card -->
            <div class="sticky-column">
                <div class="smooth-card text-center text-md-start">
                    <div class="icon-wrapper mx-auto mx-md-0">
                        <i class="bi bi-basket-fill"></i>
                    </div>
                    <h3 class="card-title-main">Book Laundry</h3>
                    <p class="card-text-main">Choose your preferred washing service and drop off your clothes instantly.</p>
                    <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#bookingModal">
                        Book Now <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
            
            <!-- Right Available Services List -->
            <div class="services-column">
                <div class="section-header">
                    <span>Available Services</span>
                    <span class="badge bg-light text-muted fw-normal small px-3 py-2 rounded-pill border">Per-kg calculation</span>
                </div>
                
                <div id="services-container" class="services-list-wrapper">
                    <div class="text-center py-5 text-muted smooth-card">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        Loading available services...
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- COOL INTERACTIVE BOOKING MODAL WITH LIVE CALCULATION -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg p-3 p-md-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-1" id="bookingModalLabel">Create Laundry Booking</h4>
                        <p class="text-muted small mb-0">Select your preferred care package and enter your estimated weight.</p>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="bookingForm">
                        
                        <!-- 1. Interactive Selectable Service Cards Container -->
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold mb-2">1. CHOOSE SERVICE TYPE</label>
                            <div id="modalServicesContainer" class="row g-3">
                                <!-- Populated dynamically via JS -->
                                <div class="text-center py-3 text-muted">Loading options...</div>
                            </div>
                        </div>

                        <!-- 2. Weight Input with automatic calculation -->
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold mb-2">2. ESTIMATED WEIGHT (KG)</label>
                            <div class="input-group">
                                <input type="number" class="form-control rounded-start-3 p-3 shadow-none border-secondary-subtle fs-5 fw-bold text-primary" name="weight" id="weightInput" placeholder="e.g. 5" min="1" step="0.5" value="3" required>
                                <span class="input-group-text rounded-end-3 bg-light px-4 text-muted fw-bold">Kilograms</span>
                            </div>
                            <div class="form-text small text-muted mt-1">Tip: Standard load estimation is around 3 to 7 kg per batch.</div>
                        </div>

                        <!-- 3. Live Price Breakdown Preview Box -->
                        <div class="price-preview-box mb-4 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small d-block">Estimated Total Price</span>
                                <span class="fs-4 fw-bold text-dark" id="liveTotalPrice">₱0.00</span>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill" id="liveServiceBadge">Select a service</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-custom py-3 fs-6" id="submitBookingBtn">
                            Confirm and Book Now <i class="bi bi-check-circle ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle & SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- AJAX & Live Calculation Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let fetchedServices = [];
            const container = document.getElementById('services-container');
            const modalServicesContainer = document.getElementById('modalServicesContainer');
            const weightInput = document.getElementById('weightInput');
            const liveTotalPrice = document.getElementById('liveTotalPrice');
            const liveServiceBadge = document.getElementById('liveServiceBadge');

            // Function to calculate live price dynamically
            function updateLivePrice() {
                const selectedRadio = document.querySelector('input[name="service_id"]:checked');
                const weight = parseFloat(weightInput.value) || 0;

                if (selectedRadio && weight > 0) {
                    const pricePerUnit = parseFloat(selectedRadio.getAttribute('data-price')) || 0;
                    const serviceName = selectedRadio.getAttribute('data-name');
                    
                    const total = pricePerUnit * weight;
                    liveTotalPrice.textContent = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(total);
                    liveServiceBadge.textContent = `${serviceName} (${weight} kg)`;
                } else {
                    liveTotalPrice.textContent = '₱0.00';
                    liveServiceBadge.textContent = 'Select a service';
                }
            }

            // Fetch available services from backend API
            fetch('../api/get-services.php')
                .then(response => response.json())
                .then(data => {
                    fetchedServices = data;
                    container.innerHTML = '';
                    modalServicesContainer.innerHTML = '';
                    
                    if(data.error) {
                        container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }

                    data.forEach((service, index) => {
                        const priceFormat = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(service.price);
                        const unitFormat = service.pricing_unit ? service.pricing_unit.replace('_', ' ') : 'kg';

                        // 1. Render main dashboard service card
                        const cardHTML = `
                            <div class="service-item">
                                <div class="service-info">
                                    <h4 class="service-name">${service.service_name}</h4>
                                    <p class="service-desc">${service.description || 'Professional laundry handling and care.'}</p>
                                </div>
                                <div class="service-pricing-block">
                                    <div class="service-price">${priceFormat}</div>
                                    <span class="service-unit">per ${unitFormat}</span>
                                </div>
                            </div>
                        `;
                        container.innerHTML += cardHTML;

                        // 2. Render cool interactive selection cards inside the modal
                        const isChecked = index === 0 ? 'checked' : '';
                        const activeClass = index === 0 ? 'selected' : '';
                        
                        const optionCardHTML = `
                            <div class="col-12 col-md-4">
                                <div class="service-option-card ${activeClass}" onclick="selectServiceCard(this, ${service.service_id})">
                                    <input class="form-check-input" type="radio" name="service_id" id="service_${service.service_id}" value="${service.service_id}" data-price="${service.price}" data-name="${service.service_name}" ${isChecked} required>
                                    <h6 class="fw-bold text-dark mb-1">${service.service_name}</h6>
                                    <p class="text-muted small mb-2" style="font-size: 0.8rem; line-height: 1.2;">${service.description || ''}</p>
                                    <span class="text-primary fw-bold small">${priceFormat} / ${unitFormat}</span>
                                </div>
                            </div>
                        `;
                        modalServicesContainer.innerHTML += optionCardHTML;
                    });

                    // Trigger initial calculation once rendered
                    updateLivePrice();
                })
                .catch(error => {
                    container.innerHTML = `<div class="alert alert-danger p-3 rounded-3">Failed to load services. Please check your connection.</div>`;
                    console.error('Error fetching services:', error);
                });

            // Helper function to handle interactive card click styling
            window.selectServiceCard = function(cardElement, serviceId) {
                document.querySelectorAll('.service-option-card').forEach(c => c.classList.remove('selected'));
                cardElement.classList.add('selected');
                const radio = document.getElementById(`service_${serviceId}`);
                if (radio) {
                    radio.checked = true;
                    updateLivePrice();
                }
            };

            // Listen to weight input changes for real-time total calculation
            weightInput.addEventListener('input', updateLivePrice);

            // Also listen to radio changes in case clicked directly
            document.addEventListener('change', function(e) {
                if (e.target && e.target.name === 'service_id') {
                    document.querySelectorAll('.service-option-card').forEach(c => c.classList.remove('selected'));
                    e.target.closest('.service-option-card').classList.add('selected');
                    updateLivePrice();
                }
            });

            // 3. Handle Booking Form Submission via AJAX
            const bookingForm = document.getElementById('bookingForm');
            if(bookingForm) {
                bookingForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const btn = document.getElementById('submitBookingBtn');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing Order...';
                    btn.disabled = true;

                    const formData = new FormData(this);

                    fetch('../api/book-laundry.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;

                        if(data.success) {
                            const modalEl = document.getElementById('bookingModal');
                            const modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if(modalInstance) modalInstance.hide();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Booking Confirmed!',
                                text: 'Tracking Number: ' + data.tracking_no,
                                confirmButtonColor: '#273b7a'
                            }).then(() => {
                                window.location.href = 'my-orders.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Booking Failed',
                                text: data.message || 'Something went wrong.',
                                confirmButtonColor: '#273b7a'
                            });
                        }
                    })
                    .catch(error => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            text: 'Please check your connection and try again.',
                            confirmButtonColor: '#273b7a'
                        });
                        console.error('Booking error:', error);
                    });
                });
            }
        });
    </script>
</body>
</html>