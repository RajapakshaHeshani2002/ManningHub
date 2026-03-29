<?php
// 1. Database එකට සම්බන්ධ වීම
$conn = new mysqli("localhost", "root", "", "manninghub");

// 2. Connection එකේ අවුලක්ද බලන්න
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Database එකෙන් settings ටික ලබා ගැනීම
$settings_res = $conn->query("SELECT * FROM system_settings WHERE id=1");
$settings = $settings_res->fetch_assoc();

// 4. Admin Settings වලින් එන දත්ත Variables වලට දාගමු
$sys_name   = $settings['system_name'] ?? "ManningHub";
$sys_email  = $settings['contact_email'] ?? "contact@manninghub.lk";
$ticker_msg = $settings['maintenance_text'] ?? "Welcome to ManningHub Digital Market.";
$currency   = $settings['currency'] ?? "Rs.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sys_name; ?> | Digital Market Ecosystem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #27ae60;
            --primary-light: #e8f5e9;
            --secondary: #e67e22;
            --dark: #2c3e50;
            --light: #f4f7f6;
            --white: #ffffff;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        html { scroll-behavior: smooth; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--light); color: var(--dark); line-height: 1.6; }

        /* --- Navigation --- */
        nav {
            background: var(--white);
            padding: 0 8%; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: 110px;
        }

        .main-logo { height: 95px; width: auto; transition: 0.3s; padding: 5px 0; }
        .main-logo:hover { transform: scale(1.05); }

        .nav-links { display: flex; list-style: none; gap: 25px; }
        .nav-links a { text-decoration: none; color: var(--dark); font-weight: 600; transition: 0.3s; }
        .nav-links a:hover { color: var(--primary); }

        .auth-buttons .btn {
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            margin-left: 10px;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-login { color: var(--primary); border: 2px solid var(--primary); }
        .btn-reg { background: var(--primary); color: white; border: 2px solid var(--primary); }

        /* --- ADVANCED PRICE TICKER --- */
        .price-ticker { 
            background: #111; 
            color: white; 
            padding: 15px 0; 
            overflow: hidden; 
            border-bottom: 3px solid var(--primary);
            position: relative;
        }

        .ticker-wrapper { 
            display: flex; 
            white-space: nowrap; 
            width: max-content;
            animation: ticker-scroll 30s linear infinite; 
        }

        .ticker-wrapper:hover { animation-play-state: paused; }

        .ticker-item { 
            display: flex;
            align-items: center;
            padding: 0 40px; 
            font-size: 15px; 
            font-weight: 600; 
            border-right: 1px solid #333; 
        }

        .ticker-item img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .ticker-item span { color: #2ecc71; font-weight: bold; margin-left: 8px; }

        @keyframes ticker-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Message Bar (Announcement from Admin) */
        .message-bar {
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        /* --- Hero Section --- */
        .hero {
            height: 550px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1500');
            background-size: cover; background-position: center;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            text-align: center; color: white; padding: 0 20px;
        }
        .hero h1 { font-size: 3.5rem; margin-bottom: 15px; font-weight: 800; }
        .hero p { font-size: 1.25rem; max-width: 850px; margin-bottom: 35px; opacity: 0.9; }

        /* --- Features Section --- */
        .features-section { padding: 80px 8%; }
        .section-header { text-align: center; max-width: 800px; margin: 0 auto 60px; }
        .section-header h2 { font-size: 2.5rem; color: var(--dark); margin-bottom: 15px; }

        .adv-feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .adv-card {
            background: var(--white); border-radius: 20px; padding: 40px;
            transition: 0.4s; border: 1px solid rgba(0,0,0,0.05); position: relative;
        }
        .adv-card:hover { transform: translateY(-10px); box-shadow: var(--shadow); border-color: var(--primary); }
        .adv-card i { font-size: 40px; color: var(--primary); margin-bottom: 20px; }
        .adv-card h3 { margin-bottom: 12px; font-size: 1.4rem; }
        .adv-card p { color: #666; font-size: 0.95rem; margin-bottom: 20px; }
        .card-link { text-decoration: none; color: var(--primary); font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* --- Footer --- */
        @media(max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
        }

        @media (max-width: 768px) {
            nav { height: auto; padding: 15px; flex-direction: column; gap: 15px; }
            .nav-links { display: none; }
            .hero h1 { font-size: 2.2rem; }
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
    </style>
</head>
<body>

    <div class="message-bar">
        <i class="fas fa-bullhorn"></i> <?php echo $ticker_msg; ?>
    </div>

    <nav>
        <a href="index.php" class="logo-container">
            <img src="image/image1.png" alt="ManningHub Logo" class="main-logo" onerror="this.src='https://via.placeholder.com/150x80?text=M-HUB'">
        </a>
        <ul class="nav-links">
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="live_prices.php">Live Prices</a></li>
            <li><a href="announcements.php">Announcements</a></li>
            <li><a href="feedback.php">Feedback</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
        <div class="auth-buttons">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-reg">Register Now</a>
        </div>
    </nav>

    <div class="price-ticker">
        <div class="ticker-wrapper" id="ajax-ticker">
            <div class="ticker-item">🔄 Loading market rates in <?php echo $currency; ?>...</div>
        </div>
    </div>

    <section class="hero">
        <h1><?php echo $sys_name; ?></h1>
        <p>Real-time wholesale market intelligence connecting Sri Lankan Farmers, Vendors, and Consumers for a smarter agricultural future.</p>
        <div style="display: flex; gap: 20px;">
            <a href="register.php" class="btn btn-reg" style="padding: 18px 40px; font-size: 1.1rem;">Get Started</a>
            <a href="live_prices.php" class="btn" style="background: white; color: var(--dark); padding: 18px 40px; font-size: 1.1rem; font-weight: bold; text-decoration: none; border-radius: 8px;">Check Daily Prices</a>
        </div>
    </section>

    <section class="features-section" id="about">
        <div class="section-header">
            <h2>Market Services</h2>
            <p>Our digital ecosystem provides full transparency and management for all market stakeholders.</p>
        </div>

        <div class="adv-feature-grid">
            <div class="adv-card">
                <i class="fas fa-hand-holding-dollar"></i>
                <h3>Price Index</h3>
                <p>View daily updated wholesale rates for all vegetables and fruits. Data managed directly by market administration.</p>
                <a href="live_prices.php" class="card-link">View Rates <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="adv-card">
                <i class="fas fa-user-check"></i>
                <h3>Farmer Portal</h3>
                <p>Farmers can register to track their sales, view market demand, and receive official price notifications.</p>
                <a href="register.php" class="card-link">Join as Farmer <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="adv-card">
                <i class="fas fa-store-alt"></i>
                <h3>Vendor Network</h3>
                <p>Advanced tools for stall owners to manage inventory, update business status, and stay connected with the hub.</p>
                <a href="login.php" class="card-link">Vendor Login <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    

    <script>
        function fetchLivePrices() {
            const xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("ajax-ticker").innerHTML = this.responseText;
                }
            };
            xhttp.open("GET", "fetch_prices.php", true);
            xhttp.send();
        }
        fetchLivePrices();
        setInterval(fetchLivePrices, 30000);
    </script>
<footer id="contact">
    <div class="footer-grid">
        <div>
            <img src="image/image2.png" alt="ManningHub Logo" class="footer-logo-img"
                 style="height:55px;width:auto;" onerror="this.style.display='none'">
            <p>The central digital hub for ManningHub. Ensuring transparency and efficiency through technology.</p>
        </div>
        <div>
            <h4>Quick Access</h4>
            <p><a href="live_prices.php">Live Market Prices</a></p>
            <p><a href="feedback.php">Feedback</a></p>
            <p><a href="contact.php">Contact Us</a></p>
            <p><a href="announcements.php">Announcements</a></p>
            <p><a href="register.php">Register</a></p>
            <p><a href="login.php">Admin Login</a></p>
            <p><a href="index.php">Home</a></p>
        </div>
        <div>
            <h4>Contact Us</h4>
            <p><i class="fas fa-phone"></i> +94 112 123 456</p>
            <p><i class="fas fa-envelope"></i> admin@manninghub.lk</p>
            <p><i class="fas fa-location-dot"></i> New Manning Market, Peliyagoda</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2026 ManningHub. Built for Progress.
    </div>
</footer>

</body>
</html>