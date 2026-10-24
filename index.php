<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f8fb">
    <meta name="description"
        content="Browse Lavadera Laundry Shop services and prices. Sign in to see available washing machines or schedule a service.">
    <title>Lavadera Laundry Shop | Plan your laundry visit</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <script src="assets/js/bootstrap.bundle.js" defer></script>
    <script src="assets/js/index.js" defer></script>
</head>

<body class="home-page">
    <?php require __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <section class="home-hero" aria-labelledby="hero-title">
            <div class="container home-hero-container">
                <div class="row align-items-center gy-5">
                    <div class="col-lg-6 home-hero-copy">
                        <span class="home-eyebrow"><span class="home-eyebrow-dot"></span> Laundry day, made
                            simpler</span>
                        <h1 id="hero-title">Know before you go.<br>
                            <!-- <span>Plan your visit.</span> -->
                        </h1>
                        <p class="home-hero-lead">Browse services and prices here. Sign in to see which machines are
                            available or choose a time for a service.</p>
                        <div class="d-flex flex-wrap align-items-center gap-3 home-hero-actions">
                            <a class="btn home-btn-primary" href="login.php">Sign in <i class="bi bi-arrow-up-right"
                                    aria-hidden="true"></i></a>
                            <a class="home-text-link" href="#services">Browse services <i class="bi bi-arrow-down-right"
                                    aria-hidden="true"></i></a>
                        </div>
                        <div class="home-hero-note">
                            <span class="home-note-icon"><i class="bi bi-stars" aria-hidden="true"></i></span>
                            <span>Sign in to check machines or schedule a service</span>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="home-visual" aria-label="Examples of machine availability customers can check">
                            <div class="home-process-card">
                                <div class="home-process-heading">
                                    <div>
                                        <span class="home-card-overline">WHAT YOU CAN CHECK</span>
                                        <h2>See what’s free.</h2>
                                    </div>
                                    <span class="home-card-icon"><i class="bi bi-droplet-half"
                                            aria-hidden="true"></i></span>
                                </div>
                                <div class="home-status-row">
                                    <span class="home-status-indicator status-available"></span>
                                    <span class="home-step-copy"><strong>Free to use</strong><small>Available
                                            now</small></span>
                                </div>
                                <div class="home-status-row">
                                    <span class="home-status-indicator status-in-use"></span>
                                    <span class="home-step-copy"><strong>In use</strong><small>A wash is in
                                            progress</small></span>
                                </div>
                                <div class="home-status-row">
                                    <span class="home-status-indicator status-maintenance"></span>
                                    <span class="home-step-copy"><strong>Not available</strong><small>Temporarily out of
                                            service</small></span>
                                </div>
                                <div class="home-process-footer"><i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                    Sign in to see available machines</div>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="home-scroll-cue" href="#services"><span>SCROLL TO EXPLORE</span><i class="bi bi-arrow-down"
                        aria-hidden="true"></i></a>
            </div>
        </section>

        <section class="home-services section-padding" id="services" aria-labelledby="services-title">
            <div class="container">
                <div class="home-section-heading text-center">
                    <span class="home-eyebrow">Lavadera laundry services</span>
                    <h2 id="services-title">Find the service you need.</h2>
                    <p>Browse services, prices, and optional extras. Sign in if you’d like to choose a service time.</p>
                </div>
                <div class="row g-4 home-service-grid" id="services-list" aria-live="polite">
                    <div class="col-12">
                        <p class="home-catalog-status">Loading services…</p>
                    </div>
                </div>
                <div class="home-addons-block">
                    <div class="home-addons-heading">
                        <span class="home-eyebrow">A little extra</span>
                        <h3>Optional extras</h3>
                    </div>
                    <div class="home-addons-list" id="service-addons-list" aria-live="polite">
                        <p class="home-catalog-status">Loading extras…</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-how section-padding" id="how-it-works" aria-labelledby="how-title">
            <div class="container">
                <div class="row align-items-end gy-3 home-how-heading">
                    <div class="col-lg-7">
                        <span class="home-eyebrow">Simple steps</span>
                        <h2 id="how-title">A simple way to<br>get started.</h2>
                    </div>
                    <div class="col-lg-5">
                        <p>Browse services here anytime. Sign in to see available machines, and choose a service time if
                            you want to schedule.</p>
                    </div>
                </div>
                <div class="row g-4 home-steps">
                    <div class="col-md-4">
                        <div class="home-step-card">
                            <span class="home-step-count">01</span>
                            <span class="home-step-line"></span>
                            <h3>Browse services</h3>
                            <p>Compare the available laundry services, prices, and extras on this page.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="home-step-card">
                            <span class="home-step-count">02</span>
                            <span class="home-step-line"></span>
                            <h3>Sign in to see machines</h3>
                            <p>Check which machines are free to use or already in use.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="home-step-card">
                            <span class="home-step-count">03</span>
                            <span class="home-step-line"></span>
                            <h3>Choose a time, if you like</h3>
                            <p>Want to schedule a service? Sign in and choose a time that works for you.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-cta-section" id="about" aria-labelledby="cta-title">
            <div class="container">
                <div class="home-cta-panel">
                    <div class="home-cta-content">
                        <span class="home-eyebrow">Ready when you are</span>
                        <h2 id="cta-title">Want to choose<br>a service time?</h2>
                        <p>Sign in when you’re ready to schedule. Browsing machines and services is up to you.</p>
                        <a class="btn home-btn-light" href="login.php">Sign in to schedule <i
                                class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>
</body>

</html>