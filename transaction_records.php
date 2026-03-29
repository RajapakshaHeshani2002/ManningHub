<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}
include 'db.php';

// ── Handle Add Transaction ───────────────────────────────────────────────────
$toast = '';
if (isset($_POST['add_transaction'])) {
    $t_date   = $conn->real_escape_string($_POST['transaction_date']);
    $vid      = intval($_POST['vendor_id']);
    $vname    = $conn->real_escape_string(trim($_POST['vendor_name']));
    $product  = $conn->real_escape_string(trim($_POST['product_name']));
    $qty      = floatval($_POST['quantity_kg']);
    $price    = floatval($_POST['unit_price']);
    $total    = $qty * $price;
    $type     = in_array($_POST['transaction_type'], ['sale','purchase']) ? $_POST['transaction_type'] : 'sale';

    if ($qty > 0 && $price > 0 && !empty($product)) {
        $conn->query("INSERT INTO transactions
            (transaction_date, vendor_id, vendor_name, product_name, quantity_kg, unit_price, total_amount, transaction_type, recorded_by, created_at)
            VALUES ('$t_date', $vid, '$vname', '$product', $qty, $price, $total, '$type', 'Admin', NOW())");
        $toast = 'Transaction recorded successfully.';
    } else {
        $toast = 'error:Please fill in all required fields correctly.';
    }
}

// ── Handle Delete ────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    $conn->query("DELETE FROM transactions WHERE id=$did");
    header('Location: transaction_records.php?msg=deleted'); exit();
}

// ── Filters ──────────────────────────────────────────────────────────────────
$filter_date   = $_GET['date']   ?? '';
$filter_vendor = $_GET['vendor'] ?? '';
$filter_type   = $_GET['type']   ?? '';

$where = "WHERE 1=1";
if (!empty($filter_date))   $where .= " AND transaction_date='" . $conn->real_escape_string($filter_date) . "'";
if (!empty($filter_vendor)) $where .= " AND vendor_name LIKE '%" . $conn->real_escape_string($filter_vendor) . "%'";
if (!empty($filter_type) && in_array($filter_type, ['sale','purchase'])) $where .= " AND transaction_type='$filter_type'";

// ── Summary stats ────────────────────────────────────────────────────────────
$today = date('Y-m-d');
$today_sales    = $conn->query("SELECT COALESCE(SUM(total_amount),0) t FROM transactions WHERE transaction_date='$today' AND transaction_type='sale'")->fetch_assoc()['t'];
$today_purchase = $conn->query("SELECT COALESCE(SUM(total_amount),0) t FROM transactions WHERE transaction_date='$today' AND transaction_type='purchase'")->fetch_assoc()['t'];
$month_total    = $conn->query("SELECT COALESCE(SUM(total_amount),0) t FROM transactions WHERE MONTH(transaction_date)=MONTH(CURDATE()) AND YEAR(transaction_date)=YEAR(CURDATE())")->fetch_assoc()['t'];
$total_count    = $conn->query("SELECT COUNT(*) c FROM transactions")->fetch_assoc()['c'];

// ── Transactions list ─────────────────────────────────────────────────────────
$transactions = $conn->query("SELECT * FROM transactions $where ORDER BY transaction_date DESC, created_at DESC");

// ── Vendors for dropdown ─────────────────────────────────────────────────────
$vendors_res = $conn->query("SELECT id, full_name, stall_number FROM users WHERE role='vendor' AND status='approved' ORDER BY full_name");

