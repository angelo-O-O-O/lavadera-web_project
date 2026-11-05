<?php
// admin/settings.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$adminId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    try {
        if ($formType === 'update_profile') {
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);

            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $fullName, $email, $phone, $address, $adminId);
            $stmt->execute();

            $_SESSION['full_name'] = $fullName;
            $_SESSION['success_msg'] = "Admin profile updated successfully!";
            header("Location: settings.php");
            exit();
        }
        elseif ($formType === 'change_password') {
            $currentPass = $_POST['current_password'];
            $newPass = $_POST['new_password'];
            $confirmPass = $_POST['confirm_password'];

            if ($newPass !== $confirmPass) {
                throw new Exception("New passwords do not match.");
            }

            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!password_verify($currentPass, $user['password_hash'])) {
                throw new Exception("Incorrect current password.");
            }

            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $updatePass = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $updatePass->bind_param("si", $newHash, $adminId);
            $updatePass->execute();

            $_SESSION['success_msg'] = "Password changed successfully!";
            header("Location: settings.php");
            exit();
        }
        elseif ($formType === 'update_shop_settings') {
            $shopName = trim($_POST['shop_name']);
            $contactEmail = trim($_POST['contact_email']);
            $operatingHours = trim($_POST['operating_hours']);
            $paymentMethods = trim($_POST['payment_methods']);

            $settings = [
                'shop_name' => $shopName,
                'contact_email' => $contactEmail,
                'operating_hours' => $operatingHours,
                'payment_methods' => $paymentMethods
            ];

            foreach ($settings as $key => $val) {
                $check = $conn->prepare("SELECT * FROM system_settings WHERE setting_key = ?");
                $check->bind_param("s", $key);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $up = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
                    $up->bind_param("ss", $val, $key);
                    $up->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
                    $ins->bind_param("ss", $key, $val);
                    $ins->execute();
                }
            }

            $_SESSION['success_msg'] = "Shop information and settings saved successfully!";
            header("Location: settings.php");
            exit();
        }
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// Fetch Admin Profile Data
$adminQuery = $conn->prepare("SELECT full_name, email, phone, address FROM users WHERE user_id = ?");
$adminQuery->bind_param("i", $adminId);
$adminQuery->execute();
$adminData = $adminQuery->get_result()->fetch_assoc();

// Define Admin Name safely to prevent undefined variable warnings
$adminName = $adminData['full_name'] ?? ($_SESSION['full_name'] ?? 'Administrator');

