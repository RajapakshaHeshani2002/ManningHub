<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php'); exit();
}
include 'db.php';

// Get logged in vendor details
$uid  = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Get vendor record
$vres   = $conn->query("SELECT * FROM vendors WHERE name='" . $conn->real_escape_string($name) . "' LIMIT 1");
$vendor = $vres ? $vres->fetch_assoc() : [];

$stall     = $vendor['stall_number']  ?? 'N/A';
$regid     = $vendor['vendor_reg_id'] ?? 'N/A';
$vstatus   = $vendor['status']        ?? 'approved';

// Stats
$farmer_count  = $conn->query("SELECT COUNT(*) c FROM farmers WHERE status='approved'")->fetch_assoc()['c'];
$price_count   = $conn->query("SELECT COUNT(*) c FROM items")->fetch_assoc()['c'];

// Get vendor's own hygiene record (latest)
$hvid = $vendor['id'] ?? 0;
$hygiene_res = $conn->query("SELECT * FROM hygiene_inspection WHERE vendor_id=$hvid ORDER BY inspection_date DESC LIMIT 1");
$hygiene     = $hygiene_res ? $hygiene_res->fetch_assoc() : null;

// Get vendor's own quality records (latest 5)
$quality_res = $conn->query("SELECT * FROM quality_inspection WHERE vendor_id=$hvid ORDER BY inspection_date DESC LIMIT 5");

// Get approved farmers list
$farmers_res = $conn->query("SELECT * FROM farmers WHERE status='approved' ORDER BY name");

// Get latest prices from items table
$prices_res = $conn->query("SELECT *, veg_name AS item_name FROM items ORDER BY updated_at DESC LIMIT 20");

