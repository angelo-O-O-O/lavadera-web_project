<?php
// admin/services.php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$adminName = $_SESSION['full_name'] ?? 'Admin';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_service') {
            $name = trim($_POST['service_name']);
            $desc = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $unit = $_POST['pricing_unit'];
            $estTime = intval($_POST['est_time_mins']);
            $icon = trim($_POST['icon'] ?? 'bi-basket');

            $stmt = $conn->prepare("INSERT INTO services (service_name, description, price, pricing_unit, est_time_mins, icon, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssdsis", $name, $desc, $price, $unit, $estTime, $icon);
            $stmt->execute();

            $_SESSION['success_msg'] = "New laundry service added successfully!";
            header("Location: services.php");
            exit();
        }
        elseif ($action === 'edit_service') {
            $serviceId = intval($_POST['service_id']);
            $name = trim($_POST['service_name']);
            $desc = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $unit = $_POST['pricing_unit'];
            $estTime = intval($_POST['est_time_mins']);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $stmt = $conn->prepare("UPDATE services SET service_name = ?, description = ?, price = ?, pricing_unit = ?, est_time_mins = ?, is_active = ? WHERE service_id = ?");
            $stmt->bind_param("ssdsiii", $name, $desc, $price, $unit, $estTime, $isActive, $serviceId);
            $stmt->execute();

            $_SESSION['success_msg'] = "Service updated successfully!";
            header("Location: services.php");
            exit();
        }
        elseif ($action === 'toggle_status') {
            $serviceId = intval($_POST['service_id']);
            $currentStatus = intval($_POST['current_status']);
            $newStatus = $currentStatus === 1 ? 0 : 1;

            $stmt = $conn->prepare("UPDATE services SET is_active = ? WHERE service_id = ?");
            $stmt->bind_param("ii", $newStatus, $serviceId);
            $stmt->execute();

            $_SESSION['success_msg'] = "Service availability status changed!";
            header("Location: services.php");
            exit();
        }
        elseif ($action === 'delete_service') {
            $serviceId = intval($_POST['service_id']);

            $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
            $stmt->bind_param("i", $serviceId);
            $stmt->execute();

            $_SESSION['success_msg'] = "Service deleted successfully.";
            header("Location: services.php");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        // 1451 = row is referenced by a foreign key (service has orders)
        if ($e->getCode() === 1451) {
            $errorMsg = "This service is linked to existing orders and can't be deleted. Hide it instead.";
        } else {
            $errorMsg = "Database Error: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $errorMsg = "Database Error: " . $e->getMessage();
    }
}

