<?php
session_start();

require __DIR__ . '/config/dbcon.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$email = '';
$flashMessage = $_SESSION['auth_flash'] ?? '';
unset($_SESSION['auth_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = $_POST['email'] ?? '';
    $email = is_string($emailInput) ? trim($emailInput) : '';
    $submittedToken = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errorMessage = 'Your session expired. Please submit the form again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password)) {
        $errorMessage = 'Enter a valid email address and password.';
    } else {
        try {
            $statement = $conn->prepare(
                'SELECT user_id, full_name, email, password_hash, role, is_active
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );
            $statement->bind_param('s', $email);
            $statement->execute();
            $statement->bind_result($userId, $fullName, $accountEmail, $passwordHash, $role, $isActive);
            $userFound = $statement->fetch();
            $statement->close();

            if (!$userFound || (int) $isActive !== 1 || !password_verify($password, $passwordHash)) {
                $errorMessage = 'The email address or password is incorrect.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $userId;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $accountEmail;
                $_SESSION['role'] = $role;
                header('Location: pages/home.php');
                exit;
            }
        } catch (mysqli_sql_exception $exception) {
            error_log('Login query failed: ' . $exception->getMessage());
            $errorMessage = 'We could not sign you in right now. Please try again later.';
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
    <title>Sign in | Lavadera Laundry Shop</title>
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
                        <!-- <span class="auth-eyebrow">It's good to see you</span> -->
                        <h2 id="form-title" class="h2 auth-title mb-2">Welcome back</h2>
                        <p class="auth-description mb-0">Sign in to manage your laundry, your way.</p>
                    </header>

                    <?php if ($flashMessage !== ''): ?>
                        <div class="alert alert-success" role="status"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if ($errorMessage !== ''): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="post" action="login.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <div class="password-field">
                                <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                                <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">
                                    <i class="bi bi-eye password-icon" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Sign in <span aria-hidden="true">→</span></button>
                    </form>

                    <p class="mt-4 mb-0 text-center">New to Lavadera? <a href="signup.php">Create an account</a></p>
                </div>
                <p class="auth-form-footer">Lavadera Laundry Shop <span aria-hidden="true">·</span> Clean &amp; fresh</p>
            </section>
        </div>
    </main>
</body>
</html>