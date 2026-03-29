<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}
include 'db.php';

// Mark as read / resolved
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id  = intval($_GET['id']);
    $act = $_GET['action'];
    if ($act === 'read')     $conn->query("UPDATE feedback SET status='read'     WHERE id=$id");
    if ($act === 'resolved') $conn->query("UPDATE feedback SET status='resolved' WHERE id=$id");
    if ($act === 'delete')   $conn->query("DELETE FROM feedback WHERE id=$id");
    header('Location: admin_feedback.php'); exit();
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = '';
if ($filter === 'unread')    $where = "WHERE status='unread'";
if ($filter === 'complaint') $where = "WHERE type='complaint'";
if ($filter === 'feedback')  $where = "WHERE type='feedback'";
if ($filter === 'resolved')  $where = "WHERE status='resolved'";

$all_res       = $conn->query("SELECT COUNT(*) c FROM feedback");
$unread_res    = $conn->query("SELECT COUNT(*) c FROM feedback WHERE status='unread'");
$complaint_res = $conn->query("SELECT COUNT(*) c FROM feedback WHERE type='complaint'");
$resolved_res  = $conn->query("SELECT COUNT(*) c FROM feedback WHERE status='resolved'");

$total     = $all_res       ? $all_res->fetch_assoc()['c']       : 0;
$unread    = $unread_res    ? $unread_res->fetch_assoc()['c']    : 0;
$complaints= $complaint_res ? $complaint_res->fetch_assoc()['c'] : 0;
$resolved  = $resolved_res  ? $resolved_res->fetch_assoc()['c']  : 0;

$rows = $conn->query("SELECT * FROM feedback $where ORDER BY FIELD(status,'unread','read','resolved'), created_at DESC");

