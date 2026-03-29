<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}
include 'db.php';

// ── Handle stock update ──────────────────────────────────────
if (isset($_POST['update_stock'])) {
    $sid      = intval($_POST['stock_id']);
    $qty      = intval($_POST['quantity']);
    $min_qty  = intval($_POST['min_quantity']);
    $max_qty  = intval($_POST['max_quantity']);
    $received = $conn->real_escape_string($_POST['received_at']);
    $conn->query("UPDATE stock SET quantity='$qty', min_quantity='$min_qty', max_quantity='$max_qty', received_at='$received' WHERE id=$sid");
    header("Location: stock_management.php?msg=updated"); exit();
}

// ── Handle waste log ─────────────────────────────────────────
if (isset($_POST['log_waste'])) {
    $sid      = intval($_POST['stock_id']);
    $waste_kg = intval($_POST['waste_kg']);
    $reason   = $conn->real_escape_string($_POST['reason']);
    $name     = $conn->real_escape_string($_POST['product_name']);
    // Deduct from stock
    $conn->query("UPDATE stock SET quantity = GREATEST(0, quantity - $waste_kg), waste_kg = waste_kg + $waste_kg WHERE id=$sid");
    // Log it
    $conn->query("INSERT INTO waste_log (stock_id, product_name, waste_kg, reason, logged_by) VALUES ($sid, '$name', $waste_kg, '$reason', 'Admin')");
    header("Location: stock_management.php?msg=waste_logged"); exit();
}

