<?php

// vendor_management.php 
// Admin: Verify, Approve/Reject, Activate/Suspend Vendors


session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}

$toast = ''; $toast_type = 'toast-success';

// Handle status actions 
if (isset($_POST['action'], $_POST['vendor_id'])) {
    $vid    = intval($_POST['vendor_id']);
    $action = $conn->real_escape_string($_POST['action']);
    $map    = ['approve'=>'approved','reject'=>'rejected','suspend'=>'suspended','activate'=>'approved'];

    if (array_key_exists($action, $map)) {
        $new = $map[$action];
        $conn->query("UPDATE vendors SET status='$new' WHERE id=$vid");
        $conn->query("UPDATE users   SET status='$new'
                      WHERE phone=(SELECT phone FROM vendors WHERE id=$vid)
                      AND role='vendor' LIMIT 1");
        $toast = ucfirst($action) . 'd successfully.';

        // ── Auto-notification to vendor ──
        $vname_r = $conn->query("SELECT full_name FROM users WHERE id=(SELECT id FROM users WHERE stall_number=(SELECT stall_number FROM vendors WHERE id=$vid) AND role='vendor' LIMIT 1) LIMIT 1");
        if (!$vname_r || $vname_r->num_rows === 0)
            $vname_r = $conn->query("SELECT name FROM vendors WHERE id=$vid LIMIT 1");
        $notif_msgs = [
            'approve'  => ['success', 'Account Approved!',   'Your vendor account has been approved by admin. You can now log in to ManningHub and manage your stall.'],
            'reject'   => ['danger',  'Account Rejected',    'Unfortunately your vendor account application has been rejected. Please contact admin for more details.'],
            'suspend'  => ['danger',  'Account Suspended',   'Your vendor account has been temporarily suspended by admin. Please contact admin to resolve this issue.'],
            'activate' => ['success', 'Account Reactivated', 'Your vendor account has been reactivated. Welcome back! You can now log in to ManningHub.'],
        ];
        if (isset($notif_msgs[$action])) {
            [$ntype, $ntitle, $nmsg] = $notif_msgs[$action];
            $uid_r = $conn->query("SELECT id FROM users WHERE role='vendor' AND id IN (SELECT id FROM users WHERE stall_number=(SELECT stall_number FROM vendors WHERE id=$vid)) LIMIT 1");
            $notif_uid = ($uid_r && $uid_r->num_rows > 0) ? $uid_r->fetch_assoc()['id'] : 'NULL';
            $conn->query("INSERT INTO notifications (user_id, role, title, type, message, is_read, created_at) VALUES ($notif_uid, 'vendor', '$ntitle', '$ntype', '$nmsg', 0, NOW())");
        }
    }
}

// Filter 
$filter = $conn->real_escape_string($_GET['filter'] ?? 'all');
$search = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$where  = "WHERE 1=1";
if ($filter !== 'all') $where .= " AND status='$filter'";
if ($search !== '')    $where .= " AND (name LIKE '%$search%' OR phone LIKE '%$search%' OR vendor_reg_id LIKE '%$search%')";

$vendors = $conn->query("SELECT * FROM vendors $where ORDER BY created_at DESC");

// Counts 
$cnt = [];
foreach (['all','pending','approved','rejected','suspended'] as $s) {
    $q = ($s==='all') ? "SELECT COUNT(*) c FROM vendors"
                      : "SELECT COUNT(*) c FROM vendors WHERE status='$s'";
    $cnt[$s] = $conn->query($q)->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management | ManningHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/ManningHub.css">
</head>
<body>

<!-- Toast -->
<?php if ($toast): ?>
<div class="toast show <?php echo $toast_type; ?>" id="toast">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($toast); ?>
</div>
<?php endif; ?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header"><h2>M-HUB</h2><p>ADMIN CONTROL CENTER</p></div>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <span class="menu-label">Himasha's Modules</span>
        <li><a href="vendor_management.php" class="active"><i class="fas fa-store"></i> Vendor Management</a></li>
        <li><a href="farmer_management.php"><i class="fas fa-tractor"></i> Farmer Management</a></li>
        <li><a href="hygiene_inspection.php"><i class="fas fa-shield-virus"></i> Hygiene Monitoring</a></li>
        <li><a href="quality_inspection.php"><i class="fas fa-star-half-alt"></i> Quality Inspection</a></li>
        <span class="menu-label">Yehani's Modules</span>
        <li><a href="products_list.php"><i class="fas fa-seedling"></i> Products List</a></li>
        <li><a href="price_history.php"><i class="fas fa-file-invoice-dollar"></i> Price History</a></li>
        <span class="menu-label">System</span>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="logout.php" style="color:#e74c3c"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Main -->
<div class="main">
    <h1 class="page-title"><i class="fas fa-store" style="color:var(--primary)"></i> Vendor Management</h1>
    <p class="breadcrumb">Admin &rsaquo; Vendor Management</p>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <?php
        $tabs  = ['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','suspended'=>'Suspended'];
        $icons = ['all'=>'fa-list','pending'=>'fa-clock','approved'=>'fa-check-circle','rejected'=>'fa-times-circle','suspended'=>'fa-ban'];
        foreach ($tabs as $k => $v):
            $active = ($filter===$k) ? 'active-tab' : '';
        ?>
        <a href="?filter=<?php echo $k; ?>" class="tab-btn <?php echo $k.' '.$active; ?>">
            <i class="fas <?php echo $icons[$k]; ?>"></i> <?php echo $v; ?>
            <span class="tab-badge"><?php echo $cnt[$k]; ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <form method="GET" style="display:flex;gap:10px;flex:1;align-items:center;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <div class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, phone, ID...">
                <i class="fas fa-search"></i>
            </div>
            <button type="submit" class="btn-search">Search</button>
            <?php if($search): ?>
                <a href="?filter=<?php echo $filter; ?>" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vendor Info</th>
                    <th>Contact</th>
                    <th>Stall No.</th>
                    <th>Permit ID</th>
                    <th>Reg. Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($vendors && $vendors->num_rows > 0):
                $i = 1; while ($v = $vendors->fetch_assoc()): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td>
                    <span class="vendor-name"><?php echo htmlspecialchars($v['name']); ?></span>
                    <span class="vendor-sub"><?php echo htmlspecialchars($v['vendor_reg_id'] ?? '—'); ?></span>
                </td>
                <td>
                    <div><?php echo htmlspecialchars($v['phone']); ?></div>
                    <div class="vendor-sub"><?php echo htmlspecialchars($v['email'] ?? '—'); ?></div>
                </td>
                <td><?php echo htmlspecialchars($v['stall_number'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($v['permit_number'] ?? '—'); ?></td>
                <td class="vendor-sub"><?php echo date('M d, Y', strtotime($v['created_at'])); ?></td>
                <td><span class="badge badge-<?php echo $v['status']; ?>"><?php echo ucfirst($v['status']); ?></span></td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;">
                        <button class="btn-action btn-view" onclick="openModal(<?php echo htmlspecialchars(json_encode($v)); ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($v['status']==='pending'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button class="btn-action btn-approve"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Reject this vendor?')">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn-action btn-reject"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        <?php elseif ($v['status']==='approved'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Suspend this vendor?')">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button class="btn-action btn-suspend"><i class="fas fa-ban"></i> Suspend</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="action" value="activate">
                                <button class="btn-action btn-activate"><i class="fas fa-redo"></i> Activate</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8"><div class="empty-state"><i class="fas fa-store-slash"></i>No vendors found.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h3><i class="fas fa-store" style="color:var(--primary)"></i> Vendor Details</h3>
        <div class="modal-info" id="modalContent"></div>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script src="js/Manninghub.js"></script>
</body>
</html>