// View single
$view = null;
if (isset($_GET['view'])) {
    $vid  = intval($_GET['view']);
    $vres = $conn->query("SELECT * FROM feedback WHERE id=$vid LIMIT 1");
    if ($vres && $vres->num_rows > 0) {
        $view = $vres->fetch_assoc();
        // Mark as read automatically
        if ($view['status'] === 'unread')
            $conn->query("UPDATE feedback SET status='read' WHERE id=$vid");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#27ae60; --primary2:#2ecc71; --dark:#0d1b2a; --sidebar:#1a2535; --danger:#e74c3c; --warn:#e67e22; --info:#3498db; --bg:#f0f4f0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); display:flex; }

        .sidebar { width:260px; min-height:100vh; background:var(--sidebar); color:white; position:fixed; }
        .sidebar-logo { padding:28px 20px 20px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-logo h2 { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--primary2); }
        .sidebar-logo p  { font-size:11px; color:rgba(255,255,255,.4); margin-top:3px; }
        .sidebar-menu { list-style:none; padding:16px 10px; }
        .sidebar-menu li a { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.65); text-decoration:none; padding:11px 14px; border-radius:10px; font-size:13px; font-weight:500; transition:.2s; margin-bottom:3px; }
        .sidebar-menu li a:hover { background:rgba(255,255,255,.08); color:white; }
        .sidebar-menu li a.active { background:var(--primary); color:white; }
        .sidebar-menu li a i { width:18px; text-align:center; }

        .main { margin-left:260px; width:calc(100% - 260px); padding:28px; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .page-head h1 { font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--dark); }
        .page-head p  { font-size:13px; color:#666; margin-top:4px; }

        /* Stats */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:24px; }
        .stat-card { background:white; border-radius:14px; padding:18px 16px; border-left:5px solid #ddd; box-shadow:0 2px 10px rgba(0,0,0,.05); cursor:pointer; transition:.2s; text-decoration:none; display:block; }
        .stat-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.1); }
        .stat-card.all     { border-left-color:var(--info); }
        .stat-card.unread  { border-left-color:var(--danger); }
        .stat-card.cmp     { border-left-color:var(--warn); }
        .stat-card.res     { border-left-color:var(--primary); }
        .stat-card.active-filter { outline:3px solid currentColor; }
        .stat-num { font-family:'Outfit',sans-serif; font-size:28px; font-weight:900; color:var(--dark); }
        .stat-lbl { font-size:12px; color:#888; margin-top:4px; }

        /* Filters */
        .filter-bar { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
        .filter-btn { padding:7px 16px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none; border:2px solid #ddd; color:#666; background:white; transition:.2s; }
        .filter-btn:hover  { border-color:var(--primary); color:var(--primary); }
        .filter-btn.active { background:var(--primary); border-color:var(--primary); color:white; }

        /* Table */
        .section-box { background:white; border-radius:16px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.05); }
        .fb-table { width:100%; border-collapse:collapse; }
        .fb-table th { background:#f8f9fa; color:#636e72; font-size:11px; text-transform:uppercase; letter-spacing:.5px; padding:12px 14px; text-align:left; }
        .fb-table td { padding:13px 14px; border-bottom:1px solid #f1f2f6; font-size:13px; vertical-align:middle; }
        .fb-table tr:last-child td { border-bottom:none; }
        .fb-table tr:hover { background:#fafffe; }
        .fb-table tr.unread td { font-weight:600; background:#fffde7; }

        /* Badges */
        .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .b-feedback  { background:#e8f4fd; color:#2471a3; }
        .b-complaint { background:#fdecea; color:#c0392b; }
        .b-unread    { background:#fdecea; color:#c0392b; }
        .b-read      { background:#fef9ec; color:#856404; }
        .b-resolved  { background:#eafaf1; color:#1e8449; }

        .btn-view     { background:#ebf5fb; color:#2980b9; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; }
        .btn-resolve  { background:#eafaf1; color:#1e8449; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; }
        .btn-del      { background:#fdecea; color:#c0392b; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; }

        /* Modal */
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9000; justify-content:center; align-items:center; }
        .modal-bg.open { display:flex; }
        .modal { background:white; border-radius:18px; width:100%; max-width:560px; padding:32px; max-height:90vh; overflow-y:auto; }
        .modal h3 { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--dark); margin-bottom:6px; }
        .modal-meta { font-size:13px; color:#888; margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; }
        .modal-msg  { background:#f8f9fa; border-radius:10px; padding:16px; font-size:14px; color:#333; line-height:1.7; white-space:pre-wrap; margin-bottom:20px; }
        .modal-detail { font-size:13px; margin-bottom:6px; color:#555; }
        .modal-detail strong { color:var(--dark); }
        .modal-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn-modal-resolve { background:var(--primary); color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:700; cursor:pointer; text-decoration:none; font-size:13px; }
        .btn-modal-close   { background:#f0f0f0; color:#555; border:none; padding:10px 20px; border-radius:8px; font-weight:700; cursor:pointer; font-size:13px; }

        .empty-state { text-align:center; padding:60px; color:#aaa; }
        .empty-state i { font-size:48px; display:block; margin-bottom:14px; }
    </style>
</head>
<body>

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
        <li><a href="stock_management.php"><i class="fas fa-boxes"></i> Stock</a></li>
        <li><a href="products_list.php"><i class="fas fa-seedling"></i> Products</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Prices</a></li>
        <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        <li><a href="admin_feedback.php" class="active"><i class="fas fa-comments"></i> Feedback</a></li>
        <li style="margin-top:20px;">
            <a href="logout.php" style="color:#e74c3c !important;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</div>

<div class="main">

    <div class="page-head">
        <div>
            <h1><i class="fas fa-comments" style="color:var(--primary);margin-right:10px;"></i>Feedback & Complaints</h1>
            <p>Review customer, vendor and farmer submissions</p>
        </div>
        <a href="feedback.php" target="_blank" style="font-size:13px;color:var(--primary);text-decoration:none;font-weight:600;">
            <i class="fas fa-external-link-alt"></i> View Public Page
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <a href="?filter=all" class="stat-card all">
            <div class="stat-num"><?php echo $total; ?></div>
            <div class="stat-lbl">Total Submissions</div>
        </a>
        <a href="?filter=unread" class="stat-card unread">
            <div class="stat-num"><?php echo $unread; ?></div>
            <div class="stat-lbl">Unread</div>
        </a>
        <a href="?filter=complaint" class="stat-card cmp">
            <div class="stat-num"><?php echo $complaints; ?></div>
            <div class="stat-lbl">Complaints</div>
        </a>
        <a href="?filter=resolved" class="stat-card res">
            <div class="stat-num"><?php echo $resolved; ?></div>
            <div class="stat-lbl">Resolved</div>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <a href="?filter=all"       class="filter-btn <?php echo $filter==='all'       ?'active':''; ?>">All</a>
        <a href="?filter=unread"    class="filter-btn <?php echo $filter==='unread'    ?'active':''; ?>">Unread <?php if($unread>0) echo "<span style='color:var(--danger)'>($unread)</span>"; ?></a>
        <a href="?filter=complaint" class="filter-btn <?php echo $filter==='complaint' ?'active':''; ?>">Complaints</a>
        <a href="?filter=feedback"  class="filter-btn <?php echo $filter==='feedback'  ?'active':''; ?>">Feedback</a>
        <a href="?filter=resolved"  class="filter-btn <?php echo $filter==='resolved'  ?'active':''; ?>">Resolved</a>
    </div>

    <!-- Table -->
    <div class="section-box">
        <div style="overflow-x:auto;">
        <?php if ($rows && $rows->num_rows > 0): ?>
        <table class="fb-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = $rows->fetch_assoc()):
                $type_badge  = $r['type'] === 'complaint' ? 'b-complaint' : 'b-feedback';
                $type_label  = $r['type'] === 'complaint' ? '<i class="fas fa-flag"></i> Complaint' : '<i class="fas fa-lightbulb"></i> Feedback';
                $stat_badge  = ['unread'=>'b-unread','read'=>'b-read','resolved'=>'b-resolved'][$r['status']] ?? 'b-read';
                $is_unread   = $r['status'] === 'unread';
            ?>
                <tr class="<?php echo $is_unread ? 'unread' : ''; ?>">
                    <td><span class="badge <?php echo $type_badge; ?>"><?php echo $type_label; ?></span></td>
                    <td>
                        <strong><?php echo htmlspecialchars($r['user_name'] ?? 'Anonymous'); ?></strong><br>
                        <span style="font-size:11px;color:#aaa;"><?php echo htmlspecialchars($r['user_role'] ?? ''); ?></span>
                    </td>
                    <td style="max-width:220px;">
                        <?php if ($is_unread): ?><i class="fas fa-circle" style="color:var(--danger);font-size:8px;margin-right:5px;"></i><?php endif; ?>
                        <?php echo htmlspecialchars(substr($r['subject'] ?? 'No subject', 0, 60)); ?>
                    </td>
                    <td style="font-size:12px;color:#888;white-space:nowrap;">
                        <?php echo date('M d, Y', strtotime($r['created_at'])); ?><br>
                        <?php echo date('h:i A', strtotime($r['created_at'])); ?>
                    </td>
                    <td><span class="badge <?php echo $stat_badge; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                    <td style="text-align:center;white-space:nowrap;">
                        <a href="?view=<?php echo $r['id']; ?>&filter=<?php echo $filter; ?>" class="btn-view"><i class="fas fa-eye"></i> View</a>
                        <?php if ($r['status'] !== 'resolved'): ?>
                        <a href="?action=resolved&id=<?php echo $r['id']; ?>&filter=<?php echo $filter; ?>" class="btn-resolve" onclick="return confirm('Mark as resolved?')"><i class="fas fa-check"></i></a>
                        <?php endif; ?>
                        <a href="?action=delete&id=<?php echo $r['id']; ?>&filter=<?php echo $filter; ?>" class="btn-del" onclick="return confirm('Delete this entry?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                No submissions found<?php echo $filter !== 'all' ? " for filter: $filter" : ''; ?>.
            </div>
        <?php endif; ?>
        </div>
    </div>

</div>

<!-- View Modal -->
<?php if ($view): ?>
<div class="modal-bg open" id="viewModal">
    <div class="modal">
        <div style="margin-bottom:6px;">
            <span class="badge <?php echo $view['type']==='complaint' ? 'b-complaint' : 'b-feedback'; ?>" style="font-size:13px;padding:5px 14px;">
                <?php echo $view['type'] === 'complaint' ? '⚑ Complaint' : '💡 Feedback'; ?>
            </span>
            &nbsp;
            <span class="badge <?php echo ['unread'=>'b-unread','read'=>'b-read','resolved'=>'b-resolved'][$view['status']] ?? 'b-read'; ?>">
                <?php echo ucfirst($view['status']); ?>
            </span>
        </div>
        <h3><?php echo htmlspecialchars($view['subject'] ?? 'No subject'); ?></h3>
        <div class="modal-meta">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($view['user_name'] ?? 'Anonymous'); ?></span>
            <span><i class="fas fa-tag"></i> <?php echo ucfirst($view['user_role'] ?? ''); ?></span>
            <span><i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($view['created_at'])); ?></span>
        </div>

        <?php if (!empty($view['submitter_email'])): ?>
        <div class="modal-detail"><strong>Email:</strong> <?php echo htmlspecialchars($view['submitter_email']); ?></div>
        <?php endif; ?>
        <?php if (!empty($view['related_stall'])): ?>
        <div class="modal-detail"><strong>Related Stall/Vendor:</strong> <?php echo htmlspecialchars($view['related_stall']); ?></div>
        <?php endif; ?>

        <div class="modal-msg" style="margin-top:14px;"><?php echo htmlspecialchars($view['message']); ?></div>

        <div class="modal-actions">
            <?php if ($view['status'] !== 'resolved'): ?>
            <a href="?action=resolved&id=<?php echo $view['id']; ?>" class="btn-modal-resolve"><i class="fas fa-check-circle"></i> Mark as Resolved</a>
            <?php endif; ?>
            <a href="?action=delete&id=<?php echo $view['id']; ?>" class="btn-del" onclick="return confirm('Delete?')" style="padding:10px 18px;text-decoration:none;border-radius:8px;font-size:13px;"><i class="fas fa-trash"></i> Delete</a>
            <button class="btn-modal-close" onclick="window.location='admin_feedback.php?filter=<?php echo $filter; ?>'">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
