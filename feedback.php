<?php
session_start();
include 'db.php';

$msg = ''; $msg_type = '';

if (isset($_POST['submit_feedback'])) {
    $name    = $conn->real_escape_string(trim($_POST['name']));
    $email   = $conn->real_escape_string(trim($_POST['email']));
    $type    = in_array($_POST['type'], ['feedback','complaint']) ? $_POST['type'] : 'feedback';
    $subject = $conn->real_escape_string(trim($_POST['subject']));
    $message = $conn->real_escape_string(trim($_POST['message']));
    $stall   = $conn->real_escape_string(trim($_POST['related_stall'] ?? ''));
    $uid     = $_SESSION['user_id'] ?? NULL;
    $role    = $_SESSION['role']    ?? 'customer';

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $msg = 'Please fill in all required fields.';
        $msg_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
        $msg_type = 'error';
    } else {
        $uid_val = $uid ? $uid : 'NULL';
        $stall_col = !empty($stall) ? ", related_stall" : "";
        $stall_val = !empty($stall) ? ", '$stall'" : "";
        $sql = "INSERT INTO feedback (user_id, user_name, user_role, subject, message, type, submitter_email, status, created_at$stall_col)
                VALUES ($uid_val, '$name', '$role', '$subject', '$message', '$type', '$email', 'unread', NOW()$stall_val)";
        if ($conn->query($sql)) {
            $msg = $type === 'complaint'
                ? '✅ Your complaint has been submitted. Our admin team will review it shortly.'
                : '✅ Thank you! Your feedback has been submitted successfully.';
            $msg_type = 'success';
        } else {
            // If new columns don't exist yet, fallback
            $sql2 = "INSERT INTO feedback (user_id, user_name, user_role, subject, message, status, created_at)
                     VALUES ($uid_val, '$name', '$role', '$subject', '$message', 'unread', NOW())";
            $conn->query($sql2);
            $msg = '✅ Thank you! Your submission has been received.';
            $msg_type = 'success';
        }
    }
}