// ── Load stock data ──────────────────────────────────────────
$stock_res = $conn->query("
    SELECT s.*, i.veg_image,
           DATEDIFF(CURDATE(), s.received_at) AS days_old
    FROM stock s
    LEFT JOIN items i ON s.item_id = i.id
    ORDER BY s.product_name ASC
    LIMIT 26
");

// ── Summary stats ────────────────────────────────────────────
$total_r    = $conn->query("SELECT COUNT(*) c FROM stock WHERE id<=26");
$total      = $total_r ? $total_r->fetch_assoc()['c'] : 0;
$low_r      = $conn->query("SELECT COUNT(*) c FROM stock WHERE quantity < min_quantity AND id<=26");
$low_count  = $low_r ? $low_r->fetch_assoc()['c'] : 0;
$over_r     = $conn->query("SELECT COUNT(*) c FROM stock WHERE quantity > max_quantity AND id<=26");
$over_count = $over_r ? $over_r->fetch_assoc()['c'] : 0;
$age_r      = $conn->query("SELECT COUNT(*) c FROM stock WHERE DATEDIFF(CURDATE(), received_at) >= 2 AND id<=26");
$age_count  = $age_r ? $age_r->fetch_assoc()['c'] : 0;
$waste_r    = $conn->query("SELECT SUM(waste_kg) t FROM waste_log WHERE MONTH(logged_at)=MONTH(CURDATE())");
$waste_total= $waste_r ? ($waste_r->fetch_assoc()['t'] ?? 0) : 0;

// ── Recent waste log ─────────────────────────────────────────
$waste_log = $conn->query("SELECT * FROM waste_log ORDER BY logged_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #27ae60; --primary2: #2ecc71;
            --dark: #0d1b2a;    --sidebar: #1a2535;
            --warn: #e67e22;    --danger: #e74c3c;
            --info: #3498db;    --purple: #8e44ad;
            --bg: #f0f4f0;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); display:flex; }

        /* ── SIDEBAR ── */
        .sidebar { width:260px; min-height:100vh; background:var(--sidebar); color:white; position:fixed; top:0; left:0; display:flex; flex-direction:column; }
        .sidebar-logo { padding:28px 20px 20px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-logo h2 { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--primary2); letter-spacing:1px; }
        .sidebar-logo p { font-size:11px; color:rgba(255,255,255,.4); margin-top:3px; }
        .sidebar-menu { list-style:none; padding:16px 10px; flex:1; }
        .sidebar-menu li a {
            display:flex; align-items:center; gap:12px;
            color:rgba(255,255,255,.65); text-decoration:none;
            padding:11px 14px; border-radius:10px; font-size:13px;
            font-weight:500; transition:.2s; margin-bottom:3px;
        }
        .sidebar-menu li a:hover { background:rgba(255,255,255,.08); color:white; }
        .sidebar-menu li a.active { background:var(--primary); color:white; }
        .sidebar-menu li a i { width:18px; text-align:center; font-size:14px; }

        /* ── MAIN ── */
        .main { margin-left:260px; width:calc(100% - 260px); padding:28px; }

        /* ── PAGE HEADER ── */
        .page-head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .page-head h1 { font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--dark); }
        .page-head p { font-size:13px; color:#666; margin-top:4px; }

        /* ── ALERT BANNER ── */
        .alert-banner { padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#eafaf1; color:#1e8449; border-left:4px solid var(--primary); }
        .alert-waste   { background:#fef9ec; color:#856404; border-left:4px solid var(--warn); }

        /* ── STAT CARDS ── */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:24px; }
        .stat-card { background:white; border-radius:14px; padding:18px 16px; border-left:5px solid #ddd; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .stat-card.green  { border-left-color:var(--primary); }
        .stat-card.red    { border-left-color:var(--danger); }
        .stat-card.orange { border-left-color:var(--warn); }
        .stat-card.amber  { border-left-color:#f39c12; }
        .stat-card.purple { border-left-color:var(--purple); }
        .stat-num { font-family:'Outfit',sans-serif; font-size:28px; font-weight:900; color:var(--dark); }
        .stat-lbl { font-size:12px; color:#888; margin-top:4px; }
        .stat-icon { font-size:22px; margin-bottom:8px; }

        /* ── SECTION BOX ── */
        .section-box { background:white; border-radius:16px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.05); margin-bottom:24px; }
        .sec-title { font-family:'Outfit',sans-serif; font-size:16px; font-weight:700; color:var(--dark); margin-bottom:18px; display:flex; align-items:center; gap:8px; }
        .sec-title i { color:var(--primary); }

        /* ── STOCK TABLE ── */
        .stock-table { width:100%; border-collapse:collapse; }
        .stock-table th { background:#f8f9fa; color:#636e72; font-size:11px; text-transform:uppercase; letter-spacing:.5px; padding:12px 14px; text-align:left; }
        .stock-table td { padding:12px 14px; border-bottom:1px solid #f1f2f6; vertical-align:middle; font-size:13px; }
        .stock-table tr:last-child td { border-bottom:none; }
        .stock-table tr:hover { background:#fafffe; }

        .prod-img { width:40px; height:40px; border-radius:8px; object-fit:cover; }
        .prod-name { font-weight:700; color:var(--dark); font-size:14px; }

        /* Status badges */
        .status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .s-ok     { background:#eafaf1; color:#1e8449; }
        .s-low    { background:#fdecea; color:#c0392b; }
        .s-over   { background:#fff3e0; color:#e65100; }
        .s-age    { background:#fef9ec; color:#856404; }
        .s-critical { background:#fdecea; color:#c0392b; animation:blink .8s step-end infinite; }
        @keyframes blink { 50%{opacity:.5} }

        /* Progress bar */
        .stock-bar { width:100%; height:8px; background:#f0f0f0; border-radius:4px; overflow:hidden; }
        .stock-fill { height:100%; border-radius:4px; transition:.3s; }
        .fill-ok     { background:var(--primary); }
        .fill-low    { background:var(--danger); }
        .fill-over   { background:var(--warn); }
        .fill-age    { background:#f39c12; }

        /* Action buttons */
        .btn-edit  { background:#ebf5fb; color:#2980b9; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; transition:.2s; }
        .btn-edit:hover { background:#2980b9; color:white; }
        .btn-waste { background:#fdecea; color:#c0392b; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; transition:.2s; }
        .btn-waste:hover { background:#c0392b; color:white; }

        /* ── MODAL ── */
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9000; justify-content:center; align-items:center; }
        .modal-bg.open { display:flex; }
        .modal { background:white; border-radius:18px; width:100%; max-width:480px; padding:28px; }
        .modal h3 { font-family:'Outfit',sans-serif; font-size:18px; font-weight:800; margin-bottom:20px; color:var(--dark); }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:6px; }
        .form-group input, .form-group select {
            width:100%; padding:10px 14px; border:1.5px solid #e0e0e0;
            border-radius:8px; font-size:14px; outline:none; transition:.2s;
        }
        .form-group input:focus, .form-group select:focus { border-color:var(--primary); }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .btn-save { background:var(--primary); color:white; border:none; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; width:100%; transition:.2s; }
        .btn-save:hover { background:#219150; }
        .btn-cancel { background:#f8f9fa; color:#636e72; border:none; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; width:100%; margin-top:8px; }

        /* ── WASTE LOG TABLE ── */
        .waste-table { width:100%; border-collapse:collapse; font-size:13px; }
        .waste-table th { background:#fef9ec; color:#856404; font-size:11px; text-transform:uppercase; letter-spacing:.5px; padding:10px 14px; text-align:left; }
        .waste-table td { padding:10px 14px; border-bottom:1px solid #f1f2f6; }
        .waste-table tr:last-child td { border-bottom:none; }
        .reason-badge { padding:3px 10px; border-radius:20px; font-size:10px; font-weight:700; }
        .r-expired  { background:#fdecea; color:#c0392b; }
        .r-spoiled  { background:#fdecea; color:#922b21; }
        .r-damaged  { background:#fff3e0; color:#e65100; }
        .r-overstock{ background:#e8f4fd; color:#2471a3; }

        /* ── PRICE SUGGESTION BANNER ── */
        .suggest-card { background:linear-gradient(135deg,#fff8e1,#fffde7); border:1.5px solid #f9a825; border-radius:14px; padding:16px 20px; margin-bottom:14px; display:flex; align-items:center; gap:14px; }
        .suggest-icon { font-size:24px; color:#f9a825; flex-shrink:0; }
        .suggest-text { font-size:13px; color:#7d5300; line-height:1.6; }
        .suggest-text strong { color:#5d3a00; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h2>ManningHub</h2>
        <p>Admin Panel</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="vendor_management.php"><i class="fas fa-store"></i> Vendors</a></li>
        <li><a href="farmer_management.php"><i class="fas fa-tractor"></i> Farmers</a></li>
        <li><a href="hygiene_inspection.php"><i class="fas fa-shield-virus"></i> Hygiene</a></li>
        <li><a href="quality_inspection.php"><i class="fas fa-star-half-alt"></i> Quality</a></li>
        <li><a href="stock_management.php" class="active"><i class="fas fa-boxes"></i> Stock</a></li>
        <li><a href="products_list.php"><i class="fas fa-seedling"></i> Products</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Prices</a></li>
        <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        <li style="margin-top:auto;padding-top:20px;">
            <a href="logout.php" style="color:#e74c3c !important;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main">

    <!-- Page Header -->
    <div class="page-head">
        <div>
            <h1><i class="fas fa-boxes" style="color:var(--primary);margin-right:10px;"></i>Stock Management</h1>
            <p>Monitor stock levels &amp; prevent vegetable waste — inspired by Sydney Markets &amp; Milan Wholesale Market</p>
        </div>
        <div style="font-size:13px;color:#888;"><i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?></div>
    </div>

    <!-- Alert Banner -->
    <?php if (isset($_GET['msg'])): ?>
    <div class="alert-banner <?php echo $_GET['msg']==='waste_logged' ? 'alert-waste' : 'alert-success'; ?>">
        <i class="fas <?php echo $_GET['msg']==='waste_logged' ? 'fa-trash-alt' : 'fa-check-circle'; ?>"></i>
        <?php echo $_GET['msg']==='waste_logged' ? 'Waste logged and stock deducted.' : 'Stock updated successfully.'; ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card green">
            <div class="stat-icon" style="color:var(--primary);"><i class="fas fa-boxes"></i></div>
            <div class="stat-num"><?php echo $total; ?></div>
            <div class="stat-lbl">Products Tracked</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon" style="color:var(--danger);"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-num"><?php echo $low_count; ?></div>
            <div class="stat-lbl">Low Stock Alerts</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon" style="color:var(--warn);"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-num"><?php echo $over_count; ?></div>
            <div class="stat-lbl">Overstocked (Waste Risk)</div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon" style="color:#f39c12;"><i class="fas fa-clock"></i></div>
            <div class="stat-num"><?php echo $age_count; ?></div>
            <div class="stat-lbl">Ageing Stock (&ge;2 days)</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon" style="color:var(--purple);"><i class="fas fa-trash-alt"></i></div>
            <div class="stat-num"><?php echo $waste_total; ?>kg</div>
            <div class="stat-lbl">Waste This Month</div>
        </div>
    </div>

    <!-- Price Reduction Suggestion for overstocked + ageing items -->
    <?php
    $suggest_res = $conn->query("
        SELECT product_name, quantity, max_quantity, DATEDIFF(CURDATE(), received_at) AS days_old
        FROM stock
        WHERE quantity > max_quantity AND DATEDIFF(CURDATE(), received_at) >= 2 AND id <= 26
        LIMIT 3
    ");
    if ($suggest_res && $suggest_res->num_rows > 0):
    ?>
    <div class="section-box">
        <div class="sec-title"><i class="fas fa-lightbulb"></i> Waste Prevention Suggestions</div>
        <?php while ($s = $suggest_res->fetch_assoc()): ?>
        <div class="suggest-card">
            <div class="suggest-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="suggest-text">
                <strong><?php echo htmlspecialchars($s['product_name']); ?></strong> is
                <strong>overstocked</strong> (<?php echo $s['quantity']; ?>kg) and
                has been in stock for <strong><?php echo $s['days_old']; ?> days</strong>.
                Consider reducing the price band maximum to move stock faster and prevent spoilage.
                <a href="admin_dashboard.php" style="color:var(--warn);font-weight:700;text-decoration:none;margin-left:8px;">
                    Update Price &rarr;
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Stock Table -->
    <div class="section-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
            <div class="sec-title" style="margin:0;"><i class="fas fa-table"></i> Current Stock Levels</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span style="font-size:12px;color:#888;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--primary);margin-right:4px;"></span>Normal</span>
                <span style="font-size:12px;color:#888;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--danger);margin-right:4px;"></span>Low</span>
                <span style="font-size:12px;color:#888;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--warn);margin-right:4px;"></span>Overstocked</span>
                <span style="font-size:12px;color:#888;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f39c12;margin-right:4px;"></span>Ageing</span>
            </div>
        </div>

        <div style="overflow-x:auto;">
        <table class="stock-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Qty (kg)</th>
                    <th>Stock Level</th>
                    <th>Min / Max</th>
                    <th>Received</th>
                    <th>Age</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($stock_res && $stock_res->num_rows > 0):
                while ($row = $stock_res->fetch_assoc()):
                    $qty     = (int)$row['quantity'];
                    $min_q   = (int)$row['min_quantity'];
                    $max_q   = (int)$row['max_quantity'];
                    $days    = (int)$row['days_old'];
                    $pct     = $max_q > 0 ? min(100, round(($qty / $max_q) * 100)) : 0;
                    $img     = !empty($row['veg_image']) ? 'image/'.$row['veg_image'] : 'image/image5.jpg';

                    // Determine status
                    if ($qty < $min_q) {
                        $status = 'LOW'; $badge = 's-low'; $bar = 'fill-low';
                        $status_label = '<i class="fas fa-arrow-down"></i> Low Stock';
                    } elseif ($qty > $max_q) {
                        $status = 'OVER'; $badge = 's-over'; $bar = 'fill-over';
                        $status_label = '<i class="fas fa-arrow-up"></i> Overstocked';
                    } elseif ($days >= 2) {
                        $status = 'AGE'; $badge = 's-age'; $bar = 'fill-age';
                        $status_label = '<i class="fas fa-clock"></i> Ageing';
                    } else {
                        $status = 'OK'; $badge = 's-ok'; $bar = 'fill-ok';
                        $status_label = '<i class="fas fa-check"></i> Normal';
                    }
                    $bar_color = $pct < 20 ? 'fill-low' : ($pct > 100 ? 'fill-over' : $bar);
            ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="<?php echo $img; ?>" class="prod-img" onerror="this.src='image/image5.jpg'">
                            <span class="prod-name"><?php echo htmlspecialchars($row['product_name']); ?></span>
                        </div>
                    </td>
                    <td><span class="status-badge <?php echo $badge; ?>"><?php echo $status_label; ?></span></td>
                    <td><strong style="font-size:16px;color:var(--dark);"><?php echo $qty; ?></strong> kg</td>
                    <td style="min-width:120px;">
                        <div class="stock-bar">
                            <div class="stock-fill <?php echo $bar_color; ?>" style="width:<?php echo min(100,$pct); ?>%"></div>
                        </div>
                        <div style="font-size:10px;color:#aaa;margin-top:3px;"><?php echo $pct; ?>% of max</div>
                    </td>
                    <td style="font-size:12px;">
                        <span style="color:var(--danger);font-weight:700;"><?php echo $min_q; ?></span>
                        &nbsp;/&nbsp;
                        <span style="color:var(--warn);font-weight:700;"><?php echo $max_q; ?></span>
                    </td>
                    <td style="font-size:12px;color:#666;">
                        <?php echo $row['received_at'] ? date('M d', strtotime($row['received_at'])) : 'Not set'; ?>
                    </td>
                    <td>
                        <?php if ($days >= 2): ?>
                        <span style="color:#f39c12;font-weight:700;font-size:12px;"><?php echo $days; ?> days</span>
                        <?php else: ?>
                        <span style="color:#aaa;font-size:12px;"><?php echo $days; ?> day<?php echo $days!=1?'s':''; ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <button class="btn-edit"
                            onclick="openEdit(<?php echo $row['id']; ?>,'<?php echo addslashes($row['product_name']); ?>',<?php echo $qty; ?>,<?php echo $min_q; ?>,<?php echo $max_q; ?>,'<?php echo $row['received_at']; ?>')">
                            <i class="fas fa-edit"></i> Update
                        </button>
                        <button class="btn-waste"
                            onclick="openWaste(<?php echo $row['id']; ?>,'<?php echo addslashes($row['product_name']); ?>')">
                            <i class="fas fa-trash-alt"></i> Log Waste
                        </button>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#aaa;">No stock data found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Waste Log -->
    <div class="section-box">
        <div class="sec-title"><i class="fas fa-history"></i> Recent Waste Log</div>
        <?php if ($waste_log && $waste_log->num_rows > 0): ?>
        <table class="waste-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Waste (kg)</th>
                    <th>Reason</th>
                    <th>Logged By</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($w = $waste_log->fetch_assoc()):
                $reason_class = [
                    'Expired'   => 'r-expired',
                    'Spoiled'   => 'r-spoiled',
                    'Damaged'   => 'r-damaged',
                    'Overstock' => 'r-overstock',
                ][$w['reason']] ?? 'r-expired';
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($w['product_name']); ?></strong></td>
                    <td><strong style="color:var(--danger);"><?php echo $w['waste_kg']; ?> kg</strong></td>
                    <td><span class="reason-badge <?php echo $reason_class; ?>"><?php echo $w['reason']; ?></span></td>
                    <td style="color:#666;"><?php echo htmlspecialchars($w['logged_by']); ?></td>
                    <td style="color:#999;font-size:12px;"><?php echo date('M d, h:i A', strtotime($w['logged_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="text-align:center;padding:30px;color:#ccc;">
                <i class="fas fa-check-circle" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                No waste logged yet this month.
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Edit Stock Modal -->
<div class="modal-bg" id="editModal">
    <div class="modal">
        <h3><i class="fas fa-edit" style="color:var(--primary);margin-right:8px;"></i>Update Stock</h3>
        <form method="POST">
            <input type="hidden" name="update_stock" value="1">
            <input type="hidden" name="stock_id" id="edit_id">
            <div class="form-group">
                <label>Product</label>
                <input type="text" id="edit_name" readonly style="background:#f8f9fa;">
            </div>
            <div class="form-group">
                <label>Current Quantity (kg)</label>
                <input type="number" name="quantity" id="edit_qty" min="0" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Min Quantity (kg) <span style="font-size:10px;color:#e74c3c;">Low stock alert below this</span></label>
                    <input type="number" name="min_quantity" id="edit_min" min="0" required>
                </div>
                <div class="form-group">
                    <label>Max Quantity (kg) <span style="font-size:10px;color:#e67e22;">Overstock alert above this</span></label>
                    <input type="number" name="max_quantity" id="edit_max" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Date Received <span style="font-size:10px;color:#888;">(used to track ageing)</span></label>
                <input type="date" name="received_at" id="edit_date" required>
            </div>
            <button type="submit" class="btn-save">Save Changes</button>
            <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
        </form>
    </div>
</div>

<!-- Log Waste Modal -->
<div class="modal-bg" id="wasteModal">
    <div class="modal">
        <h3><i class="fas fa-trash-alt" style="color:var(--danger);margin-right:8px;"></i>Log Waste / Disposal</h3>
        <p style="font-size:13px;color:#666;margin-bottom:20px;">Recording waste helps analyse which products are over-supplied so farmers can adjust. Waste amount will be deducted from current stock.</p>
        <form method="POST">
            <input type="hidden" name="log_waste" value="1">
            <input type="hidden" name="stock_id" id="waste_id">
            <input type="hidden" name="product_name" id="waste_name_hidden">
            <div class="form-group">
                <label>Product</label>
                <input type="text" id="waste_name" readonly style="background:#f8f9fa;">
            </div>
            <div class="form-group">
                <label>Waste Amount (kg)</label>
                <input type="number" name="waste_kg" min="1" placeholder="e.g. 25" required>
            </div>
            <div class="form-group">
                <label>Reason for Disposal</label>
                <select name="reason">
                    <option value="Expired">Expired — past shelf life</option>
                    <option value="Spoiled">Spoiled — rot or mould</option>
                    <option value="Damaged">Damaged — during transport</option>
                    <option value="Overstock">Overstock — excess supply</option>
                </select>
            </div>
            <button type="submit" class="btn-save" style="background:var(--danger);">Log Waste & Deduct Stock</button>
            <button type="button" class="btn-cancel" onclick="closeModal('wasteModal')">Cancel</button>
        </form>
    </div>
</div>

<script>
function openEdit(id, name, qty, min, max, date) {
    document.getElementById('edit_id').value   = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_qty').value  = qty;
    document.getElementById('edit_min').value  = min;
    document.getElementById('edit_max').value  = max;
    document.getElementById('edit_date').value = date || new Date().toISOString().split('T')[0];
    document.getElementById('editModal').classList.add('open');
}
function openWaste(id, name) {
    document.getElementById('waste_id').value          = id;
    document.getElementById('waste_name').value        = name;
    document.getElementById('waste_name_hidden').value = name;
    document.getElementById('wasteModal').classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});
</script>
</body>
</html>
