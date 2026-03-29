<?php
session_start();
if (!isset($_SESSION['role'])) { header('Location: login.php'); exit(); }
include 'db.php';

$role = $_SESSION['role'];
$uid  = $_SESSION['user_id'] ?? 0;
$name = $_SESSION['name']    ?? 'User';

// Dashboards map
$dash_map = [
    'admin'    => 'admin_dashboard.php',
    'vendor'   => 'vendor_dashboard.php',
    'farmer'   => 'farmer_dashboard.php',
    'customer' => 'customer_dashboard.php',
];
$dash = $dash_map[$role] ?? 'index.php';

// Filter
$filter = $_GET['filter'] ?? 'all';
$type_filter = '';
if (in_array($filter, ['info','warning','success','danger'])) {
    $type_filter = "AND type='$filter'";
}
$unread_filter = ($filter === 'unread') ? "AND is_read=0" : "";

// Load notifications for this role
$notifs = $conn->query("
    SELECT * FROM notifications
    WHERE (role='$role' OR role='all')
    $type_filter $unread_filter
    ORDER BY is_read ASC, created_at DESC
");

// Counts
$total_r  = $conn->query("SELECT COUNT(*) c FROM notifications WHERE role='$role' OR role='all'");
$unread_r = $conn->query("SELECT COUNT(*) c FROM notifications WHERE (role='$role' OR role='all') AND is_read=0");
$total    = $total_r  ? $total_r->fetch_assoc()['c']  : 0;
$unread   = $unread_r ? $unread_r->fetch_assoc()['c'] : 0;

// Role-specific colours
$role_colors = [
    'admin'    => '#e74c3c',
    'vendor'   => '#27ae60',
    'farmer'   => '#3498db',
    'customer' => '#8e44ad',
];
$rc = $role_colors[$role] ?? '#27ae60';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#27ae60; --rc:<?php echo $rc; ?>; --dark:#0d1b2a; --sidebar:#1a2535; --bg:#f0f4f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); display:flex; }

        /* SIDEBAR */
        .sidebar { width:240px; min-height:100vh; background:var(--sidebar); color:white; position:fixed; top:0; left:0; }
        .sidebar-logo { padding:24px 20px 18px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-logo h2 { font-family:'Outfit',sans-serif; font-size:18px; font-weight:800; color:var(--rc); }
        .sidebar-logo p  { font-size:11px; color:rgba(255,255,255,.4); margin-top:3px; }
        .sidebar-menu { list-style:none; padding:14px 10px; }
        .sidebar-menu li a { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.65); text-decoration:none; padding:10px 14px; border-radius:10px; font-size:13px; font-weight:500; transition:.2s; margin-bottom:3px; }
        .sidebar-menu li a:hover  { background:rgba(255,255,255,.08); color:white; }
        .sidebar-menu li a.active { background:var(--rc); color:white; }
        .sidebar-menu li a i { width:16px; text-align:center; }

        /* MAIN */
        .main { margin-left:240px; width:calc(100% - 240px); padding:28px; }
        .page-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .page-head h1 { font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--dark); display:flex; align-items:center; gap:10px; }
        .page-head p  { font-size:13px; color:#888; margin-top:4px; }

        /* STATS */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-bottom:22px; }
        .stat-card { background:white; border-radius:12px; padding:16px; border-left:4px solid #ddd; box-shadow:0 2px 8px rgba(0,0,0,.05); }
        .stat-card.s-all  { border-left-color:var(--rc); }
        .stat-card.s-unr  { border-left-color:#e74c3c; }
        .stat-num { font-family:'Outfit',sans-serif; font-size:26px; font-weight:900; color:var(--dark); }
        .stat-lbl { font-size:12px; color:#888; margin-top:2px; }

        /* FILTER BAR */
        .filter-bar { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
        .filter-btn { padding:7px 16px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none; border:1.5px solid #ddd; color:#666; background:white; transition:.2s; cursor:pointer; }
        .filter-btn:hover  { border-color:var(--rc); color:var(--rc); }
        .filter-btn.active { background:var(--rc); border-color:var(--rc); color:white; }
        .mark-all-btn { margin-left:auto; background:var(--dark); color:white; border:none; padding:8px 18px; border-radius:20px; font-size:13px; font-weight:600; cursor:pointer; transition:.2s; }
        .mark-all-btn:hover { opacity:.85; }

        /* NOTIFICATIONS LIST */
        .notif-list { display:flex; flex-direction:column; gap:10px; }
        .notif-item { background:white; border-radius:14px; padding:18px 20px; box-shadow:0 2px 10px rgba(0,0,0,.05); display:flex; gap:16px; align-items:flex-start; transition:.2s; border-left:4px solid transparent; cursor:pointer; }
        .notif-item:hover { box-shadow:0 4px 20px rgba(0,0,0,.1); transform:translateY(-1px); }
        .notif-item.unread { border-left-color:var(--rc); background:#fefffe; }
        .notif-item.n-info    { border-left-color:#3498db; }
        .notif-item.n-warning { border-left-color:#f39c12; }
        .notif-item.n-success { border-left-color:#27ae60; }
        .notif-item.n-danger  { border-left-color:#e74c3c; }

        .notif-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
        .ni-info    { background:#e8f4fd; color:#3498db; }
        .ni-warning { background:#fef9ec; color:#f39c12; }
        .ni-success { background:#eafaf1; color:#27ae60; }
        .ni-danger  { background:#fdecea; color:#e74c3c; }

        .notif-body { flex:1; }
        .notif-title { font-weight:700; font-size:15px; color:var(--dark); margin-bottom:4px; display:flex; align-items:center; gap:8px; }
        .notif-msg   { font-size:13px; color:#636e72; line-height:1.6; }
        .notif-time  { font-size:11px; color:#b2bec3; margin-top:6px; }
        .unread-dot  { width:8px; height:8px; border-radius:50%; background:var(--rc); flex-shrink:0; margin-top:6px; }

        .empty-state { text-align:center; padding:60px 20px; color:#bbb; }
        .empty-state i { font-size:52px; display:block; margin-bottom:16px; }
        .empty-state p { font-size:15px; }

        /* Role badge in top */
        .role-badge { display:inline-block; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; background:var(--rc); color:white; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h2>ManningHub</h2>
        <p><?php echo ucfirst($role); ?> Portal</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="<?php echo $dash; ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Prices</a></li>
        <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
        <li><a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a></li>
        <li style="margin-top:20px;"><a href="logout.php" style="color:#e74c3c !important;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Main -->
<div class="main">
    <div class="page-head">
        <div>
            <h1><i class="fas fa-bell" style="color:var(--rc);"></i> Notifications</h1>
            <p>
                <span class="role-badge"><?php echo ucfirst($role); ?></span>
                &nbsp; Logged in as <?php echo htmlspecialchars($name); ?>
            </p>
        </div>
        <a href="<?php echo $dash; ?>" style="font-size:13px;color:var(--rc);text-decoration:none;font-weight:600;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card s-all">
            <div class="stat-num"><?php echo $total; ?></div>
            <div class="stat-lbl">Total</div>
        </div>
        <div class="stat-card s-unr">
            <div class="stat-num"><?php echo $unread; ?></div>
            <div class="stat-lbl">Unread</div>
        </div>
        <div class="stat-card" style="border-left-color:#27ae60;">
            <div class="stat-num"><?php echo $total - $unread; ?></div>
            <div class="stat-lbl">Read</div>
        </div>
    </div>

    <!-- Filter + Mark All -->
    <div class="filter-bar">
        <a href="?filter=all"     class="filter-btn <?php echo $filter==='all'     ?'active':''; ?>">All</a>
        <a href="?filter=unread"  class="filter-btn <?php echo $filter==='unread'  ?'active':''; ?>">Unread <?php if($unread>0) echo "<span style='color:inherit;font-size:11px;'>($unread)</span>"; ?></a>
        <a href="?filter=info"    class="filter-btn <?php echo $filter==='info'    ?'active':''; ?>">ℹ️ Info</a>
        <a href="?filter=warning" class="filter-btn <?php echo $filter==='warning' ?'active':''; ?>">⚠️ Warning</a>
        <a href="?filter=success" class="filter-btn <?php echo $filter==='success' ?'active':''; ?>">✅ Success</a>
        <a href="?filter=danger"  class="filter-btn <?php echo $filter==='danger'  ?'active':''; ?>">🚨 Alert</a>
        <?php if ($unread > 0): ?>
        <button class="mark-all-btn" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
        <?php endif; ?>
    </div>

    <!-- Notifications -->
    <div class="notif-list" id="notifList">
    <?php
    $icons = [
        'info'    => ['ni-info',    'fa-info-circle'],
        'warning' => ['ni-warning', 'fa-exclamation-triangle'],
        'success' => ['ni-success', 'fa-check-circle'],
        'danger'  => ['ni-danger',  'fa-exclamation-circle'],
    ];

    function timeAgo($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)     return 'Just now';
        if ($diff < 3600)   return round($diff/60)   . ' min ago';
        if ($diff < 86400)  return round($diff/3600)  . ' hr ago';
        if ($diff < 604800) return round($diff/86400) . ' day' . (round($diff/86400)>1?'s':'') . ' ago';
        return date('M d, Y', strtotime($datetime));
    }

    if ($notifs && $notifs->num_rows > 0):
        while ($n = $notifs->fetch_assoc()):
            $ic      = $icons[$n['type']] ?? ['ni-info','fa-bell'];
            $unread  = !$n['is_read'];
            $n_class = 'n-'.$n['type'] . ($unread ? ' unread' : '');
    ?>
        <div class="notif-item <?php echo $n_class; ?>" id="notif-<?php echo $n['id']; ?>"
             onclick="markOneRead(<?php echo $n['id']; ?>, this)">
            <div class="notif-icon <?php echo $ic[0]; ?>">
                <i class="fas <?php echo $ic[1]; ?>"></i>
            </div>
            <div class="notif-body">
                <div class="notif-title">
                    <?php echo htmlspecialchars($n['title']); ?>
                    <?php if ($unread): ?><span style="font-size:10px;font-weight:600;background:var(--rc);color:white;padding:2px 8px;border-radius:10px;">NEW</span><?php endif; ?>
                </div>
                <div class="notif-msg"><?php echo htmlspecialchars($n['message']); ?></div>
                <div class="notif-time">
                    <i class="fas fa-clock"></i> <?php echo timeAgo($n['created_at']); ?>
                    &nbsp;·&nbsp;
                    <span style="text-transform:capitalize;"><?php echo $n['role'] === 'all' ? 'All users' : ucfirst($n['role']).'s only'; ?></span>
                </div>
            </div>
            <?php if ($unread): ?><div class="unread-dot"></div><?php endif; ?>
        </div>
    <?php
        endwhile;
    else:
    ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <p>No notifications<?php echo $filter !== 'all' ? " for filter: $filter" : ''; ?>.</p>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
function markAllRead() {
    fetch('mark_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_all_read'
    }).then(() => window.location.reload());
}

function markOneRead(id, el) {
    if (el.classList.contains('unread')) {
        fetch('mark_read.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=mark_one_read&id=' + id
        });
        el.classList.remove('unread');
        const dot = el.querySelector('.unread-dot');
        if (dot) dot.remove();
        const badge = el.querySelector('.notif-title span[style*="background"]');
        if (badge) badge.remove();
    }
}
</script>
</body>
</html>