// ── Products for dropdown ────────────────────────────────────────────────────
$products_res = $conn->query("SELECT veg_name FROM items ORDER BY veg_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Records | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#27ae60; --dark:#0d1b2a; --sidebar:#1a2535; --danger:#e74c3c; --warn:#e67e22; --info:#3498db; --bg:#f0f4f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); display:flex; }

        .sidebar { width:260px; min-height:100vh; background:var(--sidebar); color:white; position:fixed; overflow-y:auto; }
        .sidebar-header { padding:25px; text-align:center; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-header h2 { color:var(--primary); font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; }
        .sidebar-header p  { font-size:11px; color:#7f8c8d; margin-top:4px; }
        .menu-label { font-size:10px; text-transform:uppercase; color:#7f8c8d; padding:14px 18px 4px; letter-spacing:1px; display:block; }
        .sidebar-menu { list-style:none; padding:15px 10px; }
        .sidebar-menu li a { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.65); text-decoration:none; padding:10px 14px; border-radius:10px; font-size:13px; font-weight:500; transition:.2s; margin-bottom:3px; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background:var(--primary); color:white; }
        .sidebar-menu li a i { width:16px; text-align:center; }

        .main { margin-left:260px; width:calc(100% - 260px); padding:28px; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .page-head h1 { font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--dark); }
        .page-head p  { font-size:13px; color:#666; margin-top:4px; }

        /* Stats */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:24px; }
        .stat-card { background:white; border-radius:14px; padding:18px; border-left:5px solid #ddd; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .stat-card.green  { border-left-color:var(--primary); }
        .stat-card.blue   { border-left-color:var(--info); }
        .stat-card.orange { border-left-color:var(--warn); }
        .stat-card.purple { border-left-color:#8e44ad; }
        .stat-num { font-family:'Outfit',sans-serif; font-size:22px; font-weight:900; color:var(--dark); }
        .stat-lbl { font-size:12px; color:#888; margin-top:3px; }

        /* Section box */
        .section-box { background:white; border-radius:16px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.05); margin-bottom:22px; }
        .sec-title { font-family:'Outfit',sans-serif; font-size:16px; font-weight:700; color:var(--dark); margin-bottom:18px; display:flex; align-items:center; gap:8px; }
        .sec-title i { color:var(--primary); }

        /* Form */
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; }
        .form-group label { display:block; font-size:12px; font-weight:700; color:#555; margin-bottom:5px; text-transform:uppercase; letter-spacing:.4px; }
        .form-group input, .form-group select {
            width:100%; padding:10px 12px; border:1.5px solid #e0e0e0;
            border-radius:8px; font-size:13px; outline:none; transition:.2s;
        }
        .form-group input:focus, .form-group select:focus { border-color:var(--primary); }
        .total-preview { background:#eafaf1; color:#1e8449; padding:10px 14px; border-radius:8px; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; margin-top:14px; }
        .btn-add { background:var(--primary); color:white; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; transition:.2s; margin-top:14px; }
        .btn-add:hover { background:#219150; }

        /* Filter bar */
        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:flex-end; }
        .filter-bar .form-group { margin:0; }
        .filter-bar input, .filter-bar select { padding:8px 12px; font-size:13px; }
        .btn-filter { background:var(--dark); color:white; border:none; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; }
        .btn-clear  { background:#f0f0f0; color:#666; border:none; padding:9px 14px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }

        /* Table */
        .tx-table { width:100%; border-collapse:collapse; }
        .tx-table th { background:#f8f9fa; color:#636e72; font-size:11px; text-transform:uppercase; letter-spacing:.5px; padding:12px 14px; text-align:left; }
        .tx-table td { padding:13px 14px; border-bottom:1px solid #f1f2f6; font-size:13px; vertical-align:middle; }
        .tx-table tr:last-child td { border-bottom:none; }
        .tx-table tr:hover { background:#fafffe; }

        .badge-sale     { background:#eafaf1; color:#1e8449; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-purchase { background:#e8f4fd; color:#2471a3; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .btn-del { background:#fdecea; color:#c0392b; border:none; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; }
        .btn-del:hover { background:#c0392b; color:white; }

        /* Toast */
        .toast { padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .toast-ok  { background:#eafaf1; color:#1e8449; border-left:4px solid var(--primary); }
        .toast-err { background:#fdecea; color:#c0392b; border-left:4px solid var(--danger); }

        .empty-state { text-align:center; padding:50px; color:#ccc; }
        .empty-state i { font-size:44px; display:block; margin-bottom:12px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>M-HUB</h2>
        <p>ADMIN CONTROL CENTER</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Overview</a></li>
        <p class="menu-label">Himasha's Modules</p>
        <li><a href="vendor_management.php"><i class="fas fa-store"></i> Vendor Management</a></li>
        <li><a href="farmer_management.php"><i class="fas fa-tractor"></i> Farmer Management</a></li>
        <li><a href="hygiene_inspection.php"><i class="fas fa-shield-virus"></i> Hygiene Monitoring</a></li>
        <li><a href="quality_inspection.php"><i class="fas fa-star-half-alt"></i> Quality Inspection</a></li>
        <li><a href="stock_management.php"><i class="fas fa-boxes"></i> Stock Management</a></li>
        <p class="menu-label">Dilshara's Modules</p>
        <li><a href="products_list.php"><i class="fas fa-seedling"></i> Products & Prices</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Price Board</a></li>
        <li><a href="transaction_records.php" class="active"><i class="fas fa-receipt"></i> Transactions</a></li>
        <li><a href="reports_analytics.php"><i class="fas fa-chart-bar"></i> Reports & Analytics</a></li>
        <p class="menu-label">Yehani's Modules</p>
        <li><a href="price_history.php"><i class="fas fa-file-invoice-dollar"></i> Price History</a></li>
        <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
        <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <p class="menu-label">System</p>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="index.php"><i class="fas fa-eye"></i> View Website</a></li>
        <li><a href="logout.php" style="color:#e74c3c"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main">
    <div class="page-head">
        <div>
            <h1><i class="fas fa-receipt" style="color:var(--primary);margin-right:8px;"></i>Transaction Records</h1>
            <p>Record and manage daily market sales and purchases</p>
        </div>
        <div style="font-size:13px;color:#888;"><i class="fas fa-calendar"></i> <?php echo date('l, F j Y'); ?></div>
    </div>

    <!-- Toast -->
    <?php if (!empty($toast)): ?>
    <?php $is_err = str_starts_with($toast, 'error:'); ?>
    <div class="toast <?php echo $is_err ? 'toast-err' : 'toast-ok'; ?>">
        <i class="fas <?php echo $is_err ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
        <?php echo $is_err ? substr($toast, 6) : $toast; ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="toast toast-ok"><i class="fas fa-trash-alt"></i> Transaction deleted.</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card green">
            <div class="stat-num">Rs.<?php echo number_format($today_sales, 0); ?></div>
            <div class="stat-lbl">Today's Sales</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-num">Rs.<?php echo number_format($today_purchase, 0); ?></div>
            <div class="stat-lbl">Today's Purchases</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-num">Rs.<?php echo number_format($month_total, 0); ?></div>
            <div class="stat-lbl">This Month Total</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-num"><?php echo $total_count; ?></div>
            <div class="stat-lbl">All Transactions</div>
        </div>
    </div>

    <!-- Add Transaction Form -->
    <div class="section-box">
        <div class="sec-title"><i class="fas fa-plus-circle"></i> Record New Transaction</div>
        <form method="POST" id="txForm">
            <input type="hidden" name="add_transaction" value="1">
            <div class="form-grid">
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Vendor *</label>
                    <select name="vendor_id" id="vendorSelect" onchange="fillVendorName()" required>
                        <option value="">-- Select Vendor --</option>
                        <?php
                        $vendors_arr = [];
                        if ($vendors_res) while ($v = $vendors_res->fetch_assoc()):
                            $vendors_arr[$v['id']] = $v['full_name'];
                        ?>
                        <option value="<?php echo $v['id']; ?>" data-name="<?php echo htmlspecialchars($v['full_name']); ?>">
                            <?php echo htmlspecialchars($v['full_name']); ?> (<?php echo $v['stall_number']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="hidden" name="vendor_name" id="vendorName">
                </div>
                <div class="form-group">
                    <label>Product *</label>
                    <select name="product_name" required>
                        <option value="">-- Select Product --</option>
                        <?php if ($products_res) while ($p = $products_res->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($p['veg_name']); ?>">
                            <?php echo htmlspecialchars($p['veg_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity (kg) *</label>
                    <input type="number" name="quantity_kg" id="qty" min="0.1" step="0.1" placeholder="e.g. 50" oninput="calcTotal()" required>
                </div>
                <div class="form-group">
                    <label>Unit Price (Rs/kg) *</label>
                    <input type="number" name="unit_price" id="uprice" min="0.01" step="0.01" placeholder="e.g. 85.00" oninput="calcTotal()" required>
                </div>
                <div class="form-group">
                    <label>Type *</label>
                    <select name="transaction_type">
                        <option value="sale">💚 Sale</option>
                        <option value="purchase">🔵 Purchase</option>
                    </select>
                </div>
            </div>
            <div class="total-preview" id="totalPreview" style="display:none;">
                <i class="fas fa-calculator"></i>
                Total Amount: <strong id="totalAmt">Rs. 0.00</strong>
            </div>
            <button type="submit" class="btn-add">
                <i class="fas fa-plus"></i> Record Transaction
            </button>
        </form>
    </div>

    <!-- Filter -->
    <div class="section-box">
        <div class="sec-title"><i class="fas fa-filter"></i> Filter Transactions</div>
        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div class="form-group">
                <label>Vendor Name</label>
                <input type="text" name="vendor" value="<?php echo htmlspecialchars($filter_vendor); ?>" placeholder="Search vendor...">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All</option>
                    <option value="sale"     <?php echo $filter_type==='sale'     ?'selected':''; ?>>Sales</option>
                    <option value="purchase" <?php echo $filter_type==='purchase' ?'selected':''; ?>>Purchases</option>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
            <a href="transaction_records.php" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
        </form>

        <!-- Table -->
        <div style="overflow-x:auto;">
        <?php if ($transactions && $transactions->num_rows > 0): ?>
        <table class="tx-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Vendor</th>
                    <th>Product</th>
                    <th>Qty (kg)</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($tx = $transactions->fetch_assoc()): ?>
                <tr>
                    <td style="color:#aaa;font-size:12px;"><?php echo $i++; ?></td>
                    <td>
                        <strong><?php echo date('M d', strtotime($tx['transaction_date'])); ?></strong><br>
                        <span style="font-size:11px;color:#aaa;"><?php echo date('Y', strtotime($tx['transaction_date'])); ?></span>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($tx['vendor_name']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($tx['product_name']); ?></td>
                    <td><strong><?php echo number_format($tx['quantity_kg'], 1); ?></strong> kg</td>
                    <td>Rs.<?php echo number_format($tx['unit_price'], 2); ?></td>
                    <td><strong style="color:var(--dark);">Rs.<?php echo number_format($tx['total_amount'], 2); ?></strong></td>
                    <td>
                        <span class="badge-<?php echo $tx['transaction_type']; ?>">
                            <?php echo $tx['transaction_type'] === 'sale' ? '💚 Sale' : '🔵 Purchase'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="?delete=<?php echo $tx['id']; ?>"
                           class="btn-del"
                           onclick="return confirm('Delete this transaction?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>No transactions found<?php echo (!empty($filter_date)||!empty($filter_vendor)||!empty($filter_type)) ? ' for the selected filters.' : '. Add your first transaction above.'; ?></p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<script>
function fillVendorName() {
    var sel = document.getElementById('vendorSelect');
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('vendorName').value = opt.dataset.name || '';
}
function calcTotal() {
    var qty   = parseFloat(document.getElementById('qty').value)    || 0;
    var price = parseFloat(document.getElementById('uprice').value) || 0;
    var total = qty * price;
    var preview = document.getElementById('totalPreview');
    if (qty > 0 && price > 0) {
        document.getElementById('totalAmt').textContent = 'Rs. ' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        preview.style.display = 'flex';
    } else {
        preview.style.display = 'none';
    }
}
</script>
</body>
</html>