// Pre-fill if logged in
$pre_name  = $_SESSION['name']  ?? '';
$pre_email = '';
if (isset($_SESSION['user_id'])) {
    $ur = $conn->query("SELECT email FROM users WHERE id={$_SESSION['user_id']} LIMIT 1");
    if ($ur && $ur->num_rows > 0) $pre_email = $ur->fetch_assoc()['email'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Complaints | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #27ae60; --primary2: #2ecc71;
            --dark: #0d1b2a;    --warn: #e67e22;
            --danger: #e74c3c;  --info: #3498db;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f0f4f0; }

        /* NAV */
        nav { background:white; padding:14px 40px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(0,0,0,.07); position:sticky; top:0; z-index:999; }
        .logo { font-family:'Outfit',sans-serif; font-size:22px; font-weight:800; color:var(--primary); text-decoration:none; }
        .nav-links { display:flex; gap:28px; list-style:none; }
        .nav-links a { text-decoration:none; color:#444; font-weight:600; font-size:14px; transition:.2s; }
        .nav-links a:hover, .nav-links a.active { color:var(--primary); }
        .nav-auth { display:flex; gap:10px; }
        .btn-login { background:transparent; border:2px solid var(--primary); color:var(--primary); padding:8px 20px; border-radius:8px; font-weight:700; text-decoration:none; font-size:13px; }
        .btn-reg   { background:var(--primary); color:white; padding:8px 20px; border-radius:8px; font-weight:700; text-decoration:none; font-size:13px; }

        /* HERO */
        .hero { background:linear-gradient(135deg,#0d1b2a 0%,#1a3a2a 100%); padding:60px 20px; text-align:center; color:white; }
        .hero h1 { font-family:'Outfit',sans-serif; font-size:36px; font-weight:900; margin-bottom:12px; }
        .hero p  { font-size:16px; color:rgba(255,255,255,.7); max-width:550px; margin:0 auto; }

        /* MAIN */
        .main-wrap { max-width:900px; margin:0 auto; padding:40px 20px; }

        /* TYPE SELECTOR */
        .type-tabs { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:28px; }
        .type-tab { border:2px solid #ddd; border-radius:14px; padding:20px; text-align:center; cursor:pointer; transition:.2s; background:white; }
        .type-tab:hover { border-color:var(--primary); }
        .type-tab.active-fb  { border-color:var(--primary); background:#eafaf1; }
        .type-tab.active-cmp { border-color:var(--danger);  background:#fdecea; }
        .type-tab i { font-size:28px; display:block; margin-bottom:8px; }
        .type-tab.active-fb  i { color:var(--primary); }
        .type-tab.active-cmp i { color:var(--danger); }
        .type-tab h3 { font-family:'Outfit',sans-serif; font-size:17px; font-weight:700; color:var(--dark); margin-bottom:4px; }
        .type-tab p  { font-size:12px; color:#888; }

        /* FORM CARD */
        .form-card { background:white; border-radius:18px; padding:32px; box-shadow:0 2px 16px rgba(0,0,0,.06); }
        .form-title { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--dark); margin-bottom:22px; display:flex; align-items:center; gap:10px; }

        .form-row  { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:6px; }
        .form-group label span { color:var(--danger); }
        .form-group input, .form-group select, .form-group textarea {
            width:100%; padding:11px 14px; border:1.5px solid #e0e0e0;
            border-radius:9px; font-size:14px; outline:none; transition:.2s;
            font-family:'Segoe UI',sans-serif;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); }
        .form-group textarea { min-height:130px; resize:vertical; }

        .complaint-field { display:none; }
        .complaint-field.show { display:block; }

        /* SUBMIT */
        .btn-submit { width:100%; padding:14px; border:none; border-radius:10px; font-size:16px; font-weight:700; cursor:pointer; transition:.2s; font-family:'Outfit',sans-serif; letter-spacing:.3px; }
        .btn-submit.green { background:var(--primary); color:white; }
        .btn-submit.green:hover { background:#219150; }
        .btn-submit.red   { background:var(--danger);  color:white; }
        .btn-submit.red:hover   { background:#c0392b; }

        /* ALERTS */
        .alert { padding:14px 18px; border-radius:10px; font-size:14px; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#eafaf1; color:#1e8449; border-left:4px solid var(--primary); }
        .alert-error   { background:#fdecea; color:#c0392b; border-left:4px solid var(--danger); }

        /* INFO BOXES */
        .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:28px; }
        .info-box { background:white; border-radius:14px; padding:18px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .info-box i { font-size:28px; color:var(--primary); margin-bottom:8px; display:block; }
        .info-box h4 { font-size:14px; font-weight:700; color:var(--dark); margin-bottom:4px; }
        .info-box p  { font-size:12px; color:#888; }

        /* FOOTER */
        footer { background:var(--dark); color:rgba(255,255,255,.6); text-align:center; padding:28px 20px; font-size:13px; margin-top:50px; }
        footer a { color:var(--primary2); text-decoration:none; }

        @media(max-width:600px) {
            .form-row { grid-template-columns:1fr; }
            .type-tabs { grid-template-columns:1fr; }
            nav { padding:12px 16px; }
            .nav-links { display:none; }
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

<!-- Navbar -->

<nav class="site-nav">
    <a href="index.php" class="nav-logo">
        <img src="image/image1.png" alt="ManningHub Logo" onerror="this.src='https://via.placeholder.com/120x60?text=M-HUB'">
        <h2>MANNINGHUB</h2>
    </a>
    <ul class="nav-links" id="mobileMenu">
            <li><a href="index.php">Home</a></li>
            <li><a href="live_prices.php">Live Prices</a></li>
            <li><a href="announcements.php">Announcements</a></li>
            <li><a href="feedback.php" class="active">Feedback</a></li>
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
    <h1><i class="fas fa-comments" style="color:var(--primary2);margin-right:12px;"></i>Feedback & Complaints</h1>
    <p>Help us improve Manning Market. Share your experience or report an issue, we read every submission.</p>
</div>

<div class="main-wrap">

    <!-- Info Boxes -->
    <div class="info-grid">
        <div class="info-box">
            <i class="fas fa-clock"></i>
            <h4>Response Time</h4>
            <p>Complaints reviewed within 24 hours</p>
        </div>
        <div class="info-box">
            <i class="fas fa-shield-alt"></i>
            <h4>Confidential</h4>
            <p>Your submission is seen only by admin</p>
        </div>
        <div class="info-box">
            <i class="fas fa-check-circle"></i>
            <h4>Action Taken</h4>
            <p>Every complaint gets a resolution status</p>
        </div>
    </div>

    <!-- Alert -->
    <?php if (!empty($msg)): ?>
    <div class="alert alert-<?php echo $msg_type; ?>">
        <i class="fas <?php echo $msg_type==='success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <?php echo $msg; ?>
    </div>
    <?php endif; ?>

    <!-- Type Selector -->
    <div class="type-tabs" id="typeTabs">
        <div class="type-tab active-fb" id="tab-feedback" onclick="selectType('feedback')">
            <i class="fas fa-lightbulb"></i>
            <h3>Give Feedback</h3>
            <p>Suggestions, compliments, or general comments about the market</p>
        </div>
        <div class="type-tab" id="tab-complaint" onclick="selectType('complaint')">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Lodge a Complaint</h3>
            <p>Report a vendor issue, product quality problem, or hygiene concern</p>
        </div>
    </div>

    <!-- Form -->
    <div class="form-card">
        <div class="form-title" id="formTitle">
            <i class="fas fa-lightbulb" style="color:var(--primary);"></i>
            Share Your Feedback
        </div>

        <form method="POST">
            <input type="hidden" name="submit_feedback" value="1">
            <input type="hidden" name="type" id="typeInput" value="feedback">

            <div class="form-row">
                <div class="form-group">
                    <label>Your Name <span>*</span></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($pre_name); ?>" placeholder="e.g. Kamal Perera" required>
                </div>
                <div class="form-group">
                    <label>Email Address <span>*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($pre_email); ?>" placeholder="your@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label>Subject <span>*</span></label>
                <input type="text" name="subject" id="subjectField" placeholder="Brief description of your feedback" required>
            </div>

            <!-- Complaint-only: Related Stall -->
            <div class="form-group complaint-field" id="stallField">
                <label>Related Vendor / Stall Number <span style="color:#888;font-weight:400;">(optional)</span></label>
                <input type="text" name="related_stall" placeholder="e.g. ST112 or Kamal Perera">
                <div style="font-size:12px;color:#aaa;margin-top:4px;">If your complaint is about a specific vendor stall, enter the stall number or name here.</div>
            </div>

            <div class="form-group">
                <label>Your Message <span>*</span></label>
                <textarea name="message" id="messageField" placeholder="Please describe your feedback in detail..." required></textarea>
            </div>

            <button type="submit" class="btn-submit green" id="submitBtn">
                <i class="fas fa-paper-plane"></i>&nbsp; Submit Feedback
            </button>
        </form>
    </div>

</div>


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


<script>
function selectType(type) {
    const isFeedback = type === 'feedback';
    document.getElementById('typeInput').value = type;

    document.getElementById('tab-feedback').className  = 'type-tab' + (isFeedback ? ' active-fb' : '');
    document.getElementById('tab-complaint').className = 'type-tab' + (!isFeedback ? ' active-cmp' : '');

    document.getElementById('stallField').classList.toggle('show', !isFeedback);

    const title = document.getElementById('formTitle');
    const btn   = document.getElementById('submitBtn');
    const subj  = document.getElementById('subjectField');
    const msg   = document.getElementById('messageField');

    if (isFeedback) {
        title.innerHTML = '<i class="fas fa-lightbulb" style="color:var(--primary);"></i> Share Your Feedback';
        btn.innerHTML   = '<i class="fas fa-paper-plane"></i>&nbsp; Submit Feedback';
        btn.className   = 'btn-submit green';
        subj.placeholder = 'Brief description of your feedback';
        msg.placeholder  = 'Please describe your feedback in detail...';
    } else {
        title.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i> Lodge a Complaint';
        btn.innerHTML   = '<i class="fas fa-flag"></i>&nbsp; Submit Complaint';
        btn.className   = 'btn-submit red';
        subj.placeholder = 'What is your complaint about?';
        msg.placeholder  = 'Please describe the issue clearly — what happened, when, and who was involved...';
    }
}
</script>
</body>
</html>