try {
    $servicesQuery = $conn->query("SELECT * FROM services ORDER BY service_id DESC");
    $services = $servicesQuery->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $services = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Services & Pricing | SmartSpin Admin</title>

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
                <li><a href="services.php" class="active"><i class="bi bi-tags-fill"></i> <span>Services & Pricing</span></a></li>
                <li><a href="machines.php"><i class="bi bi-cpu-fill"></i> <span>Washing Machines</span></a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart-fill"></i> <span>Reports & Sales</span></a></li>
                <li><a href="settings.php"><i class="bi bi-gear-fill"></i> <span>Settings</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="d-flex align-items-center gap-2 mb-3 admin-info">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px;">
                        <?= htmlspecialchars(substr($adminName, 0, 1)) ?>
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
                    <h3 class="fw-bold text-dark mb-1">Services & Pricing Management</h3>
                    <p class="text-muted small mb-0">Create, edit, price, and enable or disable laundry offerings for your shop.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" style="background-color: var(--primary-navy);" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-circle"></i> Add New Service
                </button>
            </div>

            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Services Card Container -->
            <div class="card p-0 overflow-hidden">
                <?php if (empty($services)): ?>
                    <!-- Empty State -->
                    <div class="text-center py-5 my-3">
                        <div class="bg-light text-muted rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                            <i class="bi bi-tags"></i>
                        </div>
                        <h5 class="fw-bold text-dark">No services found</h5>
                        <p class="text-muted small mb-0">Get started by creating your first laundry service package.</p>
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View -->
                    <div class="table-container desktop-table-container">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="service-name-col">Service Name</th>
                                    <th scope="col" class="hide-tablet">Description</th>
                                    <th scope="col">Price</th>
                                    <th scope="col" style="white-space: nowrap;">Pricing Unit</th>
                                    <th scope="col">Est. Time</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $serv):
                                    $isActive = intval($serv['is_active'] ?? 1) === 1;
                                    $estTime = $serv['est_time_mins'] ?? null;
                                ?>
                                <tr>
                                    <td class="service-name-col">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light p-2.5 rounded-3 text-primary flex-shrink-0">
                                                <i class="bi <?= htmlspecialchars($serv['icon'] ?? 'bi-basket') ?> fs-5"></i>
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block"><?= htmlspecialchars($serv['service_name']) ?></strong>
                                                <small class="text-muted d-block d-md-none"><?= htmlspecialchars($serv['description'] ?? '') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hide-tablet">
                                        <small class="text-muted"><?= htmlspecialchars($serv['description'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-dark">₱<?= number_format($serv['price'], 2) ?></strong>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <span class="badge bg-light text-secondary border text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $serv['pricing_unit'] ?? '')) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?= $estTime ? intval($estTime) . ' mins' : '-' ?></span>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge badge-active"><span class="badge-dot"></span> Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-hidden"><span class="badge-dot"></span> Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <!-- Edit Button -->
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1 shadow-none" data-bs-toggle="modal" data-bs-target="#editModal<?= $serv['service_id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>

                                            <!-- Hide / Show Toggle Button -->
                                            <form action="services.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="service_id" value="<?= $serv['service_id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= intval($serv['is_active'] ?? 1) ?>">
                                                <?php if ($isActive): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-circle p-0 d-inline-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Hide service">
                                                        <i class="bi bi-eye-slash small"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success rounded-circle p-0 d-inline-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Show service">
                                                        <i class="bi bi-eye small"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Delete Button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-circle p-0 d-inline-flex align-items-center justify-content-center shadow-none"
                                                    style="width: 32px; height: 32px;"
                                                    title="Delete service"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    data-service-id="<?= $serv['service_id'] ?>"
                                                    data-service-name="<?= htmlspecialchars($serv['service_name']) ?>">
                                                <i class="bi bi-trash small"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Stacked Card View -->
                    <div class="mobile-card-list p-3">
                        <?php foreach ($services as $serv):
                            $isActive = intval($serv['is_active'] ?? 1) === 1;
                            $estTime = $serv['est_time_mins'] ?? null;
                        ?>
                        <div class="mobile-service-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-light p-2 rounded-3 text-primary">
                                        <i class="bi <?= htmlspecialchars($serv['icon'] ?? 'bi-basket') ?>"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($serv['service_name']) ?></h6>
                                </div>
                                <?php if ($isActive): ?>
                                    <span class="badge badge-active"><span class="badge-dot"></span> Active</span>
                                <?php else: ?>
                                    <span class="badge badge-hidden"><span class="badge-dot"></span> Hidden</span>
                                <?php endif; ?>
                            </div>

                            <p class="text-muted small mb-3"><?= htmlspecialchars($serv['description'] ?? 'No description provided.') ?></p>

                            <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top small">
                                <span class="text-muted">Price: <strong class="text-dark">₱<?= number_format($serv['price'], 2) ?> / <?= htmlspecialchars(str_replace('_', ' ', $serv['pricing_unit'] ?? '')) ?></strong></span>
                                <span class="text-muted">Time: <strong class="text-dark"><?= $estTime ? intval($estTime) . ' mins' : '-' ?></strong></span>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary w-100 rounded-pill fw-semibold shadow-none" style="min-height: 44px;" data-bs-toggle="modal" data-bs-target="#editModal<?= $serv['service_id'] ?>">
                                    <i class="bi bi-pencil me-1"></i> Edit Service
                                </button>
                                <form action="services.php" method="POST" class="flex-shrink-0">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="service_id" value="<?= $serv['service_id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= intval($serv['is_active'] ?? 1) ?>">
                                    <button type="submit" class="btn btn-outline-secondary rounded-pill px-3 shadow-none" style="min-height: 44px;" title="<?= $isActive ? 'Hide service' : 'Show service' ?>">
                                        <i class="bi bi-<?= $isActive ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>
                                <button type="button"
                                        class="btn btn-outline-danger rounded-pill px-3 shadow-none flex-shrink-0"
                                        style="min-height: 44px;"
                                        title="Delete service"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        data-service-id="<?= $serv['service_id'] ?>"
                                        data-service-name="<?= htmlspecialchars($serv['service_name']) ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- EDIT SERVICE MODALS (one per service, kept outside the table for valid HTML) -->
    <?php foreach ($services as $serv):
        $isActive = intval($serv['is_active'] ?? 1) === 1;
        $estTime = $serv['est_time_mins'] ?? null;
    ?>
    <div class="modal fade" id="editModal<?= $serv['service_id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Service</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 text-start">
                    <form action="services.php" method="POST">
                        <input type="hidden" name="action" value="edit_service">
                        <input type="hidden" name="service_id" value="<?= $serv['service_id'] ?>">

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">SERVICE NAME</label>
                            <input type="text" class="form-control rounded-3 p-3 shadow-none" name="service_name" value="<?= htmlspecialchars($serv['service_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">DESCRIPTION</label>
                            <textarea class="form-control rounded-3 p-3 shadow-none" name="description" rows="2"><?= htmlspecialchars($serv['description'] ?? '') ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">PRICE (₱)</label>
                                <input type="number" step="0.01" class="form-control rounded-3 p-3 shadow-none" name="price" value="<?= htmlspecialchars($serv['price']) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">PRICING UNIT</label>
                                <select class="form-select rounded-3 p-3 shadow-none" name="pricing_unit" required>
                                    <option value="per_kg" <?= ($serv['pricing_unit'] ?? '') === 'per_kg' ? 'selected' : '' ?>>Per Kilogram</option>
                                    <option value="per_load" <?= ($serv['pricing_unit'] ?? '') === 'per_load' ? 'selected' : '' ?>>Per Load</option>
                                    <option value="fixed" <?= ($serv['pricing_unit'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Price</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">EST. TIME (MINS)</label>
                                <input type="number" class="form-control rounded-3 p-3 shadow-none" name="est_time_mins" value="<?= htmlspecialchars((string)($estTime ?? '')) ?>">
                            </div>
                            <div class="col-6 d-flex align-items-center pt-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="activeSwitch<?= $serv['service_id'] ?>" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold small" for="activeSwitch<?= $serv['service_id'] ?>">Active Status</label>
                                </div>
                            </div>
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

    <!-- ADD NEW SERVICE MODAL -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add New Laundry Service</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3 text-start">
                    <form action="services.php" method="POST">
                        <input type="hidden" name="action" value="add_service">

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">SERVICE NAME</label>
                            <input type="text" class="form-control rounded-3 p-3 shadow-none" name="service_name" placeholder="e.g. Wash, Dry and Fold" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">DESCRIPTION</label>
                            <textarea class="form-control rounded-3 p-3 shadow-none" name="description" rows="2" placeholder="Brief details about what is included..."></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">PRICE (₱)</label>
                                <input type="number" step="0.01" class="form-control rounded-3 p-3 shadow-none" name="price" placeholder="0.00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">PRICING UNIT</label>
                                <select class="form-select rounded-3 p-3 shadow-none" name="pricing_unit" required>
                                    <option value="per_kg">Per Kilogram</option>
                                    <option value="per_load">Per Load</option>
                                    <option value="fixed">Fixed Price</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">EST. TIME (MINS)</label>
                                <input type="number" class="form-control rounded-3 p-3 shadow-none" name="est_time_mins" placeholder="e.g. 60">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">ICON CLASS</label>
                                <input type="text" class="form-control rounded-3 p-3 shadow-none" name="icon" value="bi-basket" placeholder="bi-basket">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-semibold" style="background-color: var(--primary-navy);">
                            Save and Publish Service
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRM MODAL (shared by all services) -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 rounded-4 shadow-lg p-4 text-center">
                <div class="bg-danger-subtle text-danger rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.6rem;">
                    <i class="bi bi-trash"></i>
                </div>
                <h5 class="fw-bold mb-2">Delete service?</h5>
                <p class="text-muted small mb-4">
                    <strong id="deleteServiceName"></strong> will be permanently removed. This can't be undone.
                </p>
                <form action="services.php" method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="delete_service">
                    <input type="hidden" name="service_id" id="deleteServiceId">
                    <button type="button" class="btn btn-outline-secondary w-100 rounded-pill shadow-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger w-100 rounded-pill shadow-none">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Mobile sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarNav = document.getElementById('sidebarNav');

            if (sidebarToggle && sidebarNav) {
                sidebarToggle.addEventListener('click', function () {
                    sidebarNav.classList.toggle('show');
                });
            }

            // Fill the shared delete modal with the clicked service
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;
                    document.getElementById('deleteServiceId').value = btn.dataset.serviceId;
                    document.getElementById('deleteServiceName').textContent = btn.dataset.serviceName;
                });
            }
        });
    </script>
</body>
</html>