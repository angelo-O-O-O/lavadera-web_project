<?php
// pages/profile.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$userId = $_SESSION['user_id'];
$successMsg = "";
$errorMsg = "";

// Kunin ang kasalukuyang detalye ng user
$userStmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$phoneValue = $user['phone'] ?? '';
$fullNameValue = $user['full_name'] ?? '';
$emailValue = $user['email'] ?? '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');

    $fullNameValue = $fullName;
    $emailValue = $email;
    $phoneValue = $phone;

    if (!empty($fullName) && !empty($email)) {
        if (!empty($phone)) {
            $phoneClean = preg_replace('/[^0-9]/', '', $phone);
            
            if (strlen($phoneClean) !== 11 || strpos($phoneClean, '09') !== 0) {
                $errorMsg = "Invalid Number!";
            } else {
                $phone = $phoneClean;
            }
        }

        if (empty($errorMsg)) {
            $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
            $updateStmt->bind_param("sssi", $fullName, $email, $phone, $userId);
            
            if ($updateStmt->execute()) {
                $_SESSION['full_name'] = $fullName; 
                $successMsg = "Profile updated successfully!";
                $phoneValue = $phone;
            } else {
                $errorMsg = "Failed to update profile. Please try again.";
            }
        }
    } else {
        $errorMsg = "Name and email fields cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile Settings | Lavadera</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #111827; }
        .navbar-custom { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 32px; }
        .nav-link { color: #475569; font-weight: 500; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
        .nav-link:hover, .nav-link.active { color: #1e4f8f; background: #f1f5f9; font-weight: 600; }
        .content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 32px; }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="navbar-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary text-white fw-bold rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">L</div>
            <span class="fw-bold fs-5 text-dark">Lavadera</span>
        </div>
        <nav class="d-flex align-items-center gap-3">
            <a href="home.php" class="nav-link">Dashboard</a>
            <a href="orders.php" class="nav-link">My Orders</a>
            <a href="profile.php" class="nav-link active">Profile Settings</a>
            <a href="../logout.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 ms-2 fw-semibold">Sign out</a>
        </nav>
    </header>

    <main class="container py-4" style="max-width: 800px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Profile Settings</h3>
                <p class="text-muted small mb-0">Manage your account information and preferences.</p>
            </div>
            <a href="home.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold shadow-none">← Back to Dashboard</a>
        </div>

        <!-- Success Message Banner -->
        <?php if (!empty($successMsg)): ?>
            <div id="alertMessage" class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                <?= htmlspecialchars($successMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Error Message Banner -->
        <?php if (!empty($errorMsg)): ?>
            <div id="alertMessage" class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                <?= htmlspecialchars($errorMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <form id="profileForm" method="POST" action="profile.php">
                <div class="mb-3">
                    <label for="full_name" class="form-label fw-semibold small text-muted">Full Name</label>
                    <input type="text" class="form-control shadow-none" id="full_name" name="full_name" value="<?= htmlspecialchars($fullNameValue) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-muted">Email Address</label>
                    <input type="email" class="form-control shadow-none" id="email" name="email" value="<?= htmlspecialchars($emailValue) ?>" required>
                </div>
                <div class="mb-4">
                    <label for="phone" class="form-label fw-semibold small text-muted">Phone Number</label>
                    <input type="text" class="form-control shadow-none" id="phone" name="phone" 
                           value="<?= htmlspecialchars($phoneValue) ?>" 
                           placeholder="09123456789" 
                           maxlength="11" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    <small class="text-muted" style="font-size: 0.75rem;"></small>
                </div>
                <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill shadow-none" style="background-color: #1e4f8f; border-color: #1e4f8f;">Save Changes</button>
            </form>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Automatic na mawawala ang alert banner pagkalipas ng 3 segundo (3000 milliseconds)
        setTimeout(function() {
            const alertEl = document.getElementById('alertMessage');
            if (alertEl) {
                // Gagamitin ang Bootstrap alert close method para mawala nang maayos
                let bsAlert = new bootstrap.Alert(alertEl);
                bsAlert.close();
            }
        }, 3000);
    </script>
</body>
</html>