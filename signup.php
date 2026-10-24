<?php
session_start();

require __DIR__ . '/config/dbcon.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullNameInput = $_POST['full_name'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $phoneInput = $_POST['phone'] ?? '';
    $fullName = is_string($fullNameInput) ? trim($fullNameInput) : '';
    $email = is_string($emailInput) ? trim($emailInput) : '';
    $phone = is_string($phoneInput) ? trim($phoneInput) : '';
    $fullNameLength = preg_match_all('/./us', $fullName);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $submittedToken = $_POST['csrf_token'] ?? '';
    $acceptedTerms = isset($_POST['terms']);

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errorMessage = 'Your session expired. Please submit the form again.';
    } elseif (
        $fullName === ''
        || $fullNameLength === false
        || $fullNameLength > 150
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 190
        || ($phone !== '' && strlen($phone) > 11)
        || !is_string($password)
        || strlen($password) < 8
        || strlen($password) > 72
        || strpos($password, "\0") !== false
        || !is_string($confirmPassword)
    ) {
        $errorMessage = 'Check your details. Name, email, and phone must fit the allowed limits, and passwords must be 8 to 72 bytes.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'The passwords do not match.';
    } elseif (!$acceptedTerms) {
        $errorMessage = 'You must agree to the terms of service and privacy policy.';
    } else {
        try {
            $checkStatement = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            $checkStatement->bind_param('s', $email);
            $checkStatement->execute();
            $checkStatement->bind_result($existingUserId);
            $emailExists = $checkStatement->fetch();
            $checkStatement->close();

            if ($emailExists) {
                $errorMessage = 'An account with that email address already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $storedPhone = $phone === '' ? null : $phone;
                $insertStatement = $conn->prepare(
                    'INSERT INTO users (full_name, email, phone, password_hash)
                     VALUES (?, ?, ?, ?)'
                );
                $insertStatement->bind_param('ssss', $fullName, $email, $storedPhone, $passwordHash);
                $insertStatement->execute();
                $insertStatement->close();

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $conn->insert_id;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer';
                header('Location: pages/home.php');
                exit;
            }
        } catch (mysqli_sql_exception $exception) {
            error_log('Signup query failed: ' . $exception->getMessage());
            $errorMessage = 'We could not create your account right now. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f8fb">
    <title>Create an account | Lavadera Laundry Shop</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <script src="assets/js/auth.js?v=<?= filemtime(__DIR__ . '/assets/js/auth.js') ?>" defer></script>
</head>
<body class="auth-page">
    <main class="container-fluid auth-page-shell">
        <div class="row g-0 auth-layout">
            <aside class="col-12 col-md-5 auth-brand-panel" aria-label="Lavadera Laundry Shop">
                <a class="auth-brand-link" href="index.php" aria-label="Lavadera Laundry Shop home">
                    <img class="auth-brand-logo" src="img/logo.jpg" alt="Lavadera Laundry Shop">
                </a>
                <div class="auth-brand-copy">
                    <span class="auth-eyebrow">A fresh start, every time</span>
                    <h1 class="auth-brand-title">Laundry day,<br><em>made lighter.</em></h1>
                    <p class="auth-brand-description">Fresh clothes and a little more time for the things you love.</p>
                </div>
                <div class="auth-brand-note">
                    <span class="auth-brand-sparkle" aria-hidden="true">✦</span>
                    <span>Clean clothes. Clear mind.</span>
                </div>
            </aside>

            <section class="col-12 col-md-7 auth-form-panel" aria-labelledby="form-title">
                <div class="auth-form-wrap">
                    <header class="form-heading mb-4">
                        <span class="auth-eyebrow">Join the fresh side</span>
                        <h2 id="form-title" class="h2 auth-title mb-2">Create your account</h2>
                        <p class="auth-description mb-0">Create an account and make laundry day a little easier.</p>
                    </header>

                    <?php if ($errorMessage !== ''): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="post" action="signup.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label" for="full-name">Full name</label>
                            <input class="form-control" id="full-name" name="full_name" type="text" autocomplete="name" maxlength="150" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control" id="email" name="email" type="email" autocomplete="email" maxlength="190" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone number <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="phone" name="phone" type="tel" autocomplete="tel" maxlength="11" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <div class="password-field">
                                <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                                <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">
                                    <i class="bi bi-eye password-icon" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="confirm-password">Confirm password</label>
                            <div class="password-field">
                                <input class="form-control" id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                                <button class="password-toggle" type="button" data-password-toggle aria-controls="confirm-password" aria-label="Show confirm password" aria-pressed="false">
                                    <i class="bi bi-eye password-icon" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" id="terms" name="terms" type="checkbox" required>
                            <label class="form-check-label" for="terms">I agree to the terms of service and privacy policy.</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Create account <span aria-hidden="true">→</span></button>
                    </form>

                    <p class="mt-4 mb-0 text-center">Already have an account? <a href="login.php">Sign in</a></p>
                </div>
                <p class="auth-form-footer">Lavadera Laundry Shop <span aria-hidden="true">·</span> Clean &amp; fresh</p>
            </section>
        </div>
    </main>
</body>
</html>