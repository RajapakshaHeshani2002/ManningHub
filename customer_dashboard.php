<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php'); exit();
}
include 'db.php';

$name = $_SESSION['name'];

// Stats
$price_count  = $conn->query("SELECT COUNT(*) c FROM items")->fetch_assoc()['c'];
$vendor_count = $conn->query("SELECT COUNT(*) c FROM vendors WHERE status='approved'")->fetch_assoc()['c'];
$farmer_count = $conn->query("SELECT COUNT(*) c FROM farmers WHERE status='approved'")->fetch_assoc()['c'];
$ann_count    = $conn->query("SELECT COUNT(*) c FROM announcements")->fetch_assoc()['c'];

// Live prices
$prices_res = $conn->query("SELECT *, veg_name AS item_name FROM items ORDER BY updated_at DESC");

// Latest announcements
$ann_res = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");

// Handle feedback submission
$feedback_msg  = '';
$feedback_type = '';
if (isset($_POST['send_feedback'])) {
    $feedback = $conn->real_escape_string(trim($_POST['feedback']));
    $uid      = $_SESSION['user_id'] ?? 0;
    if (!empty($feedback)) {
        // Insert into feedback table if it exists, otherwise just show success
        $conn->query("INSERT IGNORE INTO feedback (user_id, message, created_at) VALUES ($uid, '$feedback', NOW())");
        $feedback_msg  = '✅ Thank you! Your feedback has been submitted successfully.';
        $feedback_type = 'success';
    } else {
        $feedback_msg  = '❌ Please write your feedback before submitting.';
        $feedback_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/Manninghub.css">
    <style>
        .welcome-bar {
            background: linear-gradient(135deg, #1a252f, #27ae60);
            border-radius: 14px; padding: 22px 28px; color: white;
            margin-bottom: 24px; display: flex;
            justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px;
        }
        .welcome-bar h2 { font-size: 20px; margin-bottom: 4px; }
        .welcome-bar p  { font-size: 13px; opacity: 0.85; }
        .welcome-meta   { display: flex; gap: 18px; flex-wrap: wrap; }
        .meta-item      { text-align: center; }
        .meta-item .val { font-size: 18px; font-weight: 800; }
        .meta-item .lbl { font-size: 11px; opacity: 0.8; }

        .market-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px; margin-bottom: 24px;
        }
        .info-tile {
            background: white; border-radius: 12px; padding: 18px;
            text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .info-tile i    { font-size: 26px; margin-bottom: 8px; display: block; }
        .info-tile .t-val { font-size: 22px; font-weight: 800; color: #2c3e50; }
        .info-tile .t-lbl { font-size: 12px; color: #95a5a6; margin-top: 2px; }

        .price-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 14px; padding: 4px;
        }
        .price-card {
            background: linear-gradient(135deg, #f0faf4, #e8f5e9);
            border-radius: 12px; padding: 16px; text-align: center;
            border: 1px solid #d5f5e3;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .price-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(39,174,96,0.15);
        }
        .price-card .veg-icon  { font-size: 28px; margin-bottom: 8px; }
        .price-card .veg-name  { font-size: 14px; font-weight: 700; color: #1a252f; margin-bottom: 6px; }
        .price-card .veg-price { font-size: 20px; font-weight: 900; color: #27ae60; }
        .price-card .veg-unit  { font-size: 11px; color: #95a5a6; }

        .two-col { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media(max-width:900px) { .two-col { grid-template-columns: 1fr; } }

        .ann-item { padding: 14px 0; border-bottom: 1px solid #f1f2f6; }
        .ann-item:last-child { border-bottom: none; }
        .ann-cat   { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #27ae60; margin-bottom: 4px; }
        .ann-title { font-size: 14px; font-weight: 700; color: #2c3e50; margin-bottom: 3px; }
        .ann-msg   { font-size: 13px; color: #636e72; line-height: 1.5; margin-bottom: 4px; }
        .ann-date  { font-size: 11px; color: #b2bec3; }
        .ann-item.urgent .ann-cat   { color: #e74c3c; }
        .ann-item.urgent .ann-title { color: #e74c3c; }

        .feedback-msg {
            padding: 12px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 600; margin-bottom: 14px;
        }
        .feedback-msg.success { background:#eafaf1; color:#1a7a3a; border-left:4px solid #27ae60; }
        .feedback-msg.error   { background:#fdecea; color:#a93226; border-left:4px solid #e74c3c; }

        .feedback-form textarea {
            width: 100%; padding: 12px 14px;
            border: 1px solid #dfe6e9; border-radius: 10px;
            font-size: 14px; font-family: inherit;
            resize: vertical; min-height: 100px;
            outline: none; transition: 0.2s;
            box-sizing: border-box;
        }
        .feedback-form textarea:focus {
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39,174,96,0.08);
        }

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
        <p>Customer Portal</p>
    </div>
    <ul class="sidebar-menu">
        <span class="menu-label">Main Menu</span>
        <li><a href="customer_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Prices</a></li>
        <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        <span class="menu-label">My Account</span>
        <li><a href="profile_management.php"><i class="fas fa-user-edit"></i> My Profile</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main">

    <!-- Welcome Bar -->
    <div class="welcome-bar" style="justify-content:space-between;align-items:flex-start;">
        <div>
            <h2>🛒 Welcome, <?= htmlspecialchars($name) ?>!</h2>
            <p>Manning Market — Sri Lanka's largest wholesale vegetable market &nbsp;|&nbsp; <?= date('l, F j, Y') ?></p>
        </div>

    <?php
    // ── NOTIFICATION BELL ──
    $bell_role  = 'customer';
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
                <div class="val"><?= $price_count ?></div>
                <div class="lbl">Products</div>
            </div>
            <div class="meta-item">
                <div class="val"><?= $vendor_count ?></div>
                <div class="lbl">Vendors</div>
            </div>
            <div class="meta-item">
                <div class="val"><?= $farmer_count ?></div>
                <div class="lbl">Farmers</div>
            </div>
        </div>
    </div>

    <!-- Market Info Tiles -->
    <div class="market-info-grid">
        <div class="info-tile">
            <i class="fas fa-tags" style="color:#27ae60;"></i>
            <div class="t-val"><?= $price_count ?></div>
            <div class="t-lbl">Products Listed</div>
        </div>
        <div class="info-tile">
            <i class="fas fa-store" style="color:#3498db;"></i>
            <div class="t-val"><?= $vendor_count ?></div>
            <div class="t-lbl">Active Vendors</div>
        </div>
        <div class="info-tile">
            <i class="fas fa-tractor" style="color:#27ae60;"></i>
            <div class="t-val"><?= $farmer_count ?></div>
            <div class="t-lbl">Registered Farmers</div>
        </div>
        <div class="info-tile">
            <i class="fas fa-bell" style="color:#f39c12;"></i>
            <div class="t-val"><?= $ann_count ?></div>
            <div class="t-lbl">Announcements</div>
        </div>
    </div>

    <!-- Real-Time Price Board -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="card-title">
                <i class="fas fa-chart-line" style="color:#27ae60;"></i>
                Real-Time Price Board
            </span>
            <a href="live_prices.php" style="font-size:12px; color:#27ae60; text-decoration:none;">
                View Full Board →
            </a>
        </div>
        <div class="card-body">
            <?php if ($prices_res && $prices_res->num_rows > 0): ?>
                <div class="price-grid">
                    <?php while ($p = $prices_res->fetch_assoc()): ?>
                    <div class="price-card">
                        <div class="veg-icon">
                            <?php if (!empty($p['veg_image'])): ?>
                                <img src="image/<?= htmlspecialchars($p['veg_image']) ?>"
                                     alt="<?= htmlspecialchars($p['item_name']) ?>"
                                     style="width:64px; height:64px; object-fit:cover; border-radius:10px;">
                            <?php else: ?>
                                <i class="fas fa-leaf" style="color:#27ae60; font-size:32px;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="veg-name"><?= htmlspecialchars($p['item_name']) ?></div>
                        <div class="veg-price">Rs. <?= number_format($p['price'], 2) ?></div>
                        <div class="veg-unit">per kilogram</div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p>No prices available yet. Check back soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Announcements + Feedback -->
    <div class="two-col">

        <!-- Announcements -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i class="fas fa-bullhorn" style="color:#e67e22;"></i>
                    Market Announcements
                </span>
                <a href="announcements.php" style="font-size:12px; color:#27ae60; text-decoration:none;">View All →</a>
            </div>
            <div class="card-body" style="padding:16px 20px;">
                <?php if ($ann_res && $ann_res->num_rows > 0):
                    while ($a = $ann_res->fetch_assoc()):
                        $is_urgent = $a['category'] === 'Urgent'; ?>
                    <div class="ann-item <?= $is_urgent ? 'urgent' : '' ?>">
                        <div class="ann-cat"><?= $is_urgent ? '🚨 ' : '📢 ' ?><?= htmlspecialchars($a['category']) ?></div>
                        <div class="ann-title"><?= htmlspecialchars($a['title']) ?></div>
                        <?php if (!empty($a['message'])): ?>
                            <div class="ann-msg">
                                <?= htmlspecialchars(substr($a['message'], 0, 120)) ?><?= strlen($a['message']) > 120 ? '...' : '' ?>
                            </div>
                        <?php endif; ?>
                        <div class="ann-date"><i class="far fa-clock"></i> <?= date('F j, Y', strtotime($a['created_at'])) ?></div>
                    </div>
                <?php endwhile;
                else: ?>
                    <div class="empty-state" style="padding:30px;">
                        <i class="fas fa-bell" style="font-size:32px; color:#ddd; display:block; margin-bottom:8px;"></i>
                        <p style="color:#b2bec3; font-size:13px;">No announcements yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feedback -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i class="fas fa-comment-dots" style="color:#8e44ad;"></i>
                    Send Feedback
                </span>
            </div>
            <div class="card-body feedback-form">
                <?php if ($feedback_msg): ?>
                    <div class="feedback-msg <?= $feedback_type ?>"><?= $feedback_msg ?></div>
                <?php endif; ?>

                <p style="font-size:13px; color:#636e72; margin-bottom:14px; line-height:1.6;">
                    Have a suggestion or complaint about Manning Market? We value your feedback.
                </p>

                <form method="POST">
                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px; font-weight:600; color:#636e72; margin-bottom:6px; display:block;">
                            Your Feedback
                        </label>
                        <textarea name="feedback"
                            placeholder="Write your feedback, suggestion, or complaint here..."
                            required></textarea>
                    </div>
                    <button type="submit" name="send_feedback" class="btn-primary" style="width:100%;">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>

                <div style="margin-top:14px; padding:10px 12px; background:#f8f9fa;
                            border-radius:8px; font-size:11px; color:#95a5a6; text-align:center;">
                    <i class="fas fa-shield-alt"></i>
                    Your feedback is confidential and reviewed by market administration.
                </div>
            </div>
        </div>

    </div>

</div><!-- end main -->
</body>
</html>
