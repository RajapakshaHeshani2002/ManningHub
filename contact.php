<?php
session_start();
include 'db.php';

$msg = ''; $msg_type = '';

// Handle contact form submission
if (isset($_POST['send_message'])) {
    $cname    = $conn->real_escape_string(trim($_POST['name']    ?? ''));
    $cemail   = $conn->real_escape_string(trim($_POST['email']   ?? ''));
    $csubject = $conn->real_escape_string(trim($_POST['subject'] ?? ''));
    $cmessage = $conn->real_escape_string(trim($_POST['message'] ?? ''));

    if (empty($cname) || empty($cemail) || empty($cmessage)) {
        $msg = 'Please fill in all required fields.';
        $msg_type = 'error';
    } elseif (!filter_var($cemail, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
        $msg_type = 'error';
    } else {
        // Save as feedback in system
        $conn->query("INSERT INTO feedback (user_name, user_role, subject, message, type, submitter_email, status, created_at)
                      VALUES ('$cname', 'customer', '$csubject', '$cmessage', 'feedback', '$cemail', 'unread', NOW())");
        $msg = 'Thank you! Your message has been sent. We will get back to you within 24 hours.';
        $msg_type = 'success';
    }
}

// Load system settings
$settings_res = $conn->query("SELECT * FROM system_settings WHERE id=1");
$settings     = $settings_res ? $settings_res->fetch_assoc() : [];
$open_time    = $settings['open_time']  ?? '04:00:00';
$close_time   = $settings['close_time'] ?? '22:00:00';
$open_fmt     = date('g:i A', strtotime($open_time));
$close_fmt    = date('g:i A', strtotime($close_time));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#27ae60; --primary2:#2ecc71; --dark:#0d1b2a; --light:#f4f7f6; }
        html { scroll-behavior: smooth; }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        body { background:var(--light); color:var(--dark); }

        /* ── UNIFIED SITE NAVBAR ── */
        nav.site-nav {
            background: white; padding: 0 6%;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); height: 80px;
        }
        nav.site-nav .nav-logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
        nav.site-nav .nav-logo img { height:60px; width:auto; }
        nav.site-nav .nav-logo h2 { font-family:'Outfit',sans-serif; color:#27ae60; font-size:18px; font-weight:800; letter-spacing:1.5px; }
        nav.site-nav .nav-links { display:flex; list-style:none; gap:28px; }
        nav.site-nav .nav-links a { text-decoration:none; color:#2c3e50; font-weight:600; font-size:14px; transition:.2s; padding-bottom:4px; border-bottom:2px solid transparent; }
        nav.site-nav .nav-links a:hover { color:#27ae60; border-bottom-color:#27ae60; }
        nav.site-nav .nav-links a.active { color:#27ae60; border-bottom-color:#27ae60; }
        nav.site-nav .nav-auth { display:flex; gap:10px; align-items:center; }
        nav.site-nav .nav-auth a { padding:8px 18px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:.2s; }
        nav.site-nav .btn-login { color:#27ae60; border:2px solid #27ae60; }
        nav.site-nav .btn-login:hover { background:#27ae60; color:white; }
        nav.site-nav .btn-reg { background:#27ae60; color:white; border:2px solid #27ae60; }
        nav.site-nav .btn-reg:hover { background:#1e8449; }
        .hamburger { display:none; flex-direction:column; gap:5px; cursor:pointer; background:none; border:none; padding:4px; }
        .hamburger span { display:block; width:24px; height:2px; background:#2c3e50; border-radius:2px; transition:.3s; }
        .hamburger.open span:nth-child(1) { transform:translateY(7px) rotate(45deg); }
        .hamburger.open span:nth-child(2) { opacity:0; }
        .hamburger.open span:nth-child(3) { transform:translateY(-7px) rotate(-45deg); }
        @media(max-width:768px) {
            nav.site-nav { height:65px; padding:0 5%; position:relative; }
            nav.site-nav .nav-links { display:none; flex-direction:column; gap:0; position:absolute; top:65px; left:0; right:0; background:white; padding:12px 0; box-shadow:0 6px 20px rgba(0,0,0,.1); z-index:999; }
            nav.site-nav .nav-links.mobile-open { display:flex; }
            nav.site-nav .nav-links li a { padding:12px 6%; display:block; border-bottom:1px solid #f0f0f0; }
            nav.site-nav .nav-auth { display:none; }
            .hamburger { display:flex; }
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, #0d1b2a 0%, #1a3a2a 100%);
            padding: 60px 6% 50px; color: white; text-align: center;
        }
        .hero h1 { font-family:'Outfit',sans-serif; font-size:36px; font-weight:900; margin-bottom:12px; }
        .hero p  { font-size:16px; color:rgba(255,255,255,.7); max-width:560px; margin:0 auto; }
        .breadcrumb { display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(255,255,255,.5); justify-content:center; margin-bottom:16px; }
        .breadcrumb a { color:rgba(255,255,255,.5); text-decoration:none; }
        .breadcrumb a:hover { color:var(--primary2); }

        /* ── MAIN CONTENT ── */
        .content-wrap { max-width:1100px; margin:0 auto; padding:50px 6%; }

        /* ── INFO CARDS ROW ── */
        .info-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:18px; margin-bottom:50px; }
        .info-card { background:white; border-radius:16px; padding:24px 20px; text-align:center; box-shadow:0 2px 14px rgba(0,0,0,.06); transition:.2s; }
        .info-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
        .info-icon { width:52px; height:52px; border-radius:14px; background:linear-gradient(135deg,#27ae60,#2ecc71); color:white; display:flex; align-items:center; justify-content:center; font-size:22px; margin:0 auto 14px; }
        .info-card h4 { font-size:14px; font-weight:700; color:var(--dark); margin-bottom:6px; }
        .info-card p  { font-size:13px; color:#888; line-height:1.6; }
        .info-card a  { color:var(--primary); text-decoration:none; font-weight:600; }

        /* ── MAP + FORM GRID ── */
        .map-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:28px; margin-bottom:50px; }
        @media(max-width:768px) { .map-form-grid { grid-template-columns:1fr; } }

        .section-box { background:white; border-radius:18px; padding:28px; box-shadow:0 2px 14px rgba(0,0,0,.06); }
        .sec-title { font-family:'Outfit',sans-serif; font-size:18px; font-weight:800; color:var(--dark); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .sec-title i { color:var(--primary); }

        /* Google Maps embed */
        .map-embed { border-radius:12px; overflow:hidden; border:1px solid #e8e8e8; }
        .map-embed iframe { width:100%; height:300px; border:none; display:block; }
        .map-directions { display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
        .btn-directions { background:var(--primary); color:white; padding:10px 18px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px; transition:.2s; }
        .btn-directions:hover { background:#1e8449; }
        .btn-directions.secondary { background:white; color:var(--primary); border:2px solid var(--primary); }
        .btn-directions.secondary:hover { background:var(--primary); color:white; }

        /* Contact Form */
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:12px; font-weight:700; color:#555; margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
        .form-group input, .form-group select, .form-group textarea {
            width:100%; padding:11px 14px; border:1.5px solid #e0e0e0;
            border-radius:9px; font-size:14px; outline:none; transition:.2s;
            font-family:'Inter',sans-serif;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); }
        .form-group textarea { min-height:110px; resize:vertical; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        @media(max-width:500px) { .form-row { grid-template-columns:1fr; } }
        .btn-send { background:var(--primary); color:white; border:none; padding:13px 28px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; width:100%; transition:.2s; font-family:'Outfit',sans-serif; }
        .btn-send:hover { background:#1e8449; }
        .alert { padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#eafaf1; color:#1e8449; border-left:4px solid var(--primary); }
        .alert-error   { background:#fdecea; color:#c0392b; border-left:4px solid #e74c3c; }

        /* ── STALL FINDER ── */
        .stall-section { margin-bottom:50px; }
        .stall-search-wrap { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .stall-search { flex:1; min-width:200px; padding:11px 16px; border:1.5px solid #e0e0e0; border-radius:10px; font-size:14px; outline:none; transition:.2s; }
        .stall-search:focus { border-color:var(--primary); }
        .stall-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .stall-filter { padding:6px 16px; border-radius:20px; font-size:12px; font-weight:600; border:1.5px solid #ddd; color:#666; background:white; cursor:pointer; transition:.2s; }
        .stall-filter:hover, .stall-filter.active { background:var(--primary); border-color:var(--primary); color:white; }
        .stall-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(82px,1fr)); gap:8px; }
        .stall-card {
            border-radius:10px; padding:10px 6px; text-align:center;
            border:1.5px solid #e8e8e8; cursor:pointer; transition:.2s;
            background:white; font-size:12px;
        }
        .stall-card:hover { border-color:var(--primary); box-shadow:0 2px 10px rgba(39,174,96,.15); }
        .stall-card.approved  { border-color:#d4efdf; background:#eafaf1; }
        .stall-card.pending   { border-color:#fdebd0; background:#fef9ec; }
        .stall-card.suspended { border-color:#fadbd8; background:#fdecea; }
        .stall-num  { font-family:'Outfit',sans-serif; font-size:13px; font-weight:800; color:var(--dark); margin-bottom:2px; }
        .stall-name { font-size:10px; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:78px; }
        .stall-badge { font-size:9px; font-weight:700; padding:1px 6px; border-radius:8px; margin-top:3px; display:inline-block; }
        .badge-approved  { background:#d4efdf; color:#1e8449; }
        .badge-pending   { background:#fdebd0; color:#856404; }
        .badge-suspended { background:#fadbd8; color:#c0392b; }
        .stall-modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9000; align-items:center; justify-content:center; }
        .stall-modal-bg.open { display:flex; }
        .stall-modal { background:white; border-radius:16px; padding:28px; max-width:380px; width:90%; }
        .stall-modal h3 { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--dark); margin-bottom:6px; }
        .stall-info-row { display:flex; gap:10px; align-items:center; padding:8px 0; border-bottom:1px solid #f1f2f6; font-size:13px; }
        .stall-info-row:last-child { border-bottom:none; }
        .stall-info-row i { color:var(--primary); width:16px; }
        .btn-close-modal { background:#f0f0f0; color:#555; border:none; padding:10px 20px; border-radius:8px; font-weight:700; cursor:pointer; margin-top:16px; width:100%; font-size:13px; }

        /* ── OPENING HOURS TABLE ── */
        .hours-table { width:100%; border-collapse:collapse; }
        .hours-table td { padding:10px 0; font-size:14px; border-bottom:1px solid #f1f2f6; }
        .hours-table tr:last-child td { border-bottom:none; }
        .hours-table .day   { color:#888; font-weight:500; }
        .hours-table .time  { font-weight:700; color:var(--dark); text-align:right; }
        .hours-table .open-now { color:var(--primary); font-weight:700; }

        /* ── UNIFIED FOOTER ── */
        footer.site-footer { background:#0d1b2a; color:white; padding:50px 6% 24px; margin-top:60px; }
        .site-footer .footer-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1.5fr; gap:40px; margin-bottom:32px; }
        .site-footer .footer-grid h4 { font-size:13px; font-weight:700; margin-bottom:14px; color:#2ecc71; text-transform:uppercase; letter-spacing:.5px; }
        .site-footer .footer-grid p  { font-size:13px; color:#aaa; margin-bottom:8px; line-height:1.7; }
        .site-footer .footer-grid a  { color:#aaa; text-decoration:none; transition:color .2s; display:block; margin-bottom:7px; font-size:13px; }
        .site-footer .footer-grid a:hover { color:#2ecc71; }
        .site-footer .footer-logo-img { height:55px; margin-bottom:14px; width:auto; }
        .site-footer .footer-bottom { text-align:center; padding-top:20px; border-top:1px solid rgba(255,255,255,.08); color:#555; font-size:13px; }
        .site-footer .footer-social { display:flex; gap:12px; margin-top:12px; }
        .site-footer .footer-social a { width:34px; height:34px; border-radius:8px; background:rgba(255,255,255,.08); display:flex; align-items:center; justify-content:center; color:#aaa; font-size:14px; margin:0; }
        .site-footer .footer-social a:hover { background:#27ae60; color:white; }
        @media(max-width:768px) { .site-footer .footer-grid { grid-template-columns:1fr 1fr; gap:28px; } }
        @media(max-width:480px) { .site-footer .footer-grid { grid-template-columns:1fr; } }

        /* back to top */
        .back-top { position:fixed; bottom:28px; right:28px; width:44px; height:44px; background:#27ae60; color:white; border:none; border-radius:50%; font-size:18px; cursor:pointer; box-shadow:0 4px 14px rgba(39,174,96,.4); display:none; align-items:center; justify-content:center; z-index:9999; transition:.2s; }
        .back-top:hover { background:#1e8449; transform:translateY(-2px); }
        .back-top.visible { display:flex; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="site-nav">
    <a href="index.php" class="nav-logo">
        <img src="image/image1.png" alt="ManningHub Logo" onerror="this.src='https://via.placeholder.com/120x60?text=M-HUB'">
        <h2>MANNINGHUB</h2>
    </a>
    <ul class="nav-links" id="mobileMenu">
        <li><a href="index.php">Home</a></li>
        <li><a href="live_prices.php">Live Prices</a></li>
        <li><a href="announcements.php">Announcements</a></li>
        <li><a href="feedback.php">Feedback</a></li>
        <li><a href="contact.php" class="active">Contact</a></li>
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
    <button class="hamburger" id="hamburger" onclick="toggleMenu()">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <i class="fas fa-chevron-right" style="font-size:10px;"></i>
        <span>Contact Us</span>
    </div>
    <h1><i class="fas fa-map-marker-alt" style="color:var(--primary2);margin-right:12px;"></i>Contact Us</h1>
    <p>Visit us at Manning Market, Peliyagoda or reach out online. We are here to help.</p>
</div>

<div class="content-wrap">

    <!-- INFO CARDS -->
    <div class="info-row">
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-phone"></i></div>
            <h4>Call Us</h4>
            <p><a href="tel:+94112123456">+94 112 123 456</a></p>
            <p style="margin-top:4px;font-size:12px;">Mon – Sat, 5AM – 8PM</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-envelope"></i></div>
            <h4>Email Us</h4>
            <p><a href="mailto:admin@manninghub.lk">admin@manninghub.lk</a></p>
            <p style="margin-top:4px;font-size:12px;">Reply within 24 hours</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-clock"></i></div>
            <h4>Market Hours</h4>
            <p><?php echo $open_fmt; ?> – <?php echo $close_fmt; ?></p>
            <p style="margin-top:4px;font-size:12px;">Open every day including holidays</p>
        </div>
        <div class="info-card">
            <div class="info-icon"><i class="fas fa-location-dot"></i></div>
            <h4>Location</h4>
            <p>New Manning Market<br>Peliyagoda, Colombo</p>
            <p style="margin-top:6px;"><a href="https://maps.app.goo.gl/XCUrSh3PJkmcj9Qa7" target="_blank">Get Directions →</a></p>
        </div>
    </div>

    <!-- MAP + CONTACT FORM -->
    <div class="map-form-grid">

        <!-- Google Map -->
        <div class="section-box">
            <div class="sec-title"><i class="fas fa-map"></i> Manning Market Location</div>
            <div class="map-embed">
                <!-- Manning Market, Peliyagoda, Colombo coordinates: 6.9497° N, 79.8774° E -->
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.5!2d79.8774!3d6.9497!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae259e9b7aaaaab%3A0x5b3e3e3e3e3e3e3e!2sManning+Market%2C+Peliyagoda!5e0!3m2!1sen!2slk!4v1234567890"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    title="Manning Market Peliyagoda Location">
                </iframe>
            </div>
            <div class="map-directions" style="margin-top:14px;">
                <a href="https://maps.app.goo.gl/XCUrSh3PJkmcj9Qa7" target="_blank" class="btn-directions">
                    <i class="fas fa-diamond-turn-right"></i> Get Directions
                </a>
                <a href="https://maps.app.goo.gl/XCUrSh3PJkmcj9Qa7" target="_blank" class="btn-directions secondary">
                    <i class="fas fa-map"></i> Open in Google Maps
                </a>
            </div>
            <div style="margin-top:20px;background:#f8fffe;border-radius:10px;padding:14px 16px;">
                <p style="font-size:13px;color:#555;margin-bottom:6px;"><i class="fas fa-bus" style="color:var(--primary);margin-right:6px;"></i><strong>By Bus:</strong> Routes 137, 240, 248 stop at Peliyagoda Junction</p>
                <p style="font-size:13px;color:#555;margin-bottom:6px;"><i class="fas fa-train" style="color:var(--primary);margin-right:6px;"></i><strong>By Train:</strong> Ragama line — alight at Kelaniya station, 5 min tuk-tuk</p>
                <p style="font-size:13px;color:#555;"><i class="fas fa-car" style="color:var(--primary);margin-right:6px;"></i><strong>By Car:</strong> Off Kandy Road (A1), near Peliyagoda flyover. Parking available.</p>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="section-box">
            <div class="sec-title"><i class="fas fa-paper-plane"></i> Send Us a Message</div>

            <?php if (!empty($msg)): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <i class="fas <?php echo $msg_type==='success'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i>
                <?php echo $msg; ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="send_message" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="name" placeholder="e.g. Kamal Perera" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject">
                        <option>General Enquiry</option>
                        <option>Vendor Registration Help</option>
                        <option>Farmer Registration Help</option>
                        <option>Stall Information</option>
                        <option>Price Enquiry</option>
                        <option>Report a Problem</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Your Message *</label>
                    <textarea name="message" placeholder="Write your message here..." required></textarea>
                </div>
                <button type="submit" class="btn-send">
                    <i class="fas fa-paper-plane"></i>&nbsp; Send Message
                </button>
            </form>

            <!-- Opening Hours -->
            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f2f6;">
                <h4 style="font-size:14px;font-weight:700;color:var(--dark);margin-bottom:14px;"><i class="fas fa-clock" style="color:var(--primary);margin-right:6px;"></i> Market Opening Hours</h4>
                <table class="hours-table">
                    <?php
                    $days = [
                        'Monday–Friday'  => [$open_fmt, $close_fmt],
                        'Saturday'       => [$open_fmt, $close_fmt],
                        'Sunday'         => ['5:00 AM',  '8:00 PM'],
                        'Public Holidays'=> ['5:00 AM',  '8:00 PM'],
                    ];
                    $now_hour = (int)date('H');
                    $is_open  = $now_hour >= 4 && $now_hour < 22;
                    foreach ($days as $day => $times): ?>
                    <tr>
                        <td class="day"><?php echo $day; ?></td>
                        <td class="time"><?php echo $times[0].' – '.$times[1]; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="day">Status Now</td>
                        <td class="<?php echo $is_open?'open-now':'time'; ?>"><?php echo $is_open?'🟢 Open Now':'🔴 Closed'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- STALL FINDER -->
    <div class="stall-section">
        <div class="section-box">
            <div class="sec-title"><i class="fas fa-store"></i> Vendor Stall Finder</div>
            <p style="font-size:13px;color:#888;margin-bottom:18px;">Find a vendor by stall number or name. Click any stall card to see vendor details.</p>

            <div class="stall-search-wrap">
                <input type="text" class="stall-search" id="stallSearch"
                       placeholder="🔍 Search by stall number or vendor name..."
                       oninput="filterStalls()">
                <div class="stall-filters" style="margin:0;align-self:center;">
                    <button class="stall-filter active" onclick="filterByStatus('all',this)">All Stalls</button>
                    <button class="stall-filter" onclick="filterByStatus('approved',this)">✅ Active</button>
                    <button class="stall-filter" onclick="filterByStatus('pending',this)">⏳ Pending</button>
                </div>
            </div>

            <div class="stall-grid" id="stallGrid">
                <?php
                $vendors_res = $conn->query("
                    SELECT u.stall_number, u.full_name, u.phone, u.status, u.address, u.permit_number
                    FROM users u
                    WHERE u.role='vendor' AND u.stall_number != ''
                    ORDER BY u.stall_number ASC
                ");
                if ($vendors_res && $vendors_res->num_rows > 0):
                    while ($v = $vendors_res->fetch_assoc()):
                        $st  = $v['status'];
                        $short_name = explode(' ', $v['full_name'])[0]; // first name only
                        $enc = htmlspecialchars(json_encode($v), ENT_QUOTES);
                ?>
                <div class="stall-card <?php echo $st; ?>"
                     data-stall="<?php echo htmlspecialchars($v['stall_number']); ?>"
                     data-name="<?php echo htmlspecialchars(strtolower($v['full_name'])); ?>"
                     data-status="<?php echo $st; ?>"
                     onclick='openStallModal(<?php echo $enc; ?>)'>
                    <div class="stall-num"><?php echo htmlspecialchars($v['stall_number']); ?></div>
                    <div class="stall-name"><?php echo htmlspecialchars($short_name); ?></div>
                    <span class="stall-badge badge-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span>
                </div>
                <?php endwhile; else: ?>
                <p style="color:#ccc;font-size:13px;grid-column:1/-1;text-align:center;padding:20px;">No vendor stalls found.</p>
                <?php endif; ?>
            </div>

            <p id="stallCount" style="font-size:12px;color:#aaa;margin-top:12px;text-align:right;"></p>
        </div>
    </div>

</div><!-- end content-wrap -->

<!-- Stall Detail Modal -->
<div class="stall-modal-bg" id="stallModal">
    <div class="stall-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <h3 id="modal-stall"></h3>
                <span id="modal-badge" style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;"></span>
            </div>
            <div style="width:46px;height:46px;border-radius:12px;background:#eafaf1;display:flex;align-items:center;justify-content:center;font-size:22px;">🏪</div>
        </div>
        <div id="modal-info"></div>
        <button class="btn-close-modal" onclick="closeStallModal()">Close</button>
    </div>
</div>

<!-- FOOTER -->
<footer class="site-footer" id="contact">
    <div class="footer-grid">
        <div>
            <img src="image/image2.png" alt="ManningHub Logo" class="footer-logo-img" onerror="this.style.display='none'">
            <p>The central digital hub for Manning Market, Peliyagoda — Sri Lanka's largest wholesale vegetable market.</p>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
                <a href="mailto:admin@manninghub.lk"><i class="fas fa-envelope"></i></a>
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
        </div>
        <div>
            <h4>Contact Us</h4>
            <p><i class="fas fa-phone" style="color:#2ecc71;margin-right:8px;"></i> +94 112 123 456</p>
            <p><i class="fas fa-envelope" style="color:#2ecc71;margin-right:8px;"></i> admin@manninghub.lk</p>
            <p><i class="fas fa-location-dot" style="color:#2ecc71;margin-right:8px;"></i> Manning Market, Peliyagoda</p>
            <p><i class="fas fa-clock" style="color:#2ecc71;margin-right:8px;"></i> 4:00 AM – 10:00 PM daily</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2026 ManningHub &nbsp;|&nbsp; Built for Progress
    </div>
</footer>

<button class="back-top" id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

<script>
// Mobile menu
function toggleMenu() {
    document.getElementById('mobileMenu').classList.toggle('mobile-open');
    document.getElementById('hamburger').classList.toggle('open');
}

// Back to top
window.addEventListener('scroll', function() {
    document.getElementById('backTop').classList.toggle('visible', window.scrollY > 300);
});

// Stall search
function filterStalls() {
    var q = document.getElementById('stallSearch').value.toLowerCase();
    var cards = document.querySelectorAll('.stall-card');
    var visible = 0;
    cards.forEach(function(c) {
        var match = c.dataset.stall.toLowerCase().includes(q) || c.dataset.name.includes(q);
        var statusMatch = c.style.display !== 'none' || !q;
        c.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    var activeFilter = document.querySelector('.stall-filter.active');
    if (activeFilter && activeFilter.textContent !== 'All Stalls') {
        var status = activeFilter.getAttribute('data-status');
        cards.forEach(function(c) {
            if (c.style.display !== 'none' && c.dataset.status !== status) {
                c.style.display = 'none'; visible--;
            }
        });
    }
    document.getElementById('stallCount').textContent = visible + ' stalls shown';
}

function filterByStatus(status, btn) {
    document.querySelectorAll('.stall-filter').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    btn.setAttribute('data-status', status);
    var cards = document.querySelectorAll('.stall-card');
    var visible = 0;
    var q = document.getElementById('stallSearch').value.toLowerCase();
    cards.forEach(function(c) {
        var nameMatch = !q || c.dataset.stall.toLowerCase().includes(q) || c.dataset.name.includes(q);
        var statusMatch = status === 'all' || c.dataset.status === status;
        c.style.display = (nameMatch && statusMatch) ? '' : 'none';
        if (c.style.display !== 'none') visible++;
    });
    document.getElementById('stallCount').textContent = visible + ' stalls shown';
}

// Stall modal
function openStallModal(v) {
    var badge_colors = {
        'approved':  {bg:'#eafaf1',color:'#1e8449', label:'✅ Approved'},
        'pending':   {bg:'#fef9ec',color:'#856404', label:'⏳ Pending'},
        'suspended': {bg:'#fdecea',color:'#c0392b', label:'🚫 Suspended'},
        'rejected':  {bg:'#fdecea',color:'#c0392b', label:'❌ Rejected'},
    };
    var bc = badge_colors[v.status] || badge_colors['pending'];
    document.getElementById('modal-stall').textContent = v.stall_number;
    var badge = document.getElementById('modal-badge');
    badge.textContent = bc.label;
    badge.style.background = bc.bg;
    badge.style.color = bc.color;

    var rows = [
        ['fa-user',       'Vendor Name',   v.full_name    || 'N/A'],
        ['fa-phone',      'Phone',          v.phone        || 'N/A'],
        ['fa-id-card',    'Permit No.',     v.permit_number || 'N/A'],
        ['fa-location-dot','Address',       v.address      || 'Manning Market, Peliyagoda'],
    ];
    document.getElementById('modal-info').innerHTML = rows.map(function(r) {
        return '<div class="stall-info-row"><i class="fas ' + r[0] + '"></i><div><span style="font-size:11px;color:#aaa;">' + r[1] + '</span><br><strong style="font-size:14px;">' + r[2] + '</strong></div></div>';
    }).join('');

    document.getElementById('stallModal').classList.add('open');
}
function closeStallModal() {
    document.getElementById('stallModal').classList.remove('open');
}
document.getElementById('stallModal').addEventListener('click', function(e) {
    if (e.target === this) closeStallModal();
});

// Init count
document.addEventListener('DOMContentLoaded', function() {
    var total = document.querySelectorAll('.stall-card').length;
    document.getElementById('stallCount').textContent = total + ' stalls shown';
});
</script>
</body>
</html>
