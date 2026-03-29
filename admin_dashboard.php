<?php

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}

include 'db.php';

// Stats
$item_count  = $conn->query("SELECT COUNT(*) c FROM items")->fetch_assoc()['c'];
$vendor_total= $conn->query("SELECT COUNT(*) c FROM vendors")->fetch_assoc()['c'];
$vendor_pend = $conn->query("SELECT COUNT(*) c FROM vendors WHERE status='pending'")->fetch_assoc()['c'];
$farmer_pend = $conn->query("SELECT COUNT(*) c FROM farmers WHERE status='pending'")->fetch_assoc()['c'];
$hygiene_fail= $conn->query("SELECT COUNT(*) c FROM hygiene_inspection WHERE hygiene_status='Fail'")->fetch_assoc()['c'];
$quality_rej = $conn->query("SELECT COUNT(*) c FROM quality_inspection WHERE quality_status='Rejected'")->fetch_assoc()['c'];
$stock_low  = $conn->query("SELECT COUNT(*) c FROM stock WHERE quantity < min_quantity AND id<=26")->fetch_assoc()['c'];
$stock_over = $conn->query("SELECT COUNT(*) c FROM stock WHERE quantity > max_quantity AND id<=26")->fetch_assoc()['c'];
$stock_age  = $conn->query("SELECT COUNT(*) c FROM stock WHERE DATEDIFF(CURDATE(), received_at) >= 2 AND id<=26")->fetch_assoc()['c'];
$feedback_unread = $conn->query("SELECT COUNT(*) c FROM feedback WHERE status='unread'")->fetch_assoc()['c'];
$last_upd    = $conn->query("SELECT MAX(updated_at) t FROM items")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-HUB | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #1e272e;
            --primary: #27ae60;
            --accent:  #2ecc71;
            --danger:  #e74c3c;
            --warning: #f1c40f;
            --info:    #3498db;
            --bg:      #f4f7f6;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body { background:var(--bg); display:flex; color:#2d3436; }

        /* Sidebar */
        .sidebar { width:260px; height:100vh; background:var(--sidebar-bg); color:white; position:fixed; overflow-y:auto; }
        .sidebar-header { padding:25px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.08); }
        .sidebar-header h2 { color:var(--accent); letter-spacing:2px; font-size:20px; }
        .sidebar-header p { font-size:11px; color:#7f8c8d; margin-top:4px; }
        .menu-label { font-size:10px; text-transform:uppercase; color:#7f8c8d; padding:14px 18px 4px; letter-spacing:1px; }
        .sidebar-menu { list-style:none; padding:15px 10px; }
        .sidebar-menu li a {
            color:#d1d8e0; text-decoration:none; padding:11px 14px;
            display:flex; align-items:center; gap:11px;
            border-radius:8px; transition:0.25s; margin-bottom:4px; font-size:14px;
        }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background:var(--primary); color:white; }
        .notif-dot { background:var(--danger); color:white; border-radius:10px; padding:1px 7px; font-size:11px; margin-left:auto; }

        /* Main */
        .main { margin-left:260px; width:calc(100% - 260px); padding:28px; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; }
        .top-bar h1 { font-size:22px; color:#2c3e50; }
        .top-bar span { font-size:13px; color:#95a5a6; }

        /* Stats grid */
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:28px; }
        .stat-card { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.04); display:flex; align-items:center; gap:14px; }
        .stat-card.green  { border-left:5px solid var(--primary); }
        .stat-card.blue   { border-left:5px solid var(--info); }
        .stat-card.orange { border-left:5px solid var(--warning); }
        .stat-card.red    { border-left:5px solid var(--danger); }
        .stat-card i { font-size:28px; color:#b2bec3; }
        .stat-info h3 { font-size:22px; font-weight:800; color:#2c3e50; }
        .stat-info p  { font-size:12px; color:#636e72; margin-top:2px; }

        /* Module cards */
        .section-title { font-size:16px; font-weight:700; color:#2c3e50; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .modules-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:18px; margin-bottom:28px; }
        .module-card {
            background:white; border-radius:14px; padding:22px;
            box-shadow:0 2px 12px rgba(0,0,0,0.04); text-decoration:none;
            color:inherit; transition:0.25s; display:flex; align-items:center; gap:16px;
            border:2px solid transparent;
        }
        .module-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.08); border-color:var(--primary); }
        .module-icon { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .module-icon.green  { background:#eafaf1; color:var(--primary); }
        .module-icon.blue   { background:#eaf3fb; color:var(--info); }
        .module-icon.purple { background:#f5eef8; color:#8e44ad; }
        .module-icon.orange { background:#fef5e7; color:#e67e22; }
        .module-text h4 { font-size:15px; color:#2c3e50; margin-bottom:4px; }
        .module-text p  { font-size:12px; color:#95a5a6; }
        .alert-dot { background:var(--danger); color:white; border-radius:10px; padding:2px 8px; font-size:11px; font-weight:700; margin-left:auto; flex-shrink:0; }

        /* Quick price form */
        .section-box { background:white; padding:24px; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,0.04); margin-bottom:28px; }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; align-items:flex-end; }
        .form-group label { display:block; margin-bottom:5px; font-size:12px; font-weight:600; color:#636e72; }
        .form-group input { width:100%; padding:9px 12px; border:1px solid #dfe6e9; border-radius:8px; outline:none; font-size:13px; }
        .btn-update { background:var(--primary); color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:700; width:100%; transition:0.2s; font-size:13px; }
        .btn-update:hover { background:#219150; }

        /* Table */
        table { width:100%; border-collapse:collapse; }
        thead th { background:#f8f9fa; color:#636e72; padding:11px 16px; text-align:left; font-size:11px; text-transform:uppercase; }
        tbody td { padding:12px 16px; border-bottom:1px solid #f1f2f6; font-size:13px; }
        .product-img { width:42px; height:42px; border-radius:8px; object-fit:cover; }
        .price-badge { background:#e8f8f0; color:var(--primary); padding:4px 10px; border-radius:14px; font-weight:700; font-size:13px; }
        .btn-delete { color:var(--danger); background:none; border:none; cursor:pointer; font-size:16px; }
        .search-box { position:relative; width:280px; }
        .search-box input { width:100%; padding:9px 34px 9px 14px; border:1px solid #dfe6e9; border-radius:20px; outline:none; font-size:13px; }
        .search-box i { position:absolute; right:12px; top:10px; color:#b2bec3; }
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }

        /* ── NOTIFICATION BELL ── */
        .bell-wrap { position:relative; display:inline-block; }
        .bell-btn  { background:none; border:none; cursor:pointer; font-size:20px; color:#636e72; position:relative; padding:6px 8px; transition:.2s; }
        .bell-btn:hover { color:var(--primary,#27ae60); }
        .bell-badge { position:absolute; top:0; right:0; background:#e74c3c; color:white; font-size:10px; font-weight:800; border-radius:10px; padding:1px 5px; min-width:17px; text-align:center; line-height:15px; }
        .bell-dropdown { display:none; position:absolute; right:0; top:42px; width:320px; background:white; border-radius:14px; box-shadow:0 8px 30px rgba(0,0,0,.15); z-index:9999; overflow:hidden; }
        .bell-dropdown.open { display:block; }
        .bell-head { display:flex; justify-content:space-between; align-items:center; padding:14px 16px; border-bottom:1px solid #f1f2f6; }
        .bell-head h4 { font-size:14px; font-weight:700; color:#2c3e50; }
        .bell-mark-all { font-size:11px; color:#27ae60; cursor:pointer; font-weight:600; background:none; border:none; }
        .bell-item { display:flex; gap:12px; padding:12px 16px; border-bottom:1px solid #f8f9fa; cursor:pointer; transition:.15s; }
        .bell-item:hover { background:#f8fffe; }
        .bell-item.unread { background:#fffef0; }
        .bell-item:last-of-type { border-bottom:none; }
        .bell-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
        .bi-info    { background:#e8f4fd; color:#3498db; }
        .bi-warning { background:#fef9ec; color:#f39c12; }
        .bi-success { background:#eafaf1; color:#27ae60; }
        .bi-danger  { background:#fdecea; color:#e74c3c; }
        .bell-text  { flex:1; min-width:0; }
        .bell-title { font-size:13px; font-weight:700; color:#2c3e50; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .bell-msg   { font-size:11px; color:#95a5a6; margin-top:2px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .bell-time  { font-size:10px; color:#b2bec3; margin-top:3px; }
        .bell-unread-dot { width:6px; height:6px; border-radius:50%; background:#e74c3c; flex-shrink:0; margin-top:6px; }
        .bell-footer { text-align:center; padding:12px; }
        .bell-footer a { font-size:13px; color:#27ae60; text-decoration:none; font-weight:600; }
        .bell-empty { text-align:center; padding:24px; color:#ccc; font-size:13px; }

    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>M-HUB</h2>
        <p>ADMIN CONTROL CENTER</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php" class="active"><i class="fas fa-th-large"></i> Overview</a></li>
        <p class="menu-label">Himasha's Modules</p>
        <li>
            <a href="vendor_management.php">
                <i class="fas fa-store"></i> Vendor Management
                <?php if($vendor_pend > 0): ?><span class="notif-dot"><?php echo $vendor_pend; ?></span><?php endif; ?>
            </a>
        </li>
        <li>
            <a href="farmer_management.php">
                <i class="fas fa-tractor"></i> Farmer Management
                <?php if($farmer_pend > 0): ?><span class="notif-dot"><?php echo $farmer_pend; ?></span><?php endif; ?>
            </a>
        </li>
        <li>
            <a href="hygiene_inspection.php">
                <i class="fas fa-shield-virus"></i> Hygiene Monitoring
                <?php if($hygiene_fail > 0): ?><span class="notif-dot"><?php echo $hygiene_fail; ?></span><?php endif; ?>
            </a>
        </li>
        <li>
            <a href="quality_inspection.php">
                <i class="fas fa-star-half-alt"></i> Quality Inspection
                <?php if($quality_rej > 0): ?><span class="notif-dot"><?php echo $quality_rej; ?></span><?php endif; ?>
            </a>
        </li>
        <p class="menu-label">Dilshara's Modules</p>
        <li><a href="products_list.php"><i class="fas fa-seedling"></i> Products & Prices</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Price Board</a></li>
        <li><a href="transaction_records.php"><i class="fas fa-receipt"></i> Transactions</a></li>
        <li><a href="reports_analytics.php"><i class="fas fa-chart-bar"></i> Reports & Analytics</a></li>
        <p class="menu-label">Yehani's Modules</p>
        <li><a href="price_history.php"><i class="fas fa-file-invoice-dollar"></i> Price History</a></li>
        <li><a href="admin_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
        <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="profile_management.php"><i class="fas fa-user-edit"></i> Profile</a></li>
        <p class="menu-label">System</p>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="index.php"><i class="fas fa-eye"></i> View Website</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="logout.php" style="color:#e74c3c"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main">
    <div class="top-bar" style="justify-content:space-between;align-items:center;">
        <div>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?> 👋</h1>
            <span><?php echo date('l, F j Y — h:i A'); ?></span>
        </div>

    <?php
    // ── NOTIFICATION BELL ──
    $bell_role  = 'admin';
    $bell_uid   = $_SESSION['user_id'] ?? 0;
    $bell_res   = $conn->query("SELECT * FROM notifications WHERE (role='$bell_role' OR role='all') ORDER BY is_read ASC, created_at DESC LIMIT 5");
    $bell_unread= $conn->query("SELECT COUNT(*) c FROM notifications WHERE (role='$bell_role' OR role='all') AND is_read=0")->fetch_assoc()['c'];
    $bell_icons = ['info'=>['bi-info','fa-info-circle'],'warning'=>['bi-warning','fa-exclamation-triangle'],'success'=>['bi-success','fa-check-circle'],'danger'=>['bi-danger','fa-exclamation-circle']];
    function bellTimeAgo($dt) {
        $d = time() - strtotime($dt);
        if ($d < 60)    return 'Just now';
        if ($d < 3600)  return round($d/60).'m ago';
        if ($d < 86400) return round($d/3600).'h ago';
        return round($d/86400).'d ago';
    }
    ?>
    <div class="bell-wrap">
        <button class="bell-btn" onclick="toggleBell(event)" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php if($bell_unread > 0): ?><span class="bell-badge"><?php echo $bell_unread; ?></span><?php endif; ?>
        </button>
        <div class="bell-dropdown" id="bellDropdown">
            <div class="bell-head">
                <h4><i class="fas fa-bell"></i> Notifications</h4>
                <?php if($bell_unread > 0): ?>
                <button class="bell-mark-all" onclick="markAllBell()">Mark all read</button>
                <?php endif; ?>
            </div>
            <?php if($bell_res && $bell_res->num_rows > 0): ?>
            <?php while($bn = $bell_res->fetch_assoc()):
                $bic = $bell_icons[$bn['type']] ?? ['bi-info','fa-bell'];
                $bunread = !$bn['is_read'];
            ?>
            <div class="bell-item <?php echo $bunread?'unread':''; ?>" id="bitem-<?php echo $bn['id']; ?>"
                 onclick="markOneBell(<?php echo $bn['id']; ?>, this)">
                <div class="bell-icon <?php echo $bic[0]; ?>"><i class="fas <?php echo $bic[1]; ?>"></i></div>
                <div class="bell-text">
                    <div class="bell-title"><?php echo htmlspecialchars($bn['title']); ?></div>
                    <div class="bell-msg"><?php echo htmlspecialchars($bn['message']); ?></div>
                    <div class="bell-time"><?php echo bellTimeAgo($bn['created_at']); ?></div>
                </div>
                <?php if($bunread): ?><div class="bell-unread-dot"></div><?php endif; ?>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="bell-empty"><i class="fas fa-bell-slash"></i><br>No notifications yet</div>
            <?php endif; ?>
            <div class="bell-footer"><a href="notifications.php">See all notifications &rarr;</a></div>
        </div>
    </div>
    <script>
    function toggleBell(e) {
        e.stopPropagation();
        document.getElementById('bellDropdown').classList.toggle('open');
    }
    document.addEventListener('click', function() {
        var d = document.getElementById('bellDropdown');
        if (d) d.classList.remove('open');
    });
    function markAllBell() {
        fetch('mark_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_all_read'})
        .then(()=>location.reload());
    }
    function markOneBell(id, el) {
        if(el.classList.contains('unread')){
            fetch('mark_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_one_read&id='+id});
            el.classList.remove('unread');
            var dot = el.querySelector('.bell-unread-dot');
            if(dot) dot.remove();
            var badge = document.querySelector('.bell-badge');
            var cnt = badge ? parseInt(badge.textContent)-1 : 0;
            if(badge){ if(cnt<=0) badge.remove(); else badge.textContent=cnt; }
        }
    }
    </script>

    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card green">
            <i class="fas fa-box-open"></i>
            <div class="stat-info"><h3><?php echo $item_count; ?></h3><p>Active Products</p></div>
        </div>
        <div class="stat-card blue">
            <i class="fas fa-store"></i>
            <div class="stat-info"><h3><?php echo $vendor_total; ?></h3><p>Total Vendors</p></div>
        </div>
        <div class="stat-card orange">
            <i class="fas fa-clock"></i>
            <div class="stat-info"><h3><?php echo $vendor_pend + $farmer_pend; ?></h3><p>Pending Approvals</p></div>
        </div>
        <div class="stat-card red">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="stat-info"><h3><?php echo $hygiene_fail + $quality_rej; ?></h3><p>Quality / Hygiene Alerts</p></div>
        </div>
    </div>

    
    <p class="section-title"><i class="fas fa-puzzle-piece" style="color:var(--primary)"></i> Management Modules</p>
    <div class="modules-grid">
        <a href="vendor_management.php" class="module-card">
            <div class="module-icon green"><i class="fas fa-store"></i></div>
            <div class="module-text">
                <h4>Vendor Management</h4>
                <p>Verify, approve, reject & suspend vendors</p>
            </div>
            <?php if($vendor_pend > 0): ?><span class="alert-dot"><?php echo $vendor_pend; ?> pending</span><?php endif; ?>
        </a>
        <a href="farmer_management.php" class="module-card">
            <div class="module-icon blue"><i class="fas fa-tractor"></i></div>
            <div class="module-text">
                <h4>Farmer Management</h4>
                <p>Approve farmers & assign product categories</p>
            </div>
            <?php if($farmer_pend > 0): ?><span class="alert-dot"><?php echo $farmer_pend; ?> pending</span><?php endif; ?>
        </a>
        <a href="hygiene_inspection.php" class="module-card">
            <div class="module-icon purple"><i class="fas fa-shield-virus"></i></div>
            <div class="module-text">
                <h4>Hygiene Monitoring</h4>
                <p>Record & track vendor hygiene inspections</p>
            </div>
            <?php if($hygiene_fail > 0): ?><span class="alert-dot"><?php echo $hygiene_fail; ?> failed</span><?php endif; ?>
        </a>
        <a href="quality_inspection.php" class="module-card">
            <div class="module-icon orange"><i class="fas fa-star-half-alt"></i></div>
            <div class="module-text">
                <h4>Quality Inspection</h4>
                <p>Record product quality checks per vendor</p>
            </div>
            <?php if($quality_rej > 0): ?><span class="alert-dot"><?php echo $quality_rej; ?> rejected</span><?php endif; ?>
        </a>
        <a href="stock_management.php" class="module-card">
            <div class="module-icon" style="background:#8e44ad;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-boxes" style="color:white;font-size:18px;"></i></div>
            <div class="module-text">
                <h4>Stock Management</h4>
                <p>Track stock levels & prevent waste</p>
            </div>
            <?php if(($stock_low + $stock_over + $stock_age) > 0): ?>
            <span class="alert-dot"><?php echo ($stock_low + $stock_over + $stock_age); ?> alerts</span>
            <?php endif; ?>
        </a>
        <a href="transaction_records.php" class="module-card">
            <div class="module-icon" style="background:#e67e22;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-receipt" style="color:white;font-size:18px;"></i></div>
            <div class="module-text">
                <h4>Transaction Records</h4>
                <p>Record daily market sales & purchases</p>
            </div>
        </a>
        <a href="reports_analytics.php" class="module-card">
            <div class="module-icon" style="background:#3498db;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-chart-bar" style="color:white;font-size:18px;"></i></div>
            <div class="module-text">
                <h4>Reports & Analytics</h4>
                <p>Charts, stats & market performance</p>
            </div>
        </a>
        <a href="admin_feedback.php" class="module-card">
            <div class="module-icon" style="background:#e74c3c;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-comments" style="color:white;font-size:18px;"></i></div>
            <div class="module-text">
                <h4>Feedback & Complaints</h4>
                <p>Review customer and vendor submissions</p>
            </div>
            <?php if($feedback_unread > 0): ?>
            <span class="alert-dot"><?php echo $feedback_unread; ?> unread</span>
            <?php endif; ?>
        </a>
    </div>


    <!-- Send Notification -->
    <div class="section-box" style="margin-top:28px;">
        <div class="section-header">
            <h2 style="font-size:16px;font-weight:700;color:#2c3e50;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-bell" style="color:var(--primary);"></i> Send Notification
            </h2>
        </div>
        <?php if(isset($_GET['notif_sent'])): ?>
        <div style="background:#eafaf1;color:#1e8449;padding:10px 14px;border-radius:8px;border-left:4px solid #27ae60;font-size:13px;font-weight:600;margin-bottom:14px;">
            <i class="fas fa-check-circle"></i> Notification sent successfully to <?php echo ucfirst(htmlspecialchars($_GET['notif_sent'])); ?> users.
        </div>
        <?php endif; ?>
        <form method="POST" action="send_notification.php" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:5px;">Target Role</label>
                <select name="role" style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;outline:none;">
                    <option value="all">All Users</option>
                    <option value="vendor">Vendors Only</option>
                    <option value="farmer">Farmers Only</option>
                    <option value="customer">Customers Only</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:5px;">Type</label>
                <select name="type" style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;outline:none;">
                    <option value="info">ℹ️ Info</option>
                    <option value="success">✅ Success</option>
                    <option value="warning">⚠️ Warning</option>
                    <option value="danger">🚨 Alert / Danger</option>
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:5px;">Title</label>
                <input type="text" name="title" placeholder="e.g. Market Closure Notice" required
                    style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;outline:none;">
            </div>
            <div style="grid-column:1/-1;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:5px;">Message</label>
                <textarea name="message" rows="3" placeholder="Write your notification message here..." required
                    style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:13px;outline:none;resize:vertical;font-family:inherit;"></textarea>
            </div>
            <div style="grid-column:1/-1;">
                <button type="submit" style="background:var(--primary);color:white;border:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-paper-plane"></i> Send Notification
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Price Update -->
    <div class="section-box">
        <p class="section-title"><i class="fas fa-bolt" style="color:var(--warning)"></i> Quick Price Update</p>
        <?php
        if (isset($_GET['error']) && $_GET['error'] === 'band')
            echo "<div style='background:#fdecea;color:#c0392b;padding:12px 16px;border-radius:8px;border-left:4px solid #e74c3c;margin-bottom:14px;font-size:13px;font-weight:600;'>❌ Min price must be lower than Max price.</div>";
        if (isset($_GET['error']) && $_GET['error'] === 'range')
            echo "<div style='background:#fdecea;color:#c0392b;padding:12px 16px;border-radius:8px;border-left:4px solid #e74c3c;margin-bottom:14px;font-size:13px;font-weight:600;'>❌ Current price must be within the min–max band.</div>";
        if (isset($_GET['success']))
            echo "<div style='background:#eafaf1;color:#1e8449;padding:12px 16px;border-radius:8px;border-left:4px solid #27ae60;margin-bottom:14px;font-size:13px;font-weight:600;'>✅ Product price updated successfully.</div>";
        ?>
        <form action="process_price.php" method="POST" enctype="multipart/form-data" class="form-grid">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" placeholder="e.g. Tomato" required>
            </div>
            <div class="form-group">
                <label>Reference Price (Rs./kg)</label>
                <input type="number" name="new_price" step="0.01" min="0.01" placeholder="e.g. 60.00" required oninput="checkBand()">
            </div>
            <div class="form-group">
                <label>Min Price (Rs./kg)</label>
                <input type="number" name="min_price" id="min_price" step="0.01" min="0.01" placeholder="e.g. 45.00" required oninput="checkBand()">
            </div>
            <div class="form-group">
                <label>Max Price (Rs./kg)</label>
                <input type="number" name="max_price" id="max_price" step="0.01" min="0.01" placeholder="e.g. 80.00" required oninput="checkBand()">
            </div>
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="product_image" accept="image/*">
            </div>
            <div class="form-group">
                <div id="band_hint" style="font-size:12px;color:#27ae60;margin-bottom:6px;min-height:18px;"></div>
                <button type="submit" name="submit" class="btn-update"><i class="fas fa-save"></i> Save Product</button>
            </div>
        </form>
    </div>

    <!-- Live Market Table -->
    <div class="section-box">
        <div class="section-header">
            <p class="section-title" style="margin:0"><i class="fas fa-table" style="color:var(--info)"></i> Live Market Index</p>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search product..." onkeyup="searchTable()">
                <i class="fas fa-search"></i>
            </div>
        </div>
        <table id="marketTable">
            <thead>
                <tr>
                    <th>Image</th><th>Product</th><th>Current Price</th><th>Last Sync</th><th style="text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM items ORDER BY id DESC");
                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $img = (!empty($row['veg_image']) && file_exists('image/'.$row['veg_image']))
                             ? 'image/'.$row['veg_image'] : 'image/default.png';
                        $time = $row['updated_at'] ? date('M d, h:i A', strtotime($row['updated_at'])) : 'N/A';
                ?>
                <tr>
                    <td><img src="<?php echo $img; ?>" class="product-img"></td>
                    <td><strong><?php echo htmlspecialchars($row['veg_name']); ?></strong></td>
                    <td><span class="price-badge">Rs. <?php echo number_format($row['price'],2); ?></span></td>
                    <td style="color:#95a5a6;font-size:12px"><?php echo $time; ?></td>
                    <td style="text-align:center">
                        <form action="delete_item.php" method="POST" onsubmit="return confirm('Delete this item?')">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#95a5a6">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function searchTable() {
    const input = document.getElementById('searchInput').value.toUpperCase();
    const rows  = document.getElementById('marketTable').getElementsByTagName('tr');
    for (let i = 1; i < rows.length; i++) {
        const td = rows[i].getElementsByTagName('td')[1];
        if (td) {
            rows[i].style.display = td.textContent.toUpperCase().includes(input) ? '' : 'none';
        }
    }
}

function checkBand() {
    var price = parseFloat(document.querySelector('[name=new_price]')?.value) || 0;
    var min   = parseFloat(document.querySelector('[name=min_price]')?.value)  || 0;
    var max   = parseFloat(document.querySelector('[name=max_price]')?.value)  || 0;
    var hint  = document.getElementById('band_hint');
    if (!hint) return;
    if (min > 0 && max > 0) {
        if (min >= max) {
            hint.style.color = '#e74c3c';
            hint.textContent = 'Min must be lower than Max';
        } else if (price > 0 && (price < min || price > max)) {
            hint.style.color = '#e74c3c';
            hint.textContent = 'Price must be between Rs.' + min + ' and Rs.' + max;
        } else if (price >= min && price <= max) {
            hint.style.color = '#27ae60';
            hint.textContent = 'Valid band: Rs.' + min + ' - Rs.' + max + ' /kg';
        } else {
            hint.textContent = '';
        }
    } else {
        hint.textContent = '';
    }
}
</script>
</body>
</html>
