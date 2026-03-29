<?php session_start(); ?>
<?php
include 'db.php';

$items_res  = $conn->query("SELECT * FROM items ORDER BY veg_name ASC");
$ticker_res = $conn->query("SELECT veg_name, price, min_price, max_price FROM items ORDER BY veg_name ASC");

// Stats
$total_items   = $conn->query("SELECT COUNT(*) c FROM items")->fetch_assoc()['c'];
$total_vendors = $conn->query("SELECT COUNT(*) c FROM vendors WHERE status='approved'")->fetch_assoc()['c'];
$settings_res  = $conn->query("SELECT * FROM system_settings WHERE id=1");
$settings      = $settings_res ? $settings_res->fetch_assoc() : [];
$open_time     = isset($settings['open_time'])  ? date('h:i A', strtotime($settings['open_time']))  : '4:00 AM';
$close_time    = isset($settings['close_time']) ? date('h:i A', strtotime($settings['close_time'])) : '10:00 PM';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Market Prices | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --green:#27ae60; --green2:#2ecc71; --dark:#0d1b2a; --red:#e74c3c; --orange:#e67e22; --light:#f4f7f4; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--light); }

        /* ── TICKER ── */
        .ticker-bar { background:var(--dark); color:#fff; padding:11px 0; font-size:12px; font-weight:600; overflow:hidden; white-space:nowrap; }
        .ticker-track { display:inline-block; animation:tick 50s linear infinite; }
        .ticker-track:hover { animation-play-state:paused; }
        @keyframes tick { 0%{transform:translateX(100vw)} 100%{transform:translateX(-100%)} }
        .t-item { display:inline-block; margin-right:36px; color:var(--green2); }
        .t-item span { color:white; margin-right:5px; font-weight:700; }

        /* ── NAV ── */
        nav { background:white; padding:12px 6%; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); }
        .nav-logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .nav-logo h2 { font-family:'Outfit',sans-serif; color:var(--green); font-size:19px; letter-spacing:2px; }
        .nav-btns { display:flex; gap:10px; }
        .btn-out { padding:8px 18px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:.2s; }
        .btn-login { color:var(--green); border:2px solid var(--green); }
        .btn-login:hover { background:var(--green); color:white; }
        .btn-reg { background:var(--green); color:white; border:2px solid var(--green); }
        .btn-reg:hover { background:#1e8449; }

        /* ── HERO ── */
        .hero { background:linear-gradient(135deg, var(--dark) 0%, #1a3a25 100%); color:white; padding:44px 6%; }
        .hero-inner { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; }
        .hero-text h1 { font-family:'Outfit',sans-serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:900; margin-bottom:8px; }
        .hero-text h1 em { font-style:normal; color:var(--green2); }
        .hero-text p { font-size:13px; opacity:.75; }
        .hero-stats { display:flex; gap:24px; flex-wrap:wrap; }
        .stat-pill { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); padding:12px 20px; border-radius:12px; text-align:center; min-width:110px; }
        .stat-pill .sv { font-family:'Outfit',sans-serif; font-size:22px; font-weight:800; color:var(--green2); }
        .stat-pill .sl { font-size:11px; color:rgba(255,255,255,.6); margin-top:2px; }

        /* ── TOOLBAR ── */
        .toolbar { background:white; padding:16px 6%; display:flex; align-items:center; gap:12px; flex-wrap:wrap; box-shadow:0 1px 6px rgba(0,0,0,.05); position:sticky; top:0; z-index:100; }
        .toolbar input { flex:1; min-width:200px; padding:10px 18px; border:1px solid #dfe6e9; border-radius:24px; font-size:13px; outline:none; }
        .toolbar input:focus { border-color:var(--green); }
        .view-btns { display:flex; gap:6px; }
        .view-btn { padding:9px 16px; border-radius:8px; border:1px solid #dfe6e9; background:white; cursor:pointer; font-size:13px; font-weight:600; color:#636e72; transition:.2s; }
        .view-btn.active,.view-btn:hover { background:var(--green); color:white; border-color:var(--green); }
        .item-ct { font-size:12px; color:#95a5a6; white-space:nowrap; }

        /* ── MAIN CONTENT ── */
        .content { padding:28px 6%; }

        /* ── CARD GRID VIEW ── */
        #cardView { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:20px; }
        .veg-card { background:white; border-radius:16px; padding:18px; text-align:center; box-shadow:0 3px 12px rgba(0,0,0,.06); border-bottom:4px solid #eee; transition:transform .25s,box-shadow .25s,border-color .25s; cursor:pointer; }
        .veg-card:hover { transform:translateY(-5px); box-shadow:0 10px 26px rgba(39,174,96,.15); border-bottom-color:var(--green); }
        .veg-card img { width:110px; height:110px; object-fit:cover; border-radius:10px; margin-bottom:10px; }
        .veg-card h3 { font-family:'Outfit',sans-serif; font-size:14px; font-weight:700; color:#2c3e50; margin-bottom:8px; }
        .live-dot { display:inline-block; background:#ffebee; color:var(--red); padding:3px 9px; border-radius:20px; font-size:10px; font-weight:800; margin-bottom:8px; animation:pulse 1.5s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
        .band-row { display:flex; align-items:center; justify-content:center; gap:5px; margin:6px 0 3px; }
        .p-min { background:#eafaf1; color:#1e8449; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700; border:1px solid #a9dfbf; }
        .p-max { background:#fdecea; color:#c0392b; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700; border:1px solid #f5b7b1; }
        .band-label { font-size:10px; color:#b2bec3; }
        /* ref-price = admin set reference price — NOT a selling price */
        .ref-price-wrap { background:#f8f9fa; border-radius:8px; padding:5px 10px; margin:8px 0 2px; display:inline-flex; align-items:center; gap:6px; }
        .ref-price-icon  { font-size:11px; color:#95a5a6; }
        .ref-price-label { font-size:10px; color:#95a5a6; font-weight:600; }
        .ref-price       { font-family:'Outfit',sans-serif; font-size:13px; font-weight:700; color:#636e72; }
        .ref-label { font-size:10px; color:#b2bec3; }
        .vendor-count { font-size:11px; color:var(--green); margin-top:6px; font-weight:600; }
        .updated { font-size:10px; color:#d2d9e0; margin-top:4px; }
        .click-hint { font-size:10px; color:#bdc3c7; margin-top:5px; }

        /* ── TABLE VIEW ── */
        #tableView { display:none; background:white; border-radius:16px; overflow:hidden; box-shadow:0 3px 12px rgba(0,0,0,.06); }
        #tableView table { width:100%; border-collapse:collapse; }
        #tableView thead th { background:#f8f9fa; color:#636e72; padding:13px 16px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
        #tableView tbody td { padding:13px 16px; border-bottom:1px solid #f1f2f6; font-size:13px; vertical-align:middle; }
        #tableView tbody tr:hover { background:#fafffe; cursor:pointer; }
        #tableView tbody tr:last-child td { border-bottom:none; }
        .tbl-img { width:44px; height:44px; object-fit:cover; border-radius:8px; }
        .tbl-name { font-weight:700; color:#2c3e50; font-size:14px; }
        .tbl-band { display:flex; align-items:center; gap:5px; }

        /* ── VENDOR MODAL ── */
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:2000; justify-content:center; align-items:center; padding:20px; }
        .modal-bg.open { display:flex; }
        .modal { background:white; border-radius:20px; width:100%; max-width:680px; max-height:90vh; overflow-y:auto; }
        .modal-head { background:linear-gradient(135deg,var(--dark),#1a3a25); color:white; padding:24px 28px; border-radius:20px 20px 0 0; display:flex; justify-content:space-between; align-items:flex-start; }
        .modal-head h2 { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; }
        .modal-head p { font-size:12px; opacity:.7; margin-top:4px; }
        .modal-close { background:none; border:none; color:white; font-size:22px; cursor:pointer; opacity:.7; padding:0 4px; }
        .modal-close:hover { opacity:1; }
        .modal-band-info { background:#f8fffe; padding:16px 28px; border-bottom:1px solid #eee; display:flex; gap:16px; align-items:center; flex-wrap:wrap; }
        .band-info-item { font-size:13px; color:#555; }
        .band-info-item strong { color:#2c3e50; }
        .modal-body { padding:20px 28px; }
        .modal-body h3 { font-family:'Outfit',sans-serif; font-size:15px; font-weight:700; color:#2c3e50; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
        .stall-grid { display:flex; flex-direction:column; gap:10px; }
        .stall-card { display:flex; align-items:center; justify-content:space-between; background:#f8f9fa; border-radius:12px; padding:14px 18px; border-left:4px solid var(--green); transition:.2s; }
        .stall-card:hover { background:#eafaf1; }
        .stall-card.lowest { border-left-color:#f39c12; background:#fffbf0; }
        .stall-left { display:flex; flex-direction:column; gap:3px; }
        .stall-name { font-weight:700; color:#2c3e50; font-size:14px; }
        .stall-num { font-size:12px; color:#636e72; }
        .stall-phone { font-size:11px; color:#b2bec3; }
        .stall-right { text-align:right; }
        .stall-price { font-family:'Outfit',sans-serif; font-size:20px; font-weight:900; color:var(--green); }
        .stall-unit { font-size:11px; color:#b2bec3; }
        .best-badge { background:#f39c12; color:white; font-size:10px; font-weight:700; padding:2px 8px; border-radius:10px; display:inline-block; margin-top:3px; }
        .no-vendors { text-align:center; padding:30px; color:#b2bec3; }
        .no-vendors i { font-size:36px; margin-bottom:10px; display:block; }

        /* ── FOOTER ── */
        footer { background:var(--dark); color:#666; text-align:center; padding:20px; font-size:12px; margin-top:30px; }
        footer a { color:var(--green); text-decoration:none; margin:0 10px; }

        @media(max-width:600px) {
            #cardView { grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); }
            .hero-stats { display:none; }
        }
    
        footer {
            background: #0d1b2a;
            color: white;
            padding: 50px 8% 24px;
            margin-top: 40px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr;
            gap: 48px;
            margin-bottom: 32px;
        }
        .footer-grid h4 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #2ecc71;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .footer-grid p  { font-size: 13px; color: #aaa; margin-bottom: 9px; line-height: 1.7; }
        .footer-grid a  { color: #aaa; text-decoration: none; transition: color 0.2s; }
        .footer-grid a:hover { color: #2ecc71; }
        .footer-logo-img { height: 55px; margin-bottom: 14px; width: auto; }
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            color: #555;
            font-size: 13px;
        }
        @media(max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
        }

        /* ── UNIFIED SITE NAVBAR ── */
        nav.site-nav {
            background: white;
            padding: 0 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: 80px;
        }
        nav.site-nav .nav-logo {
            display: flex; align-items: center; gap: 10px; text-decoration: none;
        }
        nav.site-nav .nav-logo img { height: 60px; width: auto; }
        nav.site-nav .nav-logo h2 {
            font-family:'Outfit',sans-serif; color:#27ae60;
            font-size:18px; font-weight:800; letter-spacing:1.5px;
        }
        nav.site-nav .nav-links {
            display: flex; list-style: none; gap: 28px;
        }
        nav.site-nav .nav-links a {
            text-decoration: none; color: #2c3e50;
            font-weight: 600; font-size: 14px; transition: 0.2s;
            padding-bottom: 4px; border-bottom: 2px solid transparent;
        }
        nav.site-nav .nav-links a:hover { color: #27ae60; border-bottom-color: #27ae60; }
        nav.site-nav .nav-links a.active { color: #27ae60; border-bottom-color: #27ae60; }
        nav.site-nav .nav-auth { display: flex; gap: 10px; align-items: center; }
        nav.site-nav .nav-auth a {
            padding: 8px 18px; border-radius: 8px; font-size: 13px;
            font-weight: 700; text-decoration: none; transition: 0.2s;
        }
        nav.site-nav .btn-login { color: #27ae60; border: 2px solid #27ae60; }
        nav.site-nav .btn-login:hover { background: #27ae60; color: white; }
        nav.site-nav .btn-reg { background: #27ae60; color: white; border: 2px solid #27ae60; }
        nav.site-nav .btn-reg:hover { background: #1e8449; }

        /* hamburger */
        .hamburger { display:none; flex-direction:column; gap:5px; cursor:pointer;
                     background:none; border:none; padding:4px; }
        .hamburger span { display:block; width:24px; height:2px; background:#2c3e50;
                          border-radius:2px; transition:.3s; }
        .hamburger.open span:nth-child(1) { transform:translateY(7px) rotate(45deg); }
        .hamburger.open span:nth-child(2) { opacity:0; }
        .hamburger.open span:nth-child(3) { transform:translateY(-7px) rotate(-45deg); }

        @media(max-width:768px) {
            nav.site-nav { height:65px; padding:0 5%; position:relative; }
            nav.site-nav .nav-links {
                display:none; flex-direction:column; gap:0;
                position:absolute; top:65px; left:0; right:0;
                background:white; padding:12px 0;
                box-shadow:0 6px 20px rgba(0,0,0,.1); z-index:999;
            }
            nav.site-nav .nav-links.mobile-open { display:flex; }
            nav.site-nav .nav-links li a { padding:12px 6%; display:block; border-bottom:1px solid #f0f0f0; }
            nav.site-nav .nav-links a.active, nav.site-nav .nav-links a:hover {
                border-bottom-color:#f0f0f0; background:#f8fffe;
            }
            nav.site-nav .nav-auth { display:none; }
            .hamburger { display:flex; }
        }

        /* ── UNIFIED FOOTER ── */
        footer.site-footer {
            background: #0d1b2a; color: white;
            padding: 50px 6% 24px; margin-top: 60px;
        }
        .site-footer .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px; margin-bottom: 32px;
        }
        .site-footer .footer-grid h4 {
            font-size: 13px; font-weight: 700; margin-bottom: 14px;
            color: #2ecc71; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .site-footer .footer-grid p  { font-size: 13px; color: #aaa; margin-bottom: 8px; line-height: 1.7; }
        .site-footer .footer-grid a  { color: #aaa; text-decoration: none; transition: color 0.2s; display:block; margin-bottom:7px; font-size:13px; }
        .site-footer .footer-grid a:hover { color: #2ecc71; }
        .site-footer .footer-logo-img { height: 55px; margin-bottom: 14px; width: auto; }
        .site-footer .footer-bottom {
            text-align: center; padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            color: #555; font-size: 13px;
        }
        .site-footer .footer-social { display:flex; gap:12px; margin-top:12px; }
        .site-footer .footer-social a {
            width:34px; height:34px; border-radius:8px;
            background:rgba(255,255,255,.08); display:flex;
            align-items:center; justify-content:center;
            color:#aaa; font-size:14px; margin:0;
        }
        .site-footer .footer-social a:hover { background:#27ae60; color:white; }
        @media(max-width:768px) {
            .site-footer .footer-grid { grid-template-columns:1fr 1fr; gap:28px; }
        }
        @media(max-width:480px) {
            .site-footer .footer-grid { grid-template-columns:1fr; gap:24px; }
        }

        /* ── BACK TO TOP ── */
        .back-top {
            position:fixed; bottom:28px; right:28px; width:44px; height:44px;
            background:#27ae60; color:white; border:none; border-radius:50%;
            font-size:18px; cursor:pointer; box-shadow:0 4px 14px rgba(39,174,96,.4);
            display:none; align-items:center; justify-content:center;
            z-index:9999; transition:.2s;
        }
        .back-top:hover { background:#1e8449; transform:translateY(-2px); }
        .back-top.visible { display:flex; }

    </style>
</head>
<body>

<!-- Ticker -->
<div class="ticker-bar">
    <div class="ticker-track">
        <?php if ($ticker_res && $ticker_res->num_rows > 0): while ($t = $ticker_res->fetch_assoc()):
            $band = (!empty($t['min_price']) && !empty($t['max_price']))
                ? 'Rs.' . number_format($t['min_price'],0) . '/kg – Rs.' . number_format($t['max_price'],0) . '/kg'
                : 'Rs.' . number_format($t['price'],2);
        ?>
        <span class="t-item"><span><?php echo htmlspecialchars(strtoupper($t['veg_name'])); ?></span><?php echo $band; ?> /kg</span>
        <?php endwhile; endif; ?>
    </div>
</div>

<!-- Nav -->

<nav class="site-nav">
    <a href="index.php" class="nav-logo">
        <img src="image/image1.png" alt="ManningHub Logo" onerror="this.src='https://via.placeholder.com/120x60?text=M-HUB'">
        <h2>MANNINGHUB</h2>
    </a>
    <ul class="nav-links" id="mobileMenu">
            <li><a href="index.php">Home</a></li>
            <li><a href="live_prices.php" class="active">Live Prices</a></li>
            <li><a href="announcements.php">Announcements</a></li>
            <li><a href="feedback.php">Feedback</a></li>
            <li><a href="contact.php">Contact</a></li>
    </ul>
        <div class="nav-auth">
            <?php if(isset($_SESSION['role'])): ?>
            <?php $d=['admin'=>'admin_dashboard.php','vendor'=>'vendor_dashboard.php','farmer'=>'farmer_dashboard.php','customer'=>'customer_dashboard.php'][$_SESSION['role']]??'index.php'; ?>
            <a href="<?php echo $d;?>" class="btn-login">My Dashboard</a>
            <a href="logout.php" class="btn-reg">Logout</a>
            <?php else: ?>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-reg">Register</a>
            <?php endif; ?>
        </div>
    <button class="hamburger" id="hamburger" onclick="toggleMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</nav>


<!-- Hero -->
<div class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <h1>Live Vegetable<br><em>Market Prices</em></h1>
            <p>Manning Market, Peliyagoda &nbsp;|&nbsp; Open <?php echo $open_time; ?> - <?php echo $close_time; ?> &nbsp;|&nbsp; Updated daily</p>
        </div>
        <div class="hero-stats">
            <div class="stat-pill">
                <div class="sv"><?php echo $total_items; ?></div>
                <div class="sl">Products</div>
            </div>
            <div class="stat-pill">
                <div class="sv"><?php echo $total_vendors; ?></div>
                <div class="sl">Active Vendors</div>
            </div>
            <div class="stat-pill">
                <div class="sv"><?php echo date('M j'); ?></div>
                <div class="sl">Today</div>
            </div>
        </div>
    </div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <input type="text" id="searchBox" placeholder="Search vegetable..." oninput="filterItems()">
    <div class="view-btns">
        <button class="view-btn active" id="btnCard" onclick="switchView('card')">
            <i class="fas fa-th-large"></i> Cards
        </button>
        <button class="view-btn" id="btnTable" onclick="switchView('table')">
            <i class="fas fa-table"></i> Table
        </button>
    </div>
    <span class="item-ct" id="itemCount"><?php echo $total_items; ?> products</span>
</div>

<!-- Content -->
<div class="content">


    <!-- ═══ PRICE EXPLANATION BANNER ═══ -->
    <div style="background:#fff8e1;border-left:4px solid #f39c12;padding:12px 5%;font-size:13px;color:#856404;display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
        <i class="fas fa-info-circle" style="margin-top:2px;font-size:15px;"></i>
        <span><strong>How to read prices:</strong> The <span style="background:#eafaf1;color:#1e8449;padding:1px 6px;border-radius:10px;font-weight:700;">green</span>–<span style="background:#fdecea;color:#c0392b;padding:1px 6px;border-radius:10px;font-weight:700;">red</span> band is the admin-approved price range. The small grey "admin ref. price" is a market guideline. <strong>Tap any card to see each vendor's actual stall prices.</strong></span>
    </div>

    <!-- CARD VIEW -->
    <div id="cardView">
    <?php if ($items_res && $items_res->num_rows > 0):
        $items_res->data_seek(0);
        while ($row = $items_res->fetch_assoc()):
            $img = !empty($row['veg_image']) ? 'image/'.$row['veg_image'] : 'image/image5.jpg';
            $has_band = !empty($row['min_price']) && !empty($row['max_price']);
            $vcount_r = $conn->query("SELECT COUNT(*) c FROM vendor_prices vp JOIN vendors v ON vp.vendor_id=v.id WHERE vp.item_id={$row['id']} AND v.status='approved'");
            $vcount = $vcount_r ? $vcount_r->fetch_assoc()['c'] : 0;
    ?>
        <div class="veg-card" onclick="openVendorModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['veg_name'])); ?>')"
             data-name="<?php echo strtolower(htmlspecialchars($row['veg_name'])); ?>">
            <div class="live-dot">&#9679; LIVE</div>
            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($row['veg_name']); ?>" onerror="this.src='image/image5.jpg'">
            <h3><?php echo htmlspecialchars($row['veg_name']); ?></h3>
            <?php if ($has_band): ?>
            <div class="band-row">
                <span class="p-min">Rs. <?php echo number_format($row['min_price'],0); ?>/kg</span>
                <span style="color:#bdc3c7;font-size:11px;font-weight:700;">to</span>
                <span class="p-max">Rs. <?php echo number_format($row['max_price'],0); ?>/kg</span>
            </div>
            <div class="band-label">allowed price range per 1 kg</div>
            <div class="ref-price-wrap">
                <span class="ref-price-icon"><i class="fas fa-tag"></i></span>
                <span class="ref-price-label">Admin ref. price:</span>
                <span class="ref-price">Rs. <?php echo number_format($row['price'],2); ?>/kg</span>
            </div>
            <?php else: ?>
            <div class="ref-price-wrap">
                <span class="ref-price-icon"><i class="fas fa-tag"></i></span>
                <span class="ref-price-label">Admin ref. price:</span>
                <span class="ref-price">Rs. <?php echo number_format($row['price'],2); ?>/kg</span>
            </div>
            <div class="band-label" style="color:#e74c3c;font-size:11px;"><i class="fas fa-info-circle"></i> No price band set</div>
            <?php endif; ?>
            <?php if ($vcount > 0): ?>
            <div class="vendor-count"><i class="fas fa-store" style="font-size:10px;"></i> <?php echo $vcount; ?> vendor<?php echo $vcount > 1 ? 's' : ''; ?> selling this</div>
            <?php endif; ?>
            <div class="updated">Updated: <?php echo date('M d, h:i A', strtotime($row['updated_at'])); ?></div>
            <div class="click-hint"><i class="fas fa-store" style="font-size:9px;"></i> Tap to compare vendor prices per kg &rarr;</div>
        </div>
    <?php endwhile; else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px;color:#b2bec3;"><h3>No prices available yet.</h3></div>
    <?php endif; ?>
    </div>

    <!-- TABLE VIEW -->
    <div id="tableView">
        <table id="priceTable">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price Band (per 1 kg)</th>
                    <th>Admin Ref. Price</th>
                    <th>Active Vendors</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($items_res) { $items_res->data_seek(0);
            while ($row = $items_res->fetch_assoc()):
                $img = !empty($row['veg_image']) ? 'image/'.$row['veg_image'] : 'image/image5.jpg';
                $has_band = !empty($row['min_price']) && !empty($row['max_price']);
                $vcount_r = $conn->query("SELECT COUNT(*) c FROM vendor_prices vp JOIN vendors v ON vp.vendor_id=v.id WHERE vp.item_id={$row['id']} AND v.status='approved'");
                $vcount = $vcount_r ? $vcount_r->fetch_assoc()['c'] : 0;
            ?>
            <tr onclick="openVendorModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['veg_name'])); ?>')"
                data-name="<?php echo strtolower(htmlspecialchars($row['veg_name'])); ?>">
                <td><img src="<?php echo $img; ?>" class="tbl-img" onerror="this.src='image/image5.jpg'"></td>
                <td><span class="tbl-name"><?php echo htmlspecialchars($row['veg_name']); ?></span></td>
                <td>
                    <?php if ($has_band): ?>
                    <div class="tbl-band">
                        <span class="p-min">Rs.<?php echo number_format($row['min_price'],0); ?>/kg</span>
                        <span style="color:#bdc3c7;font-size:11px;">–</span>
                        <span class="p-max">Rs.<?php echo number_format($row['max_price'],0); ?>/kg</span>
                    </div>
                    <?php else: ?><span style="color:#bbb;font-size:12px;">Not set</span><?php endif; ?>
                </td>
                <td>
                    <span style="background:#f0f0f0;color:#636e72;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;">
                        Rs. <?php echo number_format($row['price'],2); ?>
                    </span>
                    <div style="font-size:10px;color:#b2bec3;margin-top:2px;">admin ref.</div>
                </td>
                <td><?php echo $vcount > 0 ? "<span style='color:var(--green);font-weight:700;'>$vcount stall".($vcount>1?'s':'')."</span>" : "<span style='color:#ccc;font-size:12px;'>—</span>"; ?></td>
                <td style="color:#b2bec3;font-size:12px;"><?php echo date('M d, h:i A', strtotime($row['updated_at'])); ?></td>
            </tr>
            <?php endwhile; } ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Vendor Modal -->
<div class="modal-bg" id="vendorModal">
    <div class="modal">
        <div class="modal-head">
            <div>
                <h2 id="modalTitle">Vendor Stalls</h2>
                <p id="modalSub">Manning Market, Peliyagoda</p>
            </div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-band-info" id="modalBandInfo"></div>
        <div class="modal-body">
            <h3><i class="fas fa-store" style="color:var(--green);"></i> Stalls selling this product today</h3>
            <div class="stall-grid" id="stallGrid">
                <div style="text-align:center;padding:20px;color:#ccc;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->


<script>
function switchView(v) {
    document.getElementById('cardView').style.display  = v === 'card'  ? 'grid' : 'none';
    document.getElementById('tableView').style.display = v === 'table' ? 'block' : 'none';
    document.getElementById('btnCard').classList.toggle('active', v === 'card');
    document.getElementById('btnTable').classList.toggle('active', v === 'table');
}

function filterItems() {
    const q = document.getElementById('searchBox').value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('.veg-card').forEach(c => {
        const m = c.dataset.name.includes(q);
        c.style.display = m ? '' : 'none';
        if (m) vis++;
    });
    document.querySelectorAll('#priceTable tbody tr').forEach(r => {
        const m = r.dataset.name.includes(q);
        r.style.display = m ? '' : 'none';
    });
    document.getElementById('itemCount').textContent = vis + ' products';
}

function openVendorModal(itemId, itemName) {
    document.getElementById('vendorModal').classList.add('open');
    document.getElementById('modalTitle').textContent = itemName;
    document.getElementById('stallGrid').innerHTML = '<div style="text-align:center;padding:24px;color:#ccc;"><i class="fas fa-spinner fa-spin"></i> Loading stalls...</div>';
    document.getElementById('modalBandInfo').innerHTML = '';

    fetch('get_vendor_prices.php?item_id=' + itemId)
        .then(r => r.json())
        .then(data => {
            // Band info
            if (data.band) {
                document.getElementById('modalBandInfo').innerHTML =
                    '<div class="band-info-item">📊 <strong>Admin price band:</strong> Rs.' + data.band.min + '/kg – Rs.' + data.band.max + '/kg &nbsp;<span style="font-size:11px;color:#888;">(allowed range)</span></div>' +
                    '<div class="band-info-item">🏷️ <strong>Admin ref. price:</strong> Rs.' + data.band.ref + ' per kg &nbsp;<span style="font-size:11px;color:#888;">(market guideline)</span></div>' +
                    '<div class="band-info-item" style="color:#27ae60;font-weight:600;"><i class="fas fa-store"></i> Tap a vendor stall below to see their actual per-kg selling price</div>';
            }
            // Stalls
            if (data.vendors && data.vendors.length > 0) {
                let html = '';
                data.vendors.forEach((v, i) => {
                    const isLowest = i === 0;
                    html += `<div class="stall-card ${isLowest ? 'lowest' : ''}">
                        <div class="stall-left">
                            <span class="stall-name">${v.name}</span>
                            <span class="stall-num"><i class="fas fa-store" style="font-size:10px;color:#27ae60;margin-right:4px;"></i>Stall ${v.stall}</span>
                            <span class="stall-phone"><i class="fas fa-phone" style="font-size:10px;margin-right:4px;"></i>${v.phone}</span>
                        </div>
                        <div class="stall-right">
                            <div class="stall-price">Rs. ${v.price}</div>
                            <div class="stall-unit">per 1 kg &nbsp;<span style="font-size:9px;background:#eafaf1;color:#1e8449;padding:1px 5px;border-radius:6px;font-weight:700;">VENDOR PRICE</span></div>
                            ${isLowest ? '<span class="best-badge">Lowest Price</span>' : ''}
                        </div>
                    </div>`;
                });
                document.getElementById('stallGrid').innerHTML = html;
            } else {
                document.getElementById('stallGrid').innerHTML =
                    '<div class="no-vendors"><i class="fas fa-store-slash"></i><p>No vendor stalls are currently listing this product.</p></div>';
            }
        })
        .catch(() => {
            document.getElementById('stallGrid').innerHTML =
                '<div class="no-vendors"><i class="fas fa-exclamation-circle"></i><p>Could not load stall data.</p></div>';
        });
}

function closeModal() {
    document.getElementById('vendorModal').classList.remove('open');
}

document.getElementById('vendorModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<footer class="site-footer" id="contact">
    <div class="footer-grid">
        <div>
            <img src="image/image2.png" alt="ManningHub Logo" class="footer-logo-img"
                 onerror="this.style.display='none'">
            <p>The central digital hub for Manning Market, Peliyagoda — Sri Lanka's largest wholesale vegetable market. Ensuring transparency and efficiency through technology.</p>
            <div class="footer-social">
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                <a href="mailto:admin@manninghub.lk" title="Email"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
        <div>
            <h4>Navigate</h4>
            <a href="index.php">Home</a>
            <a href="live_prices.php">Live Prices</a>
            <a href="announcements.php">Announcements</a>
            <a href="feedback.php">Feedback</a>
            <a href="contact.php">Contact Us</a>
        </div>
        <div>
            <h4>Account</h4>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
            <a href="vendor_dashboard.php">Vendor Portal</a>
            <a href="farmer_dashboard.php">Farmer Portal</a>
            <a href="admin_dashboard.php">Admin Panel</a>
        </div>
        <div>
            <h4>Contact Us</h4>
            <p><i class="fas fa-phone" style="color:#2ecc71;margin-right:8px;"></i> +94 112 123 456</p>
            <p><i class="fas fa-envelope" style="color:#2ecc71;margin-right:8px;"></i> admin@manninghub.lk</p>
            <p><i class="fas fa-location-dot" style="color:#2ecc71;margin-right:8px;"></i> New Manning Market, Peliyagoda, Colombo</p>
            <p><i class="fas fa-clock" style="color:#2ecc71;margin-right:8px;"></i> Open 4:00 AM – 10:00 PM daily</p>
            <p style="margin-top:10px;"><a href="contact.php" style="color:#2ecc71;font-weight:700;">📍 Find Us on Map →</a></p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2026 ManningHub &nbsp;|&nbsp; Built for Progress &nbsp;|&nbsp;
        <a href="contact.php" style="color:#555;">Contact</a> &nbsp;|&nbsp;
        <a href="feedback.php" style="color:#555;">Feedback</a>
    </div>
</footer>

<!-- Back to Top -->
<button class="back-top" id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

<script>
// Mobile menu
function toggleMenu() {
    var menu = document.getElementById('mobileMenu');
    var btn  = document.getElementById('hamburger');
    menu.classList.toggle('mobile-open');
    btn.classList.toggle('open');
}
// Back to top
window.addEventListener('scroll', function() {
    var btn = document.getElementById('backTop');
    if (btn) btn.classList.toggle('visible', window.scrollY > 300);
});
// Close menu on link click
document.querySelectorAll('.site-nav .nav-links a').forEach(function(a) {
    a.addEventListener('click', function() {
        document.getElementById('mobileMenu').classList.remove('mobile-open');
        document.getElementById('hamburger').classList.remove('open');
    });
});
</script>


</body>
</html>