// Get announcements
$ann_res = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/Manninghub.css">
    <style>
        .welcome-bar {
            background: linear-gradient(135deg, #1a252f, #27ae60);
            border-radius: 14px;
            padding: 22px 28px;
            color: white;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .welcome-bar h2 { font-size: 20px; margin-bottom: 4px; }
        .welcome-bar p  { font-size: 13px; opacity: 0.85; }
        .welcome-meta   { display: flex; gap: 18px; flex-wrap: wrap; }
        .meta-item      { text-align: center; }
        .meta-item .val { font-size: 18px; font-weight: 800; }
        .meta-item .lbl { font-size: 11px; opacity: 0.8; }

        .price-item {
            display: flex; justify-content: space-between;
            align-items: center; padding: 11px 0;
            border-bottom: 1px solid #f1f2f6; font-size: 14px;
        }
        .price-item:last-child { border-bottom: none; }
        .price-item .pname { font-weight: 600; color: #2c3e50; display: flex; align-items: center; gap: 8px; }
        .price-item .pval  { font-weight: 800; color: #27ae60; font-size: 15px; }
        .price-item .punit { font-size: 11px; color: #b2bec3; }

        .farmer-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f2f6; }
        .farmer-row:last-child { border-bottom: none; }
        .farmer-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px; flex-shrink: 0;
        }
        .farmer-name { font-weight: 700; font-size: 13px; color: #2c3e50; }
        .farmer-sub  { font-size: 11px; color: #95a5a6; margin-top: 1px; }
        .farmer-crop { margin-left: auto; font-size: 12px; color: #27ae60; font-weight: 600; text-align: right; }

        .ann-item { padding: 12px 0; border-bottom: 1px solid #f1f2f6; }
        .ann-item:last-child { border-bottom: none; }
        .ann-item .ann-cat  { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #27ae60; margin-bottom: 3px; }
        .ann-item .ann-title { font-size: 14px; font-weight: 700; color: #2c3e50; margin-bottom: 2px; }
        .ann-item .ann-date  { font-size: 11px; color: #b2bec3; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media(max-width:900px) { .two-col { grid-template-columns: 1fr; } }

        .hygiene-big {
            text-align: center; padding: 20px;
        }
        .hygiene-big .hicon { font-size: 48px; margin-bottom: 8px; }
        .hygiene-big .hstatus { font-size: 22px; font-weight: 800; margin-bottom: 4px; }
        .hygiene-big .hdate  { font-size: 12px; color: #95a5a6; }
        .hygiene-big .hremarks { font-size: 13px; color: #636e72; margin-top: 10px; line-height: 1.6; }

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

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <img src="image/image1.png" alt="Logo" style="height:55px; margin-bottom:8px;">
        <h2>MANNING<span style="color:white">HUB</span></h2>
        <p>Vendor Portal</p>
    </div>
    <ul class="sidebar-menu">
        <span class="menu-label">Main Menu</span>
        <li><a href="vendor_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Prices</a></li>
        <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        <span class="menu-label">My Account</span>
        <li><a href="profile_management.php"><i class="fas fa-user-edit"></i> My Profile</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Main -->
<div class="main">

    <!-- Welcome Bar -->
    <div class="welcome-bar" style="justify-content:space-between;align-items:flex-start;">
        <div>
            <h2>👋 Welcome back, <?= htmlspecialchars($name) ?>!</h2>
            <p>Stall <?= $stall ?> &nbsp;|&nbsp; Reg ID: <?= $regid ?> &nbsp;|&nbsp; <?= date('l, F j, Y') ?></p>
        </div>

    <?php
    // ── NOTIFICATION BELL ──
    $bell_role  = 'vendor';
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

        <div class="welcome-meta">
            <div class="meta-item">
                <div class="val"><?= $farmer_count ?></div>
                <div class="lbl">Farmers Available</div>
            </div>
            <div class="meta-item">
                <div class="val"><?= $price_count ?></div>
                <div class="lbl">Products Listed</div>
            </div>
            <div class="meta-item">
                <div class="val">
                    <?php
                    $badge_map = ['approved'=>'✅','pending'=>'⏳','suspended'=>'🚫','rejected'=>'❌'];
                    echo $badge_map[$vstatus] ?? '✅';
                    ?>
                </div>
                <div class="lbl">Account Status</div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="summary-grid" style="grid-template-columns:repeat(3,1fr); margin-bottom:24px;">
        <div class="sum-card sum-green">
            <i class="fas fa-seedling" style="color:#27ae60;"></i>
            <div>
                <div class="sum-num"><?= $farmer_count ?></div>
                <div class="sum-lbl">Approved Farmers</div>
            </div>
        </div>
        <div class="sum-card sum-blue">
            <i class="fas fa-tags" style="color:#3498db;"></i>
            <div>
                <div class="sum-num"><?= $price_count ?></div>
                <div class="sum-lbl">Products on Market</div>
            </div>
        </div>
        <div class="sum-card sum-yellow">
            <i class="fas fa-clipboard-check" style="color:#f39c12;"></i>
            <div>
                <div class="sum-num"><?= $hygiene ? $hygiene['hygiene_status'] : 'N/A' ?></div>
                <div class="sum-lbl">My Hygiene Status</div>
            </div>
        </div>
    </div>

    <!-- Two column layout -->
    <div class="two-col">

        <!-- Live Market Prices -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-chart-line" style="color:#27ae60;"></i> Live Market Prices</span>
                <a href="live_prices.php" style="font-size:12px; color:#27ae60; text-decoration:none;">View All →</a>
            </div>
            <div class="card-body" style="padding:16px 20px;">
                <?php if ($prices_res && $prices_res->num_rows > 0):
                    while ($p = $prices_res->fetch_assoc()): ?>
                    <div class="price-item">
                        <div class="pname">
                            <?php if (!empty($p['veg_image'])): ?>
                                <img src="image/<?= htmlspecialchars($p['veg_image']) ?>"
                                     alt="<?= htmlspecialchars($p['item_name']) ?>"
                                     style="width:32px; height:32px; object-fit:cover; border-radius:6px;">
                            <?php else: ?>
                                <i class="fas fa-leaf" style="color:#27ae60; font-size:14px;"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($p['item_name']) ?>
                        </div>
                        <div>
                            <span class="pval">Rs. <?= number_format($p['price'], 2) ?></span>
                            <span class="punit"> / kg</span>
                        </div>
                    </div>
                <?php endwhile;
                else: ?>
                    <div class="empty-state" style="padding:30px;">
                        <i class="fas fa-tags" style="font-size:32px; color:#ddd; margin-bottom:8px; display:block;"></i>
                        <p style="color:#b2bec3; font-size:13px;">No prices available yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Farmers -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-tractor" style="color:#27ae60;"></i> Available Farmers</span>
                <span style="font-size:12px; color:#95a5a6;"><?= $farmer_count ?> registered</span>
            </div>
            <div class="card-body" style="padding:16px 20px; max-height:380px; overflow-y:auto;">
                <?php if ($farmers_res && $farmers_res->num_rows > 0):
                    while ($f = $farmers_res->fetch_assoc()):
                        $initials = strtoupper(substr($f['name'], 0, 1));
                ?>
                    <div class="farmer-row">
                        <div class="farmer-avatar"><?= $initials ?></div>
                        <div>
                            <div class="farmer-name"><?= htmlspecialchars($f['name']) ?></div>
                            <div class="farmer-sub"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($f['location']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($f['phone']) ?></div>
                        </div>
                        <div class="farmer-crop"><?= htmlspecialchars($f['crop']) ?></div>
                    </div>
                <?php endwhile;
                else: ?>
                    <div class="empty-state" style="padding:30px;">
                        <p style="color:#b2bec3; font-size:13px;">No farmers available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Second row -->
    <div class="two-col">

        <!-- My Hygiene Status -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-hand-sparkles" style="color:#3498db;"></i> My Hygiene Inspection</span>
            </div>
            <div class="card-body">
                <?php if ($hygiene):
                    $icons = ['Pass'=>'✅','Fail'=>'❌','Warning'=>'⚠️','Under Review'=>'🔍'];
                    $colors = ['Pass'=>'#27ae60','Fail'=>'#e74c3c','Warning'=>'#f39c12','Under Review'=>'#3498db'];
                    $icon  = $icons[$hygiene['hygiene_status']]  ?? '📋';
                    $color = $colors[$hygiene['hygiene_status']] ?? '#636e72';
                ?>
                    <div class="hygiene-big">
                        <div class="hicon"><?= $icon ?></div>
                        <div class="hstatus" style="color:<?= $color ?>"><?= $hygiene['hygiene_status'] ?></div>
                        <div class="hdate">Last inspected: <?= date('F j, Y', strtotime($hygiene['inspection_date'])) ?></div>
                        <div class="hremarks">"<?= htmlspecialchars($hygiene['remarks']) ?>"</div>
                        <div style="margin-top:10px; font-size:12px; color:#95a5a6;">Inspected by: <?= $hygiene['inspected_by'] ?></div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <p>No inspection record yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Quality Inspection -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-star" style="color:#f39c12;"></i> My Quality Records</span>
            </div>
            <div class="card-body" style="padding:16px 20px;">
                <?php if ($quality_res && $quality_res->num_rows > 0):
                    $qcolors = ['Excellent'=>'badge-excellent','Good'=>'badge-good','Average'=>'badge-average','Poor'=>'badge-poor','Rejected'=>'badge-fail'];
                    while ($q = $quality_res->fetch_assoc()):
                        $qclass = $qcolors[$q['quality_status']] ?? 'badge-review';
                ?>
                    <div class="price-item">
                        <div class="pname">
                            <i class="fas fa-leaf" style="color:#27ae60; font-size:12px;"></i>
                            <?= htmlspecialchars($q['product_name']) ?>
                            <span style="font-size:11px; color:#95a5a6;"><?= date('M j', strtotime($q['inspection_date'])) ?></span>
                        </div>
                        <span class="badge <?= $qclass ?>"><?= $q['quality_status'] ?></span>
                    </div>
                <?php endwhile;
                else: ?>
                    <div class="empty-state" style="padding:30px;">
                        <i class="fas fa-star" style="font-size:32px; color:#ddd; margin-bottom:8px; display:block;"></i>
                        <p style="color:#b2bec3; font-size:13px;">No quality records yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Announcements -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-bullhorn" style="color:#e67e22;"></i> Latest Announcements</span>
            <a href="announcements.php" style="font-size:12px; color:#27ae60; text-decoration:none;">View All →</a>
        </div>
        <div class="card-body" style="padding:16px 20px;">
            <?php if ($ann_res && $ann_res->num_rows > 0):
                while ($a = $ann_res->fetch_assoc()): ?>
                <div class="ann-item">
                    <div class="ann-cat"><?= htmlspecialchars($a['category']) ?></div>
                    <div class="ann-title"><?= htmlspecialchars($a['title']) ?></div>
                    <div class="ann-date"><i class="far fa-clock"></i> <?= date('F j, Y', strtotime($a['created_at'])) ?></div>
                </div>
            <?php endwhile;
            else: ?>
                <p style="color:#b2bec3; font-size:13px; text-align:center; padding:20px;">No announcements yet.</p>
            <?php endif; ?>
        </div>
    </div>

</div><!-- end main -->
</body>
</html>
