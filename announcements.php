<?php
session_start();
include 'db.php';
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Announcements | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #27ae60; --dark: #1a252f; --urgent: #e74c3c; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #1a252f 0%, #27ae60 100%);
            color: white; padding: 55px 8%; text-align: center;
        }
        .hero h1 { font-size: 2.2rem; margin-bottom: 8px; }
        .hero p  { font-size: 1rem; opacity: 0.85; }

        /* Sticky Tab Nav */
        .tab-nav {
            display: flex; justify-content: center; flex-wrap: wrap;
            gap: 10px; padding: 22px 20px; background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky; top: 0; z-index: 100;
        }
        .tab-btn {
            padding: 10px 20px; border: 2px solid var(--primary);
            border-radius: 30px; cursor: pointer; font-weight: 600;
            font-size: 13px; background: white; color: var(--primary); transition: 0.3s;
        }
        .tab-btn.active, .tab-btn:hover { background: var(--primary); color: white; }

        /* Sections */
        .page-section { display: none; }
        .page-section.active { display: block; }

        .content-wrap { max-width: 1100px; margin: 0 auto; padding: 40px 20px; }

        .section-title {
            font-size: 1.45rem; color: var(--dark); margin-bottom: 10px;
            padding-bottom: 10px; border-bottom: 3px solid var(--primary);
            display: flex; align-items: center; gap: 10px;
        }
        .section-desc {
            color: #555; margin-bottom: 30px; font-size: 15px;
            line-height: 1.85; background: white; padding: 18px 22px;
            border-radius: 12px; border-left: 5px solid var(--primary);
        }

        /* Video Grid */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(440px, 1fr));
            gap: 25px; margin-bottom: 35px;
        }
        .video-card {
            background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .video-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.14); }
        .video-card iframe { width: 100%; height: 255px; border: none; display: block; }
        .video-info { padding: 16px 20px; }
        .video-info h3 { font-size: 15px; color: var(--dark); margin-bottom: 6px; }
        .video-info p  { font-size: 13px; color: #777; line-height: 1.6; }
        .vtag {
            display: inline-block; margin-top: 8px; padding: 3px 12px;
            border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase;
        }
        .t-market   { background:#e8f5e9; color:#27ae60; }
        .t-hygiene  { background:#e3f2fd; color:#1565c0; }
        .t-quality  { background:#fff3e0; color:#e65100; }
        .t-business { background:#fce4ec; color:#880e4f; }

        /* Info Boxes */
        .info-box { border-radius: 12px; padding: 24px; margin-top: 10px; }
        .info-box h3 { margin-bottom: 14px; font-size: 16px; display:flex; align-items:center; gap:8px; }
        .info-box ul { padding-left: 22px; line-height: 2.2; color: #333; font-size: 14px; }

        /* Grade grid for quality */
        .grade-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-top:15px; }
        .grade-item { background:white; border-radius:10px; padding:14px; text-align:center; }
        .grade-item .gicon { font-size:22px; margin-bottom:6px; }
        .grade-item strong { display:block; margin-bottom:4px; font-size:14px; }
        .grade-item p { font-size:12px; color:#666; }

        /* Business tips grid */
        .tips-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:15px; margin-top:15px; }
        .tip-item { background:white; border-radius:10px; padding:16px; }
        .tip-item .ticon { font-size:22px; margin-bottom:8px; }
        .tip-item strong { display:block; margin-bottom:5px; font-size:14px; }
        .tip-item p { font-size:13px; color:#666; line-height:1.55; }

        /* Announcements */
        .ann-card {
            background:white; border-radius:15px; padding:28px;
            margin-bottom:25px; border-left:6px solid var(--primary);
            box-shadow:0 3px 12px rgba(0,0,0,0.06);
        }
        .ann-card.Urgent { border-left-color:var(--urgent); }
        .cat-badge {
            display:inline-block; padding:4px 14px; border-radius:20px;
            font-size:11px; font-weight:bold; text-transform:uppercase;
            margin-bottom:12px; background:#e8f5e9; color:var(--primary);
        }
        .Urgent .cat-badge { background:#fdeaea; color:var(--urgent); }
        .ann-title { font-size:21px; color:var(--dark); margin-bottom:8px; }
        .ann-meta  { color:#999; font-size:13px; margin-bottom:15px; }
        .ann-msg   { color:#444; line-height:1.9; font-size:15px; }
        .empty-state { text-align:center; padding:60px; color:#aaa; }
        @media(max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
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

<nav class="site-nav">
    <a href="index.php" class="nav-logo">
        <img src="image/image1.png" alt="ManningHub Logo" onerror="this.src='https://via.placeholder.com/120x60?text=M-HUB'">
        <h2>MANNINGHUB</h2>
    </a>
    <ul class="nav-links" id="mobileMenu">
            <li><a href="index.php">Home</a></li>
            <li><a href="live_prices.php">Live Prices</a></li>
            <li><a href="announcements.php" class="active">Announcements</a></li>
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
    <h1><img src="image/icons/bullhorn.png" alt="" style="width:28px;height:28px;vertical-align:middle;margin-right:6px;"> ManningHub Official Channel</h1>
    <p>Manning Market Updates &nbsp;|&nbsp; Hygiene &amp; Quality Standards &nbsp;|&nbsp; Business Guidance for Vendors &amp; Farmers</p>
</div>

<!-- Tabs -->
<div class="tab-nav">
    <button class="tab-btn active" onclick="showTab('market',this)"><img src="image/icons/store.png" alt="" style="width:16px;height:16px;vertical-align:middle;margin-right:5px;"> Market Updates</button>
    <button class="tab-btn" onclick="showTab('hygiene',this)"><img src="image/icons/hygiene.png" alt="" style="width:16px;height:16px;vertical-align:middle;margin-right:5px;"> Hygiene Inspection</button>
    <button class="tab-btn" onclick="showTab('quality',this)"><img src="image/icons/star.png" alt="" style="width:16px;height:16px;vertical-align:middle;margin-right:5px;"> Quality Inspection</button>
    <button class="tab-btn" onclick="showTab('business',this)"><img src="image/icons/lightbulb.png" alt="" style="width:16px;height:16px;vertical-align:middle;margin-right:5px;"> Business Ideas</button>
    <button class="tab-btn" onclick="showTab('announcements',this)"><img src="image/icons/bell.png" alt="" style="width:16px;height:16px;vertical-align:middle;margin-right:5px;"> Announcements</button>
</div>


<!-- TAB 1 — MANNING MARKET UPDATES-->
<div id="tab-market" class="page-section active">
    <div class="content-wrap">
        <h2 class="section-title"><img src="image/icons/store.png" alt="" style="width:22px;height:22px;vertical-align:middle;margin-right:6px;"> Manning Market Updates</h2>
        <div class="section-desc">
            <strong>Manning Market (Peliyagoda)</strong> is Sri Lanka's largest wholesale vegetable and fruit market.
            ManningHub digitally manages vendor registrations, daily price boards, stock levels, and hygiene monitoring
            to modernize operations and improve transparency for all stakeholders.
            Watch the latest Manning Market news and updates<strong></strong>.
        </div>
        <div class="video-grid">
            <div class="video-card">
                <iframe src="https://www.youtube.com/embed/iv5OFBRaf8A"
                    title="Manning Market Update 1"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
                <div class="video-info">
                    <h3>Manning Market - Latest Update</h3>
                    <p>Manning වෙළඳපොළේ නවතම තොරතුරු, මිල ගණන් සහ වෙළඳ ක්‍රියාකාරකම් පිළිබඳ නවතම වාර්තාව.</p>
                    <span class="vtag t-market">Market Update</span>
                </div>
            </div>
            <div class="video-card">
                <iframe src="https://www.youtube.com/embed/HP1d8_-R0hY"
                    title="Manning Market Wholesale Report"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
                <div class="video-info">
                    <h3>Wholesale Vegetable Market Report</h3>
                    <p>Manning වෙළඳපොළේ එළවළු හා පලතුරු තොග වෙළඳාම, මිල ගණන් සහ ගොවීන් සමඟ ඇති සම්බන්ධය.</p>
                    <span class="vtag t-market">Market Update</span>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- TAB 2 — HYGIENE INSPECTION -->
<div id="tab-hygiene" class="page-section">
    <div class="content-wrap">
        <h2 class="section-title"><img src="image/icons/hygiene.png" alt="" style="width:22px;height:22px;vertical-align:middle;margin-right:6px;"> Hygiene Inspection - ඇයි එය වැදගත්?</h2>
        <div class="section-desc">
            ManningHub's <strong>Hygiene Monitoring Module</strong> records vendor hygiene inspection results
            and ensures vendors consistently adhere to health and safety regulations.
            Regular monitoring identifies risks early and supports compliance with government market health standards.
            Customers gain confidence purchasing fresh and safe produce  (watch these videos to understand why hygiene matters.)
        </div>
        <div class="video-grid">
            <div class="video-card">
                <iframe src="https://www.youtube.com/embed/od45avr7n7I"
                    title="Food Hygiene Importance"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
                <div class="video-info">
                    <h3>ආහාර සනීපාරක්ෂාව - Why Hygiene Matters</h3>
                    <p>වෙළඳ ස්ථාන සනීපාරක්ෂාව පවත්වා ගැනීම ඇයි අනිවාර්ය ද? ආහාර ආරක්ෂාව පිළිබඳ සිංහල මාර්ගෝපදේශය.</p>
                    <span class="vtag t-hygiene">Hygiene</span>
                </div>
            </div>
            <div class="video-card">
                <iframe src="https://www.youtube.com/embed/7fjER2wXFKE"
                    title="Market Cleanliness Guide Sinhala"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
                <div class="video-info">
                    <h3>වෙළඳ ස්ථාන පිරිසිදු කිරීමේ ක්‍රමය</h3>
                    <p>Manning Market Vendors සඳහා - ඔබේ ස්ථානය දිනපතා පිරිසිදු කර ගන්නේ කෙසේ ද? ප්‍රායෝගික සිංහල ඉඟි.</p>
                    <span class="vtag t-hygiene">Hygiene</span>
                </div>
            </div>
        </div>

        <div class="info-box" style="background:#e3f2fd; border-left:5px solid #1565c0;">
            <h3 style="color:#1565c0;"><img src="image/icons/clipboard_list.png" alt="" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> ManningHub Hygiene Standards - Vendor Checklist</h3>
            <ul>
                <li>Stalls must be cleaned daily before and after trading hours</li>
                <li>All produce must be stored off the ground on clean raised surfaces</li>
                <li>Vendors must wear gloves and masks when handling fresh produce</li>
                <li>Waste bins must be covered and emptied at the end of every trading day</li>
                <li>No rotten, spoiled, or expired produce may remain in the stall area</li>
                <li>Drainage areas must be kept clear, clean, and free-flowing at all times</li>
                <li>Vendors who fail hygiene inspection will receive a Warning or Fail status in the system</li>
            </ul>
        </div>
    </div>
</div>


<!-- TAB 3 - QUALITY INSPECTION-->
<div id="tab-quality" class="page-section">
    <div class="content-wrap">
        <h2 class="section-title"><img src="image/icons/star.png" alt="" style="width:22px;height:22px;vertical-align:middle;margin-right:6px;"> Quality Inspection - ගුණාත්මකභාවය වැදගත් ඇයි?</h2>
        <div class="section-desc">
            ManningHub's <strong>Quality Monitoring Module</strong> records product quality inspection results
            for all vendor stalls. Quality checks protect customers and build market trust by ensuring
            only fresh, safe produce is sold at Manning Market.
            Vendors and farmers who consistently maintain high quality standards are rewarded with better
            business opportunities and customer confidence.
        </div>

        <!-- No video here - video moved to Business Ideas as per your instruction -->
        <div class="info-box" style="background:#fff3e0; border-left:5px solid #e65100; margin-bottom:30px;">
            <h3 style="color:#e65100;"><img src="image/icons/clipboard_check.png" alt="" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Quality Grades Used in ManningHub</h3>
            <div class="grade-grid">
                <div class="grade-item">
                    <img src="image/icons/excellent.png" alt="Excellent" style="width:52px; height:52px; margin-bottom:8px;">
                    <strong style="color:#27ae60;">Excellent</strong>
                    <p>Fresh, firm, bright color. No blemishes or damage.</p>
                </div>
                <div class="grade-item">
                    <img src="image/icons/good.png" alt="Good" style="width:52px; height:52px; margin-bottom:8px;">
                    <strong style="color:#2196f3;">Good</strong>
                    <p>Minor surface marks acceptable. Fresh and firm.</p>
                </div>
                <div class="grade-item">
                    <img src="image/icons/average.png" alt="Average" style="width:52px; height:52px; margin-bottom:8px;">
                    <strong style="color:#ff9800;">Average</strong>
                    <p>Acceptable quality but needs improvement.</p>
                </div>
                <div class="grade-item">
                    <img src="image/icons/poor.png" alt="Poor" style="width:52px; height:52px; margin-bottom:8px;">
                    <strong style="color:#f44336;">Poor</strong>
                    <p>Below standard. Vendor will be notified.</p>
                </div>
                <div class="grade-item">
                    <img src="image/icons/rejected.png" alt="Rejected" style="width:52px; height:52px; margin-bottom:8px;">
                    <strong style="color:#b71c1c;">Rejected</strong>
                    <p>Not fit for sale. Produce must be removed immediately.</p>
                </div>
            </div>
        </div>

        <div class="info-box" style="background:#e8f5e9; border-left:5px solid #27ae60;">
            <h3 style="color:#27ae60;"><img src="image/icons/seedling.png" alt="" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> How to Maintain Quality - Tips for Vendors & Farmers</h3>
            <ul>
                <li>Harvest produce at the correct maturity stage to ensure freshness</li>
                <li>Use clean, ventilated containers for transporting produce to the market</li>
                <li>Separate damaged or overripe items from fresh stock immediately</li>
                <li>Store produce in shaded, cool areas to reduce deterioration</li>
                <li>Keep all product display areas clean and free from contamination</li>
                <li>Check and rotate stock regularly - first in, first out (FIFO) method</li>
                <li>Rejected produce must be disposed of responsibly and not resold</li>
            </ul>
        </div>
    </div>
</div>


<!-- TAB 4 - BUSINESS IDEAS (videos: hqjUxuaN8og + m797fUZ8ytg)-->
<div id="tab-business" class="page-section">
    <div class="content-wrap">
        <h2 class="section-title"><img src="image/icons/lightbulb.png" alt="" style="width:22px;height:22px;vertical-align:middle;margin-right:6px;"> Business Ideas - Vendors, Farmers & Entrepreneurs</h2>
        <div class="section-desc">
            ManningHub connects vendors, farmers, and entrepreneurs within the Manning Market ecosystem.
            Whether you are starting a new stall, expanding your farming operation, or looking to grow your
            agribusiness.  These videos offer practical Sinhala guidance on business strategies,
            eco-friendly farming, and smart market trading.
            <br><br>
            <em>(මෙම වීඩියෝ Manning Market හි Vendors, ගොවීන් සහ ව්‍යාපාරිකයන් සඳහා ප්‍රයෝජනවත් ව්‍යාපාරික අදහස් ලබා දේ.)</em>
        </div>
        <div class="video-grid">
            <div class="video-card">
                <iframe src="https://www.youtube.com/embed/hqjUxuaN8og"
                    title="Business Ideas for Vendors Sinhala"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
                <div class="video-info">
                    <h3>Manning Market Vendor Business Tips</h3>
                    <p>Manning Market Vendors සඳහා ව්‍යාපාරය ශක්තිමත් කර ගන්නේ කෙසේ ද? ආදායම් ඉහළ නංවා ගැනීමේ ක්‍රම</p>
                    <span class="vtag t-business">Business Ideas</span>
                </div>
            </div>
            <div class="video-card">
                <iframe src="https://www.youtube.com/embed/m797fUZ8ytg"
                    title="Eco Farming Business Sinhala"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen></iframe>
                <div class="video-info">
                    <h3>Eco Farming - ගොවිතැනෙන් ව්‍යාපාරයක්</h3>
                    <p>පරිසරයට හිතකාමී ගොවිතැන් ක්‍රම භාවිතා කර Manning Market සඳහා ගුණාත්මක නිෂ්පාදන ලබා දෙන ආකාරය.</p>
                    <span class="vtag t-business">Eco Business</span>
                </div>
            </div>
        </div>

        <div class="info-box" style="background:#fce4ec; border-left:5px solid #880e4f;">
            <h3 style="color:#880e4f;"><img src="image/icons/chart_line.png" alt="" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Smart Business Tips for Manning Market Participants</h3>
            <div class="tips-grid">
                <div class="tip-item">
                    <img src="image/icons/register.png" alt="Register" style="width:44px; height:44px; margin-bottom:8px;">
                    <strong>Register on ManningHub</strong>
                    <p>Get officially verified and approved through ManningHub to gain customer trust and market credibility.</p>
                </div>
                <div class="tip-item">
                    <img src="image/icons/prices.png" alt="Prices" style="width:44px; height:44px; margin-bottom:8px;">
                    <strong>Track Daily Prices</strong>
                    <p>Use ManningHub's real-time price board to monitor market prices and make smarter buying and selling decisions.</p>
                </div>
                <div class="tip-item">
                    <img src="image/icons/organic.png" alt="Organic" style="width:44px; height:44px; margin-bottom:8px;">
                    <strong>Go Organic & Eco Friendly</strong>
                    <p>Organic produce commands higher prices. Reduce chemical use and attract health-conscious customers.</p>
                </div>
                <div class="tip-item">
                    <img src="image/icons/stock.png" alt="Stock" style="width:44px; height:44px; margin-bottom:8px;">
                    <strong>Manage Your Stock</strong>
                    <p>Use ManningHub's stock tracking to avoid wastage, plan supply, and respond to low-stock alerts on time.</p>
                </div>
                <div class="tip-item">
                    <img src="image/icons/partnership.png" alt="Partnership" style="width:44px; height:44px; margin-bottom:8px;">
                    <strong>Build Farmer Partnerships</strong>
                    <p>Vendors who partner directly with registered farmers on ManningHub get fresher produce at better prices.</p>
                </div>
                <div class="tip-item">
                    <img src="image/icons/quality.png" alt="Quality" style="width:44px; height:44px; margin-bottom:8px;">
                    <strong>Maintain Quality Standards</strong>
                    <p>Vendors with consistently high quality inspection scores attract more customers and build long-term loyalty.</p>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- TAB 5 — ANNOUNCEMENTS-->
<div id="tab-announcements" class="page-section">
    <div class="content-wrap" style="max-width:900px;">
        <h2 class="section-title" style="margin-bottom:28px;"><img src="image/icons/bell.png" alt="" style="width:22px;height:22px;vertical-align:middle;margin-right:6px;"> Official Announcements</h2>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="ann-card <?php echo htmlspecialchars($row['category']); ?>">
                    <span class="cat-badge"><?php echo htmlspecialchars($row['category']); ?></span>
                    <div class="ann-meta">
                        <img src="image/icons/calendar.png" alt="" style="width:14px;height:14px;vertical-align:middle;margin-right:6px;margin-right:4px;">
                        <?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                        &nbsp;|&nbsp;
                        <img src="image/icons/clock.png" alt="" style="width:14px;height:14px;vertical-align:middle;margin-right:6px;margin-right:4px;">
                        <?php echo date('h:i A', strtotime($row['created_at'])); ?>
                    </div>
                    <h2 class="ann-title"><?php echo htmlspecialchars($row['title']); ?></h2>
                    <div class="ann-msg">
                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <img src="image/icons/box_open.png" alt="" style="width:64px;height:64px;margin-bottom:15px;opacity:0.5;">
                <h3>No announcements yet.</h3>
                <p style="margin-top:8px;">Check back soon for updates from Manning Market Administration.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<script>
function showTab(name, btn) {
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
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