// Fetch System Settings Data
$sysSettings = [];
try {
    $settingsQuery = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($settingsQuery) {
        while ($row = $settingsQuery->fetch_assoc()) {
            $sysSettings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (mysqli_sql_exception $e) {
    $errorMsg = "System settings could not be loaded. Please ensure the system_settings table exists.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Settings | SmartSpin Admin</title>
    
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .password-field { position: relative; }
        .password-field .form-control { padding-right: 45px; }
        .password-toggle {
            position: absolute; top: 50%; right: 12px; transform: translateY(-50%);
            background: none; border: none; color: #718096; cursor: pointer; padding: 0;
            display: flex; align-items: center; justify-content: center; box-shadow: none !important;
        }
        .password-toggle:hover { color: var(--primary-navy); }
    </style>
</head>
<body>

    <!-- Mobile Topbar -->
    <div class="mobile-topbar">
        <button class="btn btn-outline-secondary border-0 shadow-none" id="sidebarToggle" aria-label="Toggle navigation">
            <i class="bi bi-list fs-4"></i>
        </button>
        <span class="fw-bold text-primary"><i class="bi bi-droplet-half me-1"></i> SmartSpin Admin</span>
        <div style="width: 38px;"></div>
    </div>

    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
        <nav class="sidebar" id="sidebarNav">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="bi bi-droplet-half fs-4 text-primary"></i> <span>SmartSpin Admin</span>
            </a>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span></a></li>
                <li><a href="orders.php"><i class="bi bi-basket-fill"></i> <span>Laundry Orders</span></a></li>
                <li><a href="customers.php"><i class="bi bi-people-fill"></i> <span>Customers</span></a></li>
                <li><a href="services.php"><i class="bi bi-tags-fill"></i> <span>Services & Pricing</span></a></li>
                <li><a href="machines.php"><i class="bi bi-cpu-fill"></i> <span>Washing Machines</span></a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart-fill"></i> <span>Reports & Sales</span></a></li>
                <li><a href="settings.php" class="active"><i class="bi bi-gear-fill"></i> <span>Settings</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="d-flex align-items-center gap-2 mb-3 admin-info">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px;">
                        <?= substr($adminName, 0, 1) ?>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-truncate fw-bold small text-dark"><?= htmlspecialchars($adminName) ?></h6>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Administrator</small>
                    </div>
                </div>
                <a href="../login.php" class="btn btn-outline-danger w-100 btn-sm rounded-pill d-flex align-items-center justify-content-center gap-1" style="min-height: 38px;">
                    <i class="bi bi-box-arrow-right"></i> <span>Sign out</span>
                </a>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h3 class="fw-bold text-dark mb-1">System & Admin Settings</h3>
                    <p class="text-muted small mb-0">Manage your administrative credentials, security preferences, and shop configurations.</p>
                </div>
            </div>

            <?php if(isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- 1. Admin Profile Settings -->
                <div class="col-12 col-lg-6">
                    <div class="card h-100 mb-0">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-gear text-primary me-2"></i> Edit Admin Profile</h5>
                        <form action="settings.php" method="POST">
                            <input type="hidden" name="form_type" value="update_profile">
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">FULL NAME</label>
                                <input type="text" class="form-control rounded-3 p-3 shadow-none" name="full_name" value="<?= htmlspecialchars($adminData['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                                <input type="email" class="form-control rounded-3 p-3 shadow-none" name="email" value="<?= htmlspecialchars($adminData['email'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">CONTACT NUMBER</label>
                                <input type="text" class="form-control rounded-3 p-3 shadow-none" name="phone" value="<?= htmlspecialchars($adminData['phone'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">ADDRESS</label>
                                <textarea class="form-control rounded-3 p-3 shadow-none" name="address" rows="2"><?= htmlspecialchars($adminData['address'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-semibold" style="background-color: var(--primary-navy);">
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 2. Change Password -->
                <div class="col-12 col-lg-6">
                    <div class="card h-100 mb-0">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock text-warning me-2"></i> Change Password</h5>
                        <form action="settings.php" method="POST" autocomplete="off">
                            <input type="hidden" name="form_type" value="change_password">
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold" for="current_password">CURRENT PASSWORD</label>
                                <div class="password-field">
                                    <input type="password" class="form-control rounded-3 p-3 shadow-none" id="current_password" name="current_password" placeholder="Enter current password" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle aria-controls="current_password" aria-label="Show password" aria-pressed="false">
                                        <i class="bi bi-eye password-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold" for="new_password">NEW PASSWORD</label>
                                <div class="password-field">
                                    <input type="password" class="form-control rounded-3 p-3 shadow-none" id="new_password" name="new_password" placeholder="Enter new password" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle aria-controls="new_password" aria-label="Show password" aria-pressed="false">
                                        <i class="bi bi-eye password-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold" for="confirm_password">CONFIRM NEW PASSWORD</label>
                                <div class="password-field">
                                    <input type="password" class="form-control rounded-3 p-3 shadow-none" id="confirm_password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle aria-controls="confirm_password" aria-label="Show password" aria-pressed="false">
                                        <i class="bi bi-eye password-icon" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 py-2.5 rounded-3 fw-semibold text-white mt-4">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 3. Shop Information & Operating Hours -->
                <div class="col-12">
                    <div class="card mb-0">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shop text-success me-2"></i> Manage Shop Information & Configurations</h5>
                        <form action="settings.php" method="POST">
                            <input type="hidden" name="form_type" value="update_shop_settings">
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">LAUNDRY SHOP NAME</label>
                                    <input type="text" class="form-control rounded-3 p-3 shadow-none" name="shop_name" value="<?= htmlspecialchars($sysSettings['shop_name'] ?? 'SmartSpin Laundry') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">CONTACT EMAIL</label>
                                    <input type="email" class="form-control rounded-3 p-3 shadow-none" name="contact_email" value="<?= htmlspecialchars($sysSettings['contact_email'] ?? 'support@smartspin.com') ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">OPERATING HOURS</label>
                                    <input type="text" class="form-control rounded-3 p-3 shadow-none" name="operating_hours" value="<?= htmlspecialchars($sysSettings['operating_hours'] ?? 'Mon - Sun: 8:00 AM - 8:00 PM') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">SUPPORTED PAYMENT METHODS</label>
                                    <input type="text" class="form-control rounded-3 p-3 shadow-none" name="payment_methods" value="<?= htmlspecialchars($sysSettings['payment_methods'] ?? 'Cash, GCash') ?>" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-success px-5 py-2.5 rounded-pill fw-semibold shadow-sm">
                                    Save Configurations
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>