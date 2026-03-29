<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}
include 'db.php';

// ══════════════════════════════════════════════════════════
// ALL DATA QUERIES
// ══════════════════════════════════════════════════════════

// ── Summary stats ─────────────────────────────────────────
$total_vendors   = $conn->query("SELECT COUNT(*) c FROM users WHERE role='vendor' AND status='approved'")->fetch_assoc()['c'];
$total_farmers   = $conn->query("SELECT COUNT(*) c FROM users WHERE role='farmer' AND status='approved'")->fetch_assoc()['c'];
$total_customers = $conn->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$total_products  = $conn->query("SELECT COUNT(*) c FROM items")->fetch_assoc()['c'];
$pending_vendors = $conn->query("SELECT COUNT(*) c FROM users WHERE role='vendor' AND status='pending'")->fetch_assoc()['c'];
$pending_farmers = $conn->query("SELECT COUNT(*) c FROM users WHERE role='farmer' AND status='pending'")->fetch_assoc()['c'];
$low_stock       = $conn->query("SELECT COUNT(*) c FROM stock WHERE quantity < min_quantity")->fetch_assoc()['c'];
$waste_month     = $conn->query("SELECT COALESCE(SUM(waste_kg),0) t FROM waste_log WHERE MONTH(logged_at)=MONTH(CURDATE())")->fetch_assoc()['t'];
$hygiene_fail    = $conn->query("SELECT COUNT(*) c FROM hygiene_inspection WHERE hygiene_status='Fail'")->fetch_assoc()['c'];
$quality_rej     = $conn->query("SELECT COUNT(*) c FROM quality_inspection WHERE quality_status='Rejected'")->fetch_assoc()['c'];
$feedback_unread = $conn->query("SELECT COUNT(*) c FROM feedback WHERE status='unread'")->fetch_assoc()['c'];
$tx_month_total  = $conn->query("SELECT COALESCE(SUM(total_amount),0) t FROM transactions WHERE MONTH(transaction_date)=MONTH(CURDATE())")->fetch_assoc()['t'];
$tx_month_count  = $conn->query("SELECT COUNT(*) c FROM transactions WHERE MONTH(transaction_date)=MONTH(CURDATE())")->fetch_assoc()['c'];

// ── Chart 1: Top 10 products by average vendor price ──────
$price_chart = $conn->query("
    SELECT i.veg_name, ROUND(AVG(vp.selling_price),2) avg_price
    FROM vendor_prices vp
    JOIN items i ON vp.item_id = i.id
    GROUP BY i.id, i.veg_name
    ORDER BY avg_price DESC
    LIMIT 10
");
$price_labels = []; $price_data = [];
while ($r = $price_chart->fetch_assoc()) {
    $price_labels[] = $r['veg_name'];
    $price_data[]   = $r['avg_price'];
}

// ── Chart 2: Vendor status doughnut ───────────────────────
$vstat = $conn->query("SELECT status, COUNT(*) c FROM users WHERE role='vendor' GROUP BY status");
$vstat_labels = []; $vstat_data = []; $vstat_colors = [];
$color_map = ['approved'=>'#27ae60','pending'=>'#f39c12','rejected'=>'#e74c3c','suspended'=>'#95a5a6'];
while ($r = $vstat->fetch_assoc()) {
    $vstat_labels[] = ucfirst($r['status']);
    $vstat_data[]   = $r['c'];
    $vstat_colors[] = $color_map[$r['status']] ?? '#3498db';
}

// ── Chart 3: Hygiene results bar ──────────────────────────
$hyg = $conn->query("SELECT hygiene_status, COUNT(*) c FROM hygiene_inspection GROUP BY hygiene_status");
$hyg_labels = []; $hyg_data = []; $hyg_colors = [];
$hyg_color_map = ['Pass'=>'#27ae60','Fail'=>'#e74c3c','Warning'=>'#f39c12','Under Review'=>'#3498db'];
while ($r = $hyg->fetch_assoc()) {
    $hyg_labels[] = $r['hygiene_status'];
    $hyg_data[]   = $r['c'];
    $hyg_colors[] = $hyg_color_map[$r['hygiene_status']] ?? '#95a5a6';
}

// ── Chart 4: Quality inspection doughnut ─────────────────
$qual = $conn->query("SELECT quality_status, COUNT(*) c FROM quality_inspection GROUP BY quality_status");
$qual_labels = []; $qual_data = []; $qual_colors = [];
$qual_color_map = ['Excellent'=>'#27ae60','Good'=>'#2ecc71','Average'=>'#f39c12','Poor'=>'#e67e22','Rejected'=>'#e74c3c'];
while ($r = $qual->fetch_assoc()) {
    $qual_labels[] = $r['quality_status'];
    $qual_data[]   = $r['c'];
    $qual_colors[] = $qual_color_map[$r['quality_status']] ?? '#95a5a6';
}

// ── Chart 5: Stock levels current vs min ─────────────────
$stock_res = $conn->query("SELECT product_name, quantity, min_quantity FROM stock WHERE id<=26 ORDER BY quantity DESC LIMIT 10");
$stk_labels = []; $stk_curr = []; $stk_min = [];
while ($r = $stock_res->fetch_assoc()) {
    $stk_labels[] = $r['product_name'];
    $stk_curr[]   = $r['quantity'];
    $stk_min[]    = $r['min_quantity'];
}

// ── Chart 6: Transaction revenue last 7 days ─────────────
$tx_days = $conn->query("
    SELECT transaction_date, COALESCE(SUM(total_amount),0) total
    FROM transactions
    WHERE transaction_date >= CURDATE() - INTERVAL 6 DAY
    GROUP BY transaction_date
    ORDER BY transaction_date ASC
");
$tx_day_map = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $tx_day_map[$d] = 0;
}
while ($r = $tx_days->fetch_assoc()) $tx_day_map[$r['transaction_date']] = floatval($r['total']);
$tx_labels = array_map(fn($d) => date('M d', strtotime($d)), array_keys($tx_day_map));
$tx_totals = array_values($tx_day_map);

// ── Table 1: Top 5 vendors by products priced ────────────
$top_vendors = $conn->query("
    SELECT u.full_name, u.stall_number, COUNT(vp.id) cnt, ROUND(AVG(vp.selling_price),2) avg_p
    FROM vendor_prices vp
    JOIN users u ON vp.vendor_id = u.id
    WHERE u.role='vendor'
    GROUP BY vp.vendor_id
    ORDER BY cnt DESC LIMIT 5
");

// ── Table 2: Most expensive products ─────────────────────
$top_priced = $conn->query("
    SELECT i.veg_name, MAX(vp.selling_price) max_p, MIN(vp.selling_price) min_p, ROUND(AVG(vp.selling_price),2) avg_p
    FROM vendor_prices vp JOIN items i ON vp.item_id=i.id
    GROUP BY i.id ORDER BY avg_p DESC LIMIT 5
");

// ── Table 3: Waste log summary ────────────────────────────
$waste_summary = $conn->query("
    SELECT product_name, SUM(waste_kg) total_waste, COUNT(*) incidents
    FROM waste_log GROUP BY product_name ORDER BY total_waste DESC LIMIT 5
");

// ── Table 4: Hygiene risk vendors ────────────────────────
$risk_vendors = $conn->query("
    SELECT v.name stall_name, hi.hygiene_status, hi.inspection_date, hi.remarks
    FROM hygiene_inspection hi
    JOIN vendors v ON hi.vendor_id = v.id
    WHERE hi.hygiene_status IN ('Fail','Warning')
    ORDER BY hi.inspection_date DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
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

        /* Stats grid */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:24px; }
        .stat-card { background:white; border-radius:14px; padding:16px; border-left:4px solid #ddd; box-shadow:0 2px 8px rgba(0,0,0,.05); }
        .stat-card.green  { border-left-color:var(--primary); }
        .stat-card.blue   { border-left-color:var(--info); }
        .stat-card.orange { border-left-color:var(--warn); }
        .stat-card.red    { border-left-color:var(--danger); }
        .stat-card.purple { border-left-color:#8e44ad; }
        .stat-card.teal   { border-left-color:#16a085; }
        .stat-icon { font-size:20px; margin-bottom:6px; }
        .stat-num  { font-family:'Outfit',sans-serif; font-size:22px; font-weight:900; color:var(--dark); }
        .stat-lbl  { font-size:11px; color:#888; margin-top:2px; }

        /* Section */
        .section-title { font-family:'Outfit',sans-serif; font-size:17px; font-weight:800; color:var(--dark); margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .section-title i { color:var(--primary); }

        /* Charts grid */
        .charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
        .chart-card  { background:white; border-radius:16px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.05); }
        .chart-title { font-size:14px; font-weight:700; color:var(--dark); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .chart-title i { color:var(--primary); }
        .chart-wrap  { position:relative; height:220px; }
        .chart-full  { grid-column:1/-1; }

        /* Tables */
        .tables-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
        .table-card  { background:white; border-radius:16px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.05); }
        .rpt-table   { width:100%; border-collapse:collapse; }
        .rpt-table th { background:#f8f9fa; color:#636e72; font-size:11px; text-transform:uppercase; letter-spacing:.5px; padding:10px 12px; text-align:left; }
        .rpt-table td { padding:10px 12px; border-bottom:1px solid #f1f2f6; font-size:13px; }
        .rpt-table tr:last-child td { border-bottom:none; }
        .badge { padding:3px 8px; border-radius:12px; font-size:10px; font-weight:700; }
        .b-fail { background:#fdecea; color:#c0392b; }
        .b-warn { background:#fef9ec; color:#856404; }

        /* Print button */
        .btn-print { background:var(--dark); color:white; border:none; padding:10px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; }

        @media(max-width:900px) {
            .charts-grid, .tables-grid { grid-template-columns:1fr; }
            .chart-full { grid-column:auto; }
        }
        @media print {
            .sidebar, .btn-print { display:none !important; }
            .main { margin-left:0 !important; width:100% !important; }
        }
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
        <li><a href="transaction_records.php"><i class="fas fa-receipt"></i> Transactions</a></li>
        <li><a href="reports_analytics.php" class="active"><i class="fas fa-chart-bar"></i> Reports & Analytics</a></li>
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
            <h1><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:8px;"></i>Reports & Analytics</h1>
            <p>Market performance overview — <?php echo date('F Y'); ?></p>
        </div>
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
    </div>

    <!-- ── SUMMARY STATS ── -->
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon" style="color:var(--primary);">🏪</div>
            <div class="stat-num"><?php echo $total_vendors; ?></div>
            <div class="stat-lbl">Active Vendors</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon" style="color:var(--info);">🌾</div>
            <div class="stat-num"><?php echo $total_farmers; ?></div>
            <div class="stat-lbl">Active Farmers</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon" style="color:#8e44ad;">🛒</div>
            <div class="stat-num"><?php echo $total_customers; ?></div>
            <div class="stat-lbl">Customers</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon" style="color:var(--primary);">🥬</div>
            <div class="stat-num"><?php echo $total_products; ?></div>
            <div class="stat-lbl">Products Listed</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon" style="color:var(--warn);">⏳</div>
            <div class="stat-num"><?php echo $pending_vendors + $pending_farmers; ?></div>
            <div class="stat-lbl">Pending Approvals</div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon" style="color:#16a085;">💰</div>
            <div class="stat-num">Rs.<?php echo number_format($tx_month_total/1000, 1); ?>K</div>
            <div class="stat-lbl">This Month Revenue</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon" style="color:var(--danger);">📦</div>
            <div class="stat-num"><?php echo $low_stock; ?></div>
            <div class="stat-lbl">Low Stock Items</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon" style="color:var(--warn);">🗑️</div>
            <div class="stat-num"><?php echo $waste_month; ?>kg</div>
            <div class="stat-lbl">Waste This Month</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon" style="color:var(--danger);">🚫</div>
            <div class="stat-num"><?php echo $hygiene_fail; ?></div>
            <div class="stat-lbl">Hygiene Fails</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon" style="color:var(--danger);">❌</div>
            <div class="stat-num"><?php echo $quality_rej; ?></div>
            <div class="stat-lbl">Quality Rejected</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon" style="color:var(--warn);">💬</div>
            <div class="stat-num"><?php echo $feedback_unread; ?></div>
            <div class="stat-lbl">Unread Feedback</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon" style="color:var(--info);">🧾</div>
            <div class="stat-num"><?php echo $tx_month_count; ?></div>
            <div class="stat-lbl">Transactions This Month</div>
        </div>
    </div>

    <!-- ── CHARTS ROW 1 ── -->
    <div class="section-title"><i class="fas fa-chart-bar"></i> Market Charts</div>
    <div class="charts-grid">

        <!-- Chart 1: Top products by price -->
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-tags"></i> Average Vendor Price — Top 10 Products (Rs/kg)</div>
            <div class="chart-wrap"><canvas id="priceChart"></canvas></div>
        </div>

        <!-- Chart 2: Vendor status doughnut -->
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-store"></i> Vendor Account Status</div>
            <div class="chart-wrap"><canvas id="vendorChart"></canvas></div>
        </div>

        <!-- Chart 3: Hygiene bar -->
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-shield-virus"></i> Hygiene Inspection Results</div>
            <div class="chart-wrap"><canvas id="hygChart"></canvas></div>
        </div>

        <!-- Chart 4: Quality doughnut -->
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-star-half-alt"></i> Quality Inspection Results</div>
            <div class="chart-wrap"><canvas id="qualChart"></canvas></div>
        </div>

        <!-- Chart 5: Stock levels — full width -->
        <div class="chart-card chart-full">
            <div class="chart-title"><i class="fas fa-boxes"></i> Current Stock vs Minimum Required (kg) — Top 10 Products</div>
            <div class="chart-wrap" style="height:260px;"><canvas id="stockChart"></canvas></div>
        </div>

        <!-- Chart 6: Revenue line — full width -->
        <div class="chart-card chart-full">
            <div class="chart-title"><i class="fas fa-chart-line"></i> Transaction Revenue — Last 7 Days (Rs)</div>
            <div class="chart-wrap" style="height:220px;"><canvas id="txChart"></canvas></div>
        </div>

    </div>

    <!-- ── DATA TABLES ── -->
    <div class="section-title"><i class="fas fa-table"></i> Detailed Reports</div>
    <div class="tables-grid">

        <!-- Table 1: Top vendors -->
        <div class="table-card">
            <div class="chart-title"><i class="fas fa-trophy"></i> Top 5 Vendors by Products Priced</div>
            <table class="rpt-table">
                <thead><tr><th>#</th><th>Vendor</th><th>Stall</th><th>Products</th><th>Avg Price</th></tr></thead>
                <tbody>
                <?php $i=1; while($r=$top_vendors->fetch_assoc()): ?>
                <tr>
                    <td style="color:#f39c12;font-weight:700;"><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($r['full_name']); ?></strong></td>
                    <td><?php echo $r['stall_number']; ?></td>
                    <td><strong><?php echo $r['cnt']; ?></strong></td>
                    <td>Rs.<?php echo $r['avg_p']; ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Table 2: Most expensive -->
        <div class="table-card">
            <div class="chart-title"><i class="fas fa-fire"></i> Top 5 Most Expensive Products</div>
            <table class="rpt-table">
                <thead><tr><th>Product</th><th>Max</th><th>Min</th><th>Avg</th></tr></thead>
                <tbody>
                <?php while($r=$top_priced->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($r['veg_name']); ?></strong></td>
                    <td style="color:var(--danger);">Rs.<?php echo $r['max_p']; ?></td>
                    <td style="color:var(--primary);">Rs.<?php echo $r['min_p']; ?></td>
                    <td><strong>Rs.<?php echo $r['avg_p']; ?></strong></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Table 3: Waste summary -->
        <div class="table-card">
            <div class="chart-title"><i class="fas fa-trash-alt"></i> Waste Log Summary</div>
            <?php if ($waste_summary && $waste_summary->num_rows > 0): ?>
            <table class="rpt-table">
                <thead><tr><th>Product</th><th>Total Waste</th><th>Incidents</th></tr></thead>
                <tbody>
                <?php while($r=$waste_summary->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['product_name']); ?></td>
                    <td><strong style="color:var(--danger);"><?php echo $r['total_waste']; ?> kg</strong></td>
                    <td><?php echo $r['incidents']; ?> times</td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color:#ccc;text-align:center;padding:20px;">No waste logged yet.</p>
            <?php endif; ?>
        </div>

        <!-- Table 4: Hygiene risk -->
        <div class="table-card">
            <div class="chart-title"><i class="fas fa-exclamation-triangle"></i> Hygiene Risk Vendors</div>
            <?php if ($risk_vendors && $risk_vendors->num_rows > 0): ?>
            <table class="rpt-table">
                <thead><tr><th>Vendor</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while($r=$risk_vendors->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['stall_name']); ?></td>
                    <td><span class="badge <?php echo $r['hygiene_status']==='Fail'?'b-fail':'b-warn'; ?>"><?php echo $r['hygiene_status']; ?></span></td>
                    <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($r['inspection_date'])); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="color:#ccc;text-align:center;padding:20px;">No hygiene risks found.</p>
            <?php endif; ?>
        </div>

    </div>

</div><!-- end main -->

<script>
// ── Chart defaults ────────────────────────────────────────
Chart.defaults.font.family = "'Segoe UI', sans-serif";
Chart.defaults.font.size   = 12;

// ── Chart 1: Top Products by Price ────────────────────────
new Chart(document.getElementById('priceChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($price_labels); ?>,
        datasets: [{
            label: 'Avg Price (Rs/kg)',
            data: <?php echo json_encode($price_data); ?>,
            backgroundColor: ['#27ae60','#2ecc71','#3498db','#9b59b6','#e67e22','#e74c3c','#f1c40f','#1abc9c','#34495e','#95a5a6'],
            borderRadius: 6,
        }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});

// ── Chart 2: Vendor Status Doughnut ───────────────────────
new Chart(document.getElementById('vendorChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($vstat_labels); ?>,
        datasets: [{ data: <?php echo json_encode($vstat_data); ?>, backgroundColor: <?php echo json_encode($vstat_colors); ?>, borderWidth:2 }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
});

// ── Chart 3: Hygiene Bar ──────────────────────────────────
new Chart(document.getElementById('hygChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($hyg_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($hyg_data); ?>,
            backgroundColor: <?php echo json_encode($hyg_colors); ?>,
            borderRadius: 6,
        }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});

// ── Chart 4: Quality Doughnut ─────────────────────────────
new Chart(document.getElementById('qualChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($qual_labels); ?>,
        datasets: [{ data: <?php echo json_encode($qual_data); ?>, backgroundColor: <?php echo json_encode($qual_colors); ?>, borderWidth:2 }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
});

// ── Chart 5: Stock Levels ─────────────────────────────────
new Chart(document.getElementById('stockChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($stk_labels); ?>,
        datasets: [
            { label:'Current Stock (kg)', data:<?php echo json_encode($stk_curr); ?>, backgroundColor:'#27ae60', borderRadius:5 },
            { label:'Minimum Required',   data:<?php echo json_encode($stk_min); ?>,  backgroundColor:'#e74c3c', borderRadius:5 }
        ]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true}} }
});

// ── Chart 6: Revenue Line ─────────────────────────────────
new Chart(document.getElementById('txChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($tx_labels); ?>,
        datasets: [{
            label: 'Revenue (Rs)',
            data: <?php echo json_encode($tx_totals); ?>,
            borderColor: '#27ae60',
            backgroundColor: 'rgba(39,174,96,.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#27ae60',
            pointRadius: 5,
        }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
});
</script>
</body>
</html>
