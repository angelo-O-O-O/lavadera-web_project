<?php
// admin/machines.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$adminName = $_SESSION['full_name'];
$errorMsg = '';

// Handle Form Actions (Add Machine, Edit Machine, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_machine') {
            $machineName = trim($_POST['machine_name']);
            $type = $_POST['type'];
            $status = $_POST['status'];

            $stmt = $conn->prepare("INSERT INTO washing_machines (machine_name, type, status) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $machineName, $type, $status);
            $stmt->execute();
            
            $_SESSION['success_msg'] = "New washing machine added successfully!";
            header("Location: machines.php");
            exit();
        }
        elseif ($action === 'edit_machine') {
            $machineId = intval($_POST['machine_id']);
            $machineName = trim($_POST['machine_name']);
            $type = $_POST['type'];
            $status = $_POST['status'];

            $stmt = $conn->prepare("UPDATE washing_machines SET machine_name = ?, type = ?, status = ? WHERE machine_id = ?");
            $stmt->bind_param("sssi", $machineName, $type, $status, $machineId);
            $stmt->execute();

            $_SESSION['success_msg'] = "Machine details updated successfully!";
            header("Location: machines.php");
            exit();
        }
        elseif ($action === 'delete_machine') {
            $machineId = intval($_POST['machine_id']);

            $stmt = $conn->prepare("DELETE FROM washing_machines WHERE machine_id = ?");
            $stmt->bind_param("i", $machineId);
            $stmt->execute();

            $_SESSION['success_msg'] = "Machine removed from the system.";
            header("Location: machines.php");
            exit();
        }
    } catch (Exception $e) {
        $errorMsg = "Database Error: " . $e->getMessage();
    }
}

// Fetch all washing machines securely without strict assignment inner/left join blocks
try {
    $machinesQuery = $conn->query("SELECT * FROM washing_machines ORDER BY machine_id DESC");
    $machines = $machinesQuery->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $machines = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Washing Machines | SmartSpin Admin</title>
    
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                <li><a href="machines.php" class="active"><i class="bi bi-cpu-fill"></i> <span>Washing Machines</span></a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart-fill"></i> <span>Reports & Sales</span></a></li>
                <li><a href="settings.php"><i class="bi bi-gear-fill"></i> <span>Settings</span></a></li>
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
            
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Washing Machine Management</h3>
                    <p class="text-muted small mb-0">Monitor real-time machine activity, status indicators, and operational conditions.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" style="background-color: var(--primary-navy);" data-bs-toggle="modal" data-bs-target="#addMachineModal">
                    <i class="bi bi-plus-circle"></i> Add New Machine
                </button>
            </div>

            <!-- Flash Messages -->
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

            <!-- Machines Grid View -->
            <div class="row g-4">
                <?php if(empty($machines)): ?>
                    <div class="col-12">
                        <div class="card text-center py-5">
                            <div class="bg-light text-muted rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                                <i class="bi bi-cpu"></i>
                            </div>
                            <h5 class="fw-bold text-dark">No washing machines registered</h5>
                            <p class="text-muted small mb-0">Get started by adding your shop's first washer or dryer unit.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($machines as $mach): 
                        $status = $mach['status'] ?? 'Available';
                        $statusClass = match($status) {
                            'Available' => 'badge-active',
                            'In Use' => 'bg-info bg-opacity-15 text-info',
                            'Maintenance' => 'bg-warning bg-opacity-15 text-warning',
                            default => 'badge-hidden'
                        };
                    ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100 d-flex flex-column justify-content-between mb-0">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge bg-light text-secondary border mb-2"><?= htmlspecialchars($mach['type']) ?></span>
                                        <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($mach['machine_name']) ?></h4>
                                    </div>
                                    <span class="badge <?= $statusClass ?> px-3 py-1.5 rounded-pill fw-semibold"><?= htmlspecialchars($status) ?></span>
                                </div>

                                <div class="bg-light p-3 rounded-4 mb-3">
                                    <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Operational Status</small>
                                    <span class="text-dark small fw-semibold">Unit is currently <?= strtolower(htmlspecialchars($status)) ?> and ready for workflow scheduling.</span>
                                </div>
                            </div>

                            <div class="d-flex gap-2 pt-3 border-top">
                                <button class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 shadow-none py-2" data-bs-toggle="modal" data-bs-target="#editMachineModal<?= $mach['machine_id'] ?>">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                                <form action="machines.php" method="POST" class="flex-grow-1" onsubmit="return confirm('Are you sure you want to delete this machine?');">
                                    <input type="hidden" name="action" value="delete_machine">
                                    <input type="hidden" name="machine_id" value="<?= $mach['machine_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill w-100 shadow-none py-2">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- EDIT MACHINE MODAL -->
                    <div class="modal fade" id="editMachineModal<?= $mach['machine_id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold">Edit Machine</h5>
                                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body pt-3 text-start">
                                    <form action="machines.php" method="POST">
                                        <input type="hidden" name="action" value="edit_machine">
                                        <input type="hidden" name="machine_id" value="<?= $mach['machine_id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">MACHINE NAME</label>
                                            <input type="text" class="form-control rounded-3 p-3 shadow-none" name="machine_name" value="<?= htmlspecialchars($mach['machine_name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">MACHINE TYPE</label>
                                            <select class="form-select rounded-3 p-3 shadow-none" name="type" required>
                                                <option value="Washer" <?= $mach['type'] === 'Washer' ? 'selected' : '' ?>>Washer</option>
                                                <option value="Dryer" <?= $mach['type'] === 'Dryer' ? 'selected' : '' ?>>Dryer</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">OPERATIONAL STATUS</label>
                                            <select class="form-select rounded-3 p-3 shadow-none" name="status" required>
                                                <option value="Available" <?= $status === 'Available' ? 'selected' : '' ?>>Available</option>
                                                <option value="In Use" <?= $status === 'In Use' ? 'selected' : '' ?>>In Use</option>
                                                <option value="Maintenance" <?= $status === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                                <option value="Offline" <?= $status === 'Offline' ? 'selected' : '' ?>>Offline</option>
                                                <option value="Out of Service" <?= $status === 'Out of Service' ? 'selected' : '' ?>>Out of Service</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-semibold" style="background-color: var(--primary-navy);">
                                            Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- ADD NEW MACHINE MODAL -->
    <div class="modal fade" id="addMachineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add New Washing Machine</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 text-start">
                    <form action="machines.php" method="POST">
                        <input type="hidden" name="action" value="add_machine">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">MACHINE NAME</label>
                            <input type="text" class="form-control rounded-3 p-3 shadow-none" name="machine_name" placeholder="e.g. Washer 01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">MACHINE TYPE</label>
                            <select class="form-select rounded-3 p-3 shadow-none" name="type" required>
                                <option value="Washer" selected>Washer</option>
                                <option value="Dryer">Dryer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">INITIAL STATUS</label>
                            <select class="form-select rounded-3 p-3 shadow-none" name="status" required>
                                <option value="Available" selected>Available</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Offline">Offline</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-semibold" style="background-color: var(--primary-navy);">
                            Save Machine
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarNav = document.getElementById('sidebarNav');
            
            if(sidebarToggle && sidebarNav) {
                sidebarToggle.addEventListener('click', function() {
                    sidebarNav.classList.toggle('show');
                });
            }
        });
    </script>
</body>
</html>