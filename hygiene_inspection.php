<?php


// Admin: Record & Track Vendor Hygiene Inspections

session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}

$toast = ''; $toast_type = 'toast-success';

// Add inspection 
if (isset($_POST['add_inspection'])) {
    $vendor_id = intval($_POST['vendor_id']);
    $insp_date = $conn->real_escape_string($_POST['inspection_date']);
    $status    = $conn->real_escape_string($_POST['hygiene_status']);
    $remarks   = $conn->real_escape_string(trim($_POST['remarks']));
    $inspector = $conn->real_escape_string($_SESSION['name'] ?? 'Admin');

    if ($conn->query("INSERT INTO hygiene_inspection (vendor_id, inspection_date, hygiene_status, remarks, inspected_by)
                      VALUES ($vendor_id, '$insp_date', '$status', '$remarks', '$inspector')")) {
        $toast = 'Hygiene inspection record saved successfully.';
    } else {
        $toast = 'Error: ' . $conn->error; $toast_type = 'toast-error';
    }
}

// Delete 
if (isset($_POST['delete_id'])) {
    $conn->query("DELETE FROM hygiene_inspection WHERE inspection_id=" . intval($_POST['delete_id']));
    $toast = 'Inspection record deleted.';
}

// Filter 
$filter_status = $conn->real_escape_string($_GET['status'] ?? 'all');
$where = "WHERE 1=1";
if ($filter_status !== 'all') $where .= " AND hi.hygiene_status='$filter_status'";

$records = $conn->query(
    "SELECT hi.*, v.name AS vendor_name, v.stall_number
     FROM hygiene_inspection hi
     JOIN vendors v ON hi.vendor_id = v.id
     $where ORDER BY hi.inspection_date DESC"
);

$vendors = $conn->query("SELECT id, name, stall_number FROM vendors WHERE status='approved' ORDER BY name");

// Summary counts
$summary = [];
foreach (['Pass','Fail','Warning','Under Review'] as $s) {
    $summary[$s] = $conn->query("SELECT COUNT(*) c FROM hygiene_inspection WHERE hygiene_status='$s'")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hygiene Inspection | ManningHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/ManningHub.css">
</head>
<body>

<?php if ($toast): ?>
<div class="toast show <?php echo $toast_type; ?>" id="toast">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($toast); ?>
</div>
<?php endif; ?>

<div class="sidebar">
    <div class="sidebar-header"><h2>M-HUB</h2><p>ADMIN CONTROL CENTER</p></div>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <span class="menu-label">Himasha's Modules</span>
        <li><a href="vendor_management.php"><i class="fas fa-store"></i> Vendor Management</a></li>
        <li><a href="farmer_management.php"><i class="fas fa-tractor"></i> Farmer Management</a></li>
        <li><a href="hygiene_inspection.php" class="active"><i class="fas fa-shield-virus"></i> Hygiene Monitoring</a></li>
        <li><a href="quality_inspection.php"><i class="fas fa-star-half-alt"></i> Quality Inspection</a></li>
        <span class="menu-label">Yehani's Modules</span>
        <li><a href="products_list.php"><i class="fas fa-seedling"></i> Products List</a></li>
        <li><a href="price_history.php"><i class="fas fa-file-invoice-dollar"></i> Price History</a></li>
        <span class="menu-label">System</span>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="logout.php" style="color:#e74c3c"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main">
    <h1 class="page-title"><i class="fas fa-shield-virus" style="color:var(--primary)"></i> Hygiene & Quality Monitoring</h1>
    <p class="breadcrumb">Admin &rsaquo; Hygiene Inspection</p>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="sum-card sum-green"><i class="fas fa-check-circle" style="color:var(--primary)"></i><div><div class="sum-num"><?php echo $summary['Pass']; ?></div><div class="sum-lbl">Passed</div></div></div>
        <div class="sum-card sum-red"><i class="fas fa-times-circle" style="color:var(--danger)"></i><div><div class="sum-num"><?php echo $summary['Fail']; ?></div><div class="sum-lbl">Failed</div></div></div>
        <div class="sum-card sum-yellow"><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i><div><div class="sum-num"><?php echo $summary['Warning']; ?></div><div class="sum-lbl">Warnings</div></div></div>
        <div class="sum-card sum-blue"><i class="fas fa-search" style="color:var(--info)"></i><div><div class="sum-num"><?php echo $summary['Under Review']; ?></div><div class="sum-lbl">Under Review</div></div></div>
    </div>

    <!-- Add Inspection Form -->
    <div class="form-card">
        <h3><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Record New Hygiene Inspection</h3>
        <form method="POST">
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Select Vendor</label>
                    <select name="vendor_id" required>
                        <option value="">-- Select Vendor --</option>
                        <?php if ($vendors) while ($v = $vendors->fetch_assoc()): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo htmlspecialchars($v['name']); ?> (<?php echo htmlspecialchars($v['stall_number'] ?? '—'); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Inspection Date</label>
                    <input type="date" name="inspection_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Hygiene Status</label>
                    <select name="hygiene_status" required>
                        <option value="Pass">✅ Pass</option>
                        <option value="Warning">⚠️ Warning</option>
                        <option value="Fail">❌ Fail</option>
                        <option value="Under Review" selected>🔍 Under Review</option>
                    </select>
                </div>
                <div class="form-group col-span-3">
                    <label>Remarks / Notes</label>
                    <textarea name="remarks" placeholder="Enter inspection remarks, observations, or corrective actions required..."></textarea>
                </div>
            </div>
            <button type="submit" name="add_inspection" class="btn-primary" style="margin-top:8px;">
                <i class="fas fa-save"></i> Save Inspection Record
            </button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-list-alt"></i> Inspection Records</span>
            <div class="filter-bar">
                <?php
                $flinks = ['all'=>'All','Pass'=>'✅ Pass','Warning'=>'⚠️ Warning','Fail'=>'❌ Fail','Under Review'=>'🔍 Review'];
                foreach ($flinks as $k => $v):
                    $a = ($filter_status===$k) ? 'active' : '';
                ?>
                <a href="?status=<?php echo urlencode($k); ?>" class="<?php echo $a; ?>"><?php echo $v; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Vendor</th><th>Stall</th><th>Inspection Date</th>
                    <th>Hygiene Status</th><th>Remarks</th><th>Inspected By</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($records && $records->num_rows > 0):
                $i = 1; while ($r = $records->fetch_assoc()):
                    $bs = ['Pass'=>'badge-pass','Fail'=>'badge-fail','Warning'=>'badge-warning','Under Review'=>'badge-review'];
                    $badge = $bs[$r['hygiene_status']] ?? 'badge-review';
            ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><strong><?php echo htmlspecialchars($r['vendor_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($r['stall_number'] ?? '—'); ?></td>
                <td><?php echo date('M d, Y', strtotime($r['inspection_date'])); ?></td>
                <td><span class="badge <?php echo $badge; ?>"><?php echo $r['hygiene_status']; ?></span></td>
                <td class="vendor-sub" style="max-width:200px"><?php echo htmlspecialchars($r['remarks'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($r['inspected_by']); ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this record?')">
                        <input type="hidden" name="delete_id" value="<?php echo $r['inspection_id']; ?>">
                        <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8"><div class="empty-state"><i class="fas fa-clipboard-list fa-3x"></i>No inspection records found.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="js/Manninghub.js"></script>
</body>
</html>
