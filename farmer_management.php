<?php

session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}

$toast = ''; $toast_type = 'toast-success';

// Handle status actions 
if (isset($_POST['action'], $_POST['farmer_id'])) {
    $fid    = intval($_POST['farmer_id']);
    $action = $conn->real_escape_string($_POST['action']);
    $map    = ['approve'=>'approved','reject'=>'rejected','suspend'=>'suspended','activate'=>'approved'];

    if (array_key_exists($action, $map)) {
        $new = $map[$action];
        $conn->query("UPDATE farmers SET status='$new' WHERE id=$fid");
        $conn->query("UPDATE users   SET status='$new'
                      WHERE phone=(SELECT phone FROM farmers WHERE id=$fid)
                      AND role='farmer' LIMIT 1");
        $toast = "Farmer " . $action . "d successfully.";
    }
}

// Category assignment 
if (isset($_POST['assign_category'])) {
    $fid = intval($_POST['farmer_id']);
    $cid = intval($_POST['category_id']);
    $conn->query("UPDATE farmers SET category_id=$cid WHERE id=$fid");
    $toast = 'Product category assigned successfully.';
}

// Filter 
$filter = $conn->real_escape_string($_GET['filter'] ?? 'all');
$search = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$where  = "WHERE 1=1";
if ($filter !== 'all') $where .= " AND f.status='$filter'";
if ($search !== '')    $where .= " AND (f.name LIKE '%$search%' OR f.phone LIKE '%$search%' OR f.location LIKE '%$search%')";

$farmers = $conn->query(
    "SELECT f.*, pc.category_name
     FROM farmers f
     LEFT JOIN product_categories pc ON f.category_id = pc.category_id
     $where ORDER BY f.created_at DESC"
);

$cnt = [];
foreach (['all','pending','approved','rejected','suspended'] as $s) {
    $q = ($s==='all') ? "SELECT COUNT(*) c FROM farmers"
                      : "SELECT COUNT(*) c FROM farmers WHERE status='$s'";
    $cnt[$s] = $conn->query($q)->fetch_assoc()['c'];
}

$all_cats = $conn->query("SELECT * FROM product_categories ORDER BY category_name");
$cat_list = [];
if ($all_cats) while ($c = $all_cats->fetch_assoc()) $cat_list[] = $c;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Management | ManningHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/Manninghub.css">
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
        <li><a href="farmer_management.php" class="active"><i class="fas fa-tractor"></i> Farmer Management</a></li>
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

<div class="main">
    <h1 class="page-title"><i class="fas fa-tractor" style="color:var(--primary)"></i> Farmer Management</h1>
    <p class="breadcrumb">Admin &rsaquo; Farmer Management</p>

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

    <div class="toolbar">
        <form method="GET" style="display:flex;gap:10px;flex:1;align-items:center;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <div class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, phone, location...">
                <i class="fas fa-search"></i>
            </div>
            <button type="submit" class="btn-search">Search</button>
            <?php if($search): ?>
                <a href="?filter=<?php echo $filter; ?>" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farmer Info</th>
                    <th>Contact</th>
                    <th>Farm Location</th>
                    <th>Crops</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($farmers && $farmers->num_rows > 0):
                $i = 1; while ($f = $farmers->fetch_assoc()): ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td>
                    <span class="vendor-name"><?php echo htmlspecialchars($f['name']); ?></span>
                    <span class="vendor-sub">NIC: <?php echo htmlspecialchars($f['nic'] ?? '—'); ?></span>
                </td>
                <td>
                    <div><?php echo htmlspecialchars($f['phone']); ?></div>
                    <div class="vendor-sub"><?php echo htmlspecialchars($f['email'] ?? '—'); ?></div>
                </td>
                <td><?php echo htmlspecialchars($f['location'] ?? '—'); ?></td>
                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?php echo htmlspecialchars($f['crop'] ?? '—'); ?>
                </td>
                <td>
                    <?php if ($f['category_name']): ?>
                        <span class="badge badge-cat"><?php echo htmlspecialchars($f['category_name']); ?></span>
                    <?php else: ?>
                        <span class="vendor-sub">Not assigned</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?php echo $f['status']; ?>"><?php echo ucfirst($f['status']); ?></span></td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;">
                        <button class="btn-action btn-assign" onclick="openCatModal(<?php echo $f['id']; ?>, '<?php echo addslashes($f['name']); ?>')">
                            <i class="fas fa-tag"></i> Category
                        </button>
                        <?php if ($f['status']==='pending'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="farmer_id" value="<?php echo $f['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button class="btn-action btn-approve"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Reject this farmer?')">
                                <input type="hidden" name="farmer_id" value="<?php echo $f['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn-action btn-reject"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        <?php elseif ($f['status']==='approved'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Suspend this farmer?')">
                                <input type="hidden" name="farmer_id" value="<?php echo $f['id']; ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button class="btn-action btn-suspend"><i class="fas fa-ban"></i> Suspend</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="farmer_id" value="<?php echo $f['id']; ?>">
                                <input type="hidden" name="action" value="activate">
                                <button class="btn-action btn-activate"><i class="fas fa-redo"></i> Activate</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8"><div class="empty-state"><i class="fas fa-tractor"></i>No farmers found.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Modal -->
<div class="modal-overlay" id="catModal">
    <div class="modal">
        <h3><i class="fas fa-tag" style="color:var(--purple)"></i> Assign Product Category</h3>
        <p id="catFarmerName" style="color:#636e72;margin-bottom:14px;font-size:14px;"></p>
        <form method="POST">
            <input type="hidden" name="assign_category" value="1">
            <input type="hidden" name="farmer_id" id="catFarmerId">
            <div class="form-group" style="margin-bottom:16px;">
                <label>Select Category</label>
                <select name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($cat_list as $c): ?>
                        <option value="<?php echo $c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-confirm"><i class="fas fa-save"></i> Assign</button>
            </div>
        </form>
    </div>
</div>

<script src="js/Manninghub.js"></script>
</body>
</html>
