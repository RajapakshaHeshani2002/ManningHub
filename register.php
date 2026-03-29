<?php

session_start();
include 'db.php';

$message     = '';
$msg_type    = 'error';

if (isset($_POST['verify_register'])) {

    $phone        = $conn->real_escape_string(trim($_POST['phone']));
    $email        = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $entered_otp  = $conn->real_escape_string(trim($_POST['otp']));
    $password     = $_POST['password'];
    $confirm_pw   = $_POST['confirm_password'] ?? '';
    $role         = $conn->real_escape_string(trim($_POST['role']));
    $full_name    = $conn->real_escape_string(trim($_POST['full_name']));

    // Validate OTP 
    // Check: correct OTP, not expired, still pending
    $now = date('Y-m-d H:i:s');
    $otp_check = $conn->query(
        "SELECT * FROM otp_verification 
         WHERE email = '$email' 
           AND otp_code = '$entered_otp' 
           AND verification_status = 'pending'
           AND expiry_time > '$now'
         ORDER BY id DESC LIMIT 1"
    );

    if ($otp_check->num_rows === 0) {
        // Check if OTP exists but expired
        $exp_check = $conn->query(
            "SELECT * FROM otp_verification 
             WHERE email = '$email' AND otp_code = '$entered_otp'
             ORDER BY id DESC LIMIT 1"
        );
        if ($exp_check->num_rows > 0) {
            $message = 'OTP has expired. Please request a new OTP.';
        } else {
            $message = 'Invalid OTP. Please check and try again.';
        }

    } else {
        $otp_row = $otp_check->fetch_assoc();

        // Mark OTP as verified 
        $conn->query(
            "UPDATE otp_verification 
             SET verification_status = 'verified' 
             WHERE id = {$otp_row['id']}"
        );

        // Check passwords match
        if ($password !== $confirm_pw) {
            $message = '❌ Passwords do not match. Please re-enter your password.';
        } else

        // Validate password strength
        $pw_error = '';
        if (strlen($password) < 8) {
            $pw_error = '❌ Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $pw_error = '❌ Password must contain at least one uppercase letter (A-Z).';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $pw_error = '❌ Password must contain at least one lowercase letter (a-z).';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $pw_error = '❌ Password must contain at least one number (0-9).';
        } elseif (!preg_match('/[\W_]/', $password)) {
            $pw_error = '❌ Password must contain at least one special character (!@#$%&*).';
        }

        if ($pw_error) {
            $message = $pw_error;
        } else {

        // Hash password
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);

        // Role-specific fields
        $address       = $conn->real_escape_string(trim($_POST['address'] ?? ''));
        $stall         = $conn->real_escape_string($_SESSION['stall_number'] ?? '');
        $permit        = $conn->real_escape_string(trim($_POST['permit_number'] ?? ''));
        $farm_location = $conn->real_escape_string(trim($_POST['farm_location'] ?? ''));
        $crops         = $conn->real_escape_string(trim($_POST['crops'] ?? ''));
        $category_id   = intval($_POST['category_id'] ?? 0);

        $vendor_reg_id = '';

        // Customers are approved immediately — vendors and farmers need admin approval
        $initial_status = ($role === 'customer') ? 'approved' : 'pending';

        // Insert into role-specific table 
        if ($role === 'vendor') {
            $vendor_reg_id = 'VEND' . rand(1000, 9999);
            $conn->query(
                "INSERT INTO vendors (name, email, phone, password, stall_number, permit_number, address, vendor_reg_id, status)
                 VALUES ('$full_name','$email','$phone','$hashed_pw','$stall','$permit','$address','$vendor_reg_id','$initial_status')"
            );

        } elseif ($role === 'farmer') {
            $conn->query(
                "INSERT INTO farmers (name, email, phone, nic, location, crop, category_id, password, status)
                 VALUES ('$full_name','$email','$phone','','$farm_location','$crops','$category_id','$hashed_pw','$initial_status')"
            );
        }

        // Insert into unified users table 
        $sql = "INSERT INTO users 
                    (role, full_name, email, phone, password, address, stall_number, permit_number, farm_location, crops, vendor_reg_id, status)
                VALUES 
                    ('$role','$full_name','$email','$phone','$hashed_pw','$address','$stall','$permit','$farm_location','$crops','$vendor_reg_id','$initial_status')";

        if ($conn->query($sql)) {
            if ($role === 'customer') {
                $message = 'Registration successful! You can now <a href="login.php" style="color:#27ae60;font-weight:700;">log in</a> to your account.';
            } else {
                $message = 'Registration successful! Your account is <strong>pending admin approval</strong>. You will be notified once approved.';
            }
            $msg_type = 'success';
            session_destroy();
            session_start();
        } else {
            $message = 'Registration error: ' . $conn->error;
        }
        } // end password strength check
    }
}

// Load categories for farmer dropdown
$categories = $conn->query("SELECT * FROM product_categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManningHub | Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #27ae60; --dark: #2c3e50; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body {
            background: linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('image/image4.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 40px 20px;
        }
        .reg-card {
            background: rgba(255,255,255,0.14);
            width: 100%; max-width: 490px;
            padding: 35px; border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
        .logo-box { text-align: center; margin-bottom: 18px; }
        .logo-box img { height: 85px; width: auto; }
        h2 { color: #fff; text-align: center; margin-bottom: 4px; font-weight: 800; font-size: 26px; }
        p.sub-text { text-align: center; color: rgba(255,255,255,0.75); font-size: 13px; margin-bottom: 20px; }
        label { display: block; margin-top: 10px; font-size: 13px; font-weight: 600; color: #fff; }
        input, select {
            width: 100%; padding: 11px 14px; margin: 6px 0;
            border: 1px solid rgba(255,255,255,0.3); border-radius: 11px;
            font-size: 14px; background: rgba(255,255,255,0.92); color: #333; transition: 0.3s;
        }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 10px rgba(39,174,96,0.4); }
        .btn-otp {
            background: #3498db; color: white; border: none;
            padding: 11px; border-radius: 11px; cursor: pointer;
            width: 100%; font-weight: bold; margin: 6px 0 14px; transition: 0.3s;
        }
        .btn-otp:hover { background: #2980b9; }
        .btn-reg {
            background: var(--primary); color: white; border: none;
            padding: 14px; border-radius: 11px; cursor: pointer;
            width: 100%; font-size: 15px; font-weight: bold;
            margin-top: 18px; transition: 0.3s; letter-spacing: 0.5px;
        }
        .btn-reg:hover { background: #219150; transform: translateY(-1px); }
        .hidden { display: none; }
        .msg {
            text-align: center; padding: 12px; border-radius: 11px;
            font-weight: bold; margin-bottom: 16px; font-size: 13px;
        }
        .msg.error { background: rgba(255,235,235,0.9); color: #c0392b; border-left: 4px solid #e74c3c; }
        .msg.success { background: rgba(232,255,240,0.9); color: #1a7a3a; border-left: 4px solid #27ae60; }
        .otp-info { font-size: 11px; color: rgba(255,255,255,0.65); margin: 3px 0 0; }
        .login-redirect {
            text-align: center; margin-top: 22px; padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 13px; color: rgba(255,255,255,0.8);
        }
        .login-redirect a { color: #fff; font-weight: bold; border-bottom: 1px solid var(--primary); text-decoration: none; }
        .pending-note {
            background: rgba(255,243,205,0.9); color: #856404;
            border-left: 4px solid #ffc107; border-radius: 8px;
            padding: 10px 14px; font-size: 12px; margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="reg-card">
    <div class="logo-box">
        <img src="image/image1.png" alt="ManningHub Logo">
    </div>
    <h2>Join ManningHub</h2>
    <p class="sub-text">Create your account to access the digital marketplace</p>

    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo $message; ?></div>
    <?php else: ?>
        <div class="msg error hidden" id="otpStatus"></div>
    <?php endif; ?>

    <form method="POST" id="regForm">
        <label>Select Your Role</label>
        <select name="role" id="role" required>
            <option value="">Choose Your Role</option>
            <option value="customer">Customer</option>
            <option value="vendor">Vendor (Stall Owner)</option>
            <option value="farmer">Farmer</option>
        </select>

        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Full Name" required>

        <label>Email Address</label>
        <input type="email" name="email" id="email" placeholder="your@email.com" required>

        <label>Phone Number</label>
        <input type="text" name="phone" id="phone" placeholder="07XXXXXXXX" required>

        <button type="button" class="btn-otp" id="sendOtpBtn">
            <i class="fas fa-envelope"></i> Send OTP to Email
        </button>
        <p class="otp-info"><i class="fas fa-clock"></i> OTP will be sent to your email inbox — valid for 10 minutes only</p>

        <label>Enter OTP Code</label>
        <input type="text" name="otp" id="otpInput" placeholder="6-Digit OTP Code" maxlength="6" required>

        <label>Create Password</label>
        <div style="position:relative;">
            <input type="password" name="password" id="passwordInput"
                   placeholder="Min 8 chars, upper, lower, number, symbol"
                   required oninput="checkStrength(this.value)"
                   style="padding-right:44px;">
            <span onclick="togglePw()" id="pwEye"
                  style="position:absolute; right:14px; top:50%; transform:translateY(-50%);
                         cursor:pointer; color:#888; font-size:15px;">👁</span>
        </div>
        <!-- Strength bar -->
        <div style="margin-top:6px;">
            <div style="height:5px; border-radius:4px; background:#e0e0e0; overflow:hidden;">
                <div id="strengthBar" style="height:100%; width:0%; border-radius:4px; transition:0.3s;"></div>
            </div>
            <div id="strengthText" style="font-size:11px; margin-top:4px; color:rgba(255,255,255,0.7);"></div>
        </div>
        <!-- Requirements checklist -->
        <div id="pwRequirements" style="margin-top:8px; font-size:11px; color:rgba(255,255,255,0.7); display:none;">
            <div id="req-len">  ✗ At least 8 characters</div>
            <div id="req-upper">✗ At least one uppercase letter (A-Z)</div>
            <div id="req-lower">✗ At least one lowercase letter (a-z)</div>
            <div id="req-num">  ✗ At least one number (0-9)</div>
            <div id="req-sym">  ✗ At least one special character (!@#$%&*)</div>
        </div>

        <label style="margin-top:12px;">Confirm Password</label>
        <div style="position:relative;">
            <input type="password" name="confirm_password" id="confirmInput"
                   placeholder="Re-enter your password"
                   required oninput="checkMatch()"
                   style="padding-right:44px;">
        </div>
        <div id="matchMsg" style="font-size:11px; margin-top:4px; min-height:16px;"></div>

        <!-- Customer fields -->
        <div id="customerFields" class="hidden">
            <label>Home Address</label>
            <input type="text" name="address" placeholder="Your Address">
        </div>

        <!-- Vendor fields -->
        <div id="vendorFields" class="hidden">
            <label>Stall Number (Auto-assigned)</label>
            <input type="text" name="stall_number" id="stall_number" placeholder="Will be assigned after OTP" readonly>
            <label>Manning Market Permit ID</label>
            <input type="text" name="permit_number" placeholder="Permit Number">
            <label>Address</label>
            <input type="text" name="address" placeholder="Business Address">
            <div class="pending-note">
                <i class="fas fa-info-circle"></i>
                Your registration will be reviewed and approved by Admin before you can login.
            </div>
        </div>

        <!-- Farmer fields -->
        <div id="farmerFields" class="hidden">
            <label>Farm Location</label>
            <input type="text" name="farm_location" placeholder="e.g. Nuwara Eliya">
            <label>Main Crops</label>
            <input type="text" name="crops" placeholder="e.g. Carrot, Cabbage">
            <label>Product Category</label>
            <select name="category_id">
                <option value="0">Select Category</option>
                <?php if($categories): while($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['category_id']; ?>">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
            <div class="pending-note">
                <i class="fas fa-info-circle"></i>
                Your registration will be reviewed and approved by Admin before you can login.
            </div>
        </div>

        <button type="submit" name="verify_register" class="btn-reg">
            <i class="fas fa-user-plus"></i> Complete Registration
        </button>
    </form>

    <div class="login-redirect">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<script>
    // Show role-specific fields
    document.getElementById('role').addEventListener('change', function () {
        ['customerFields','vendorFields','farmerFields'].forEach(id => {
            document.getElementById(id).classList.add('hidden');
        });
        if (this.value) {
            const el = document.getElementById(this.value + 'Fields');
            if (el) el.classList.remove('hidden');
        }
    });

    // Send OTP
    document.getElementById('sendOtpBtn').addEventListener('click', function () {
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const role  = document.getElementById('role').value;
        const box   = document.getElementById('otpStatus');
        const btn   = this;

        // Client-side validation first
        if (!role) {
            box.className = 'msg error';
            box.textContent = '❌ Please select your role first.';
            box.classList.remove('hidden');
            return;
        }
        if (!email) {
            box.className = 'msg error';
            box.textContent = '❌ Please enter your email address.';
            box.classList.remove('hidden');
            return;
        }
        if (!email.includes('@') || !email.includes('.')) {
            box.className = 'msg error';
            box.textContent = '❌ Please enter a valid email address (e.g. name@gmail.com).';
            box.classList.remove('hidden');
            return;
        }
        if (!phone) {
            box.className = 'msg error';
            box.textContent = '❌ Please enter your phone number.';
            box.classList.remove('hidden');
            return;
        }

        // Show sending state
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP to your email...';
        box.className = 'msg error';
        box.textContent = '📧 Sending OTP to ' + email + ' — please wait...';
        box.classList.remove('hidden');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'send_otp.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-envelope"></i> Resend OTP';
            try {
                const res = JSON.parse(this.responseText);
                if (res.success) {
                    box.className = 'msg success';
                    box.textContent = res.message;
                    if (res.stall_number) {
                        document.getElementById('stall_number').value = res.stall_number;
                    }
                } else {
                    box.className = 'msg error';
                    box.textContent = res.message;
                }
            } catch (e) {
                box.className = 'msg error';
                box.textContent = '❌ Error sending OTP. Please try again.';
            }
        };
        xhr.onerror = function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-envelope"></i> Send OTP to Email';
            box.className = 'msg error';
            box.textContent = '❌ Network error. Please check your connection and try again.';
        };
        xhr.send('phone=' + encodeURIComponent(phone) +
                 '&email=' + encodeURIComponent(email) +
                 '&role='  + encodeURIComponent(role));
    });

    // ── Password strength checker ──────────────────────────
    function checkStrength(pw) {
        const bar  = document.getElementById('strengthBar');
        const txt  = document.getElementById('strengthText');
        const reqs = document.getElementById('pwRequirements');
        reqs.style.display = pw.length > 0 ? 'block' : 'none';

        const checks = {
            len:   pw.length >= 8,
            upper: /[A-Z]/.test(pw),
            lower: /[a-z]/.test(pw),
            num:   /[0-9]/.test(pw),
            sym:   /[\W_]/.test(pw)
        };

        // Update checklist
        const labels = {
            len:   'At least 8 characters',
            upper: 'At least one uppercase letter (A-Z)',
            lower: 'At least one lowercase letter (a-z)',
            num:   'At least one number (0-9)',
            sym:   'At least one special character (!@#$%&*)'
        };
        for (const [key, ok] of Object.entries(checks)) {
            const el = document.getElementById('req-' + key);
            if (el) {
                el.textContent = (ok ? '✓ ' : '✗ ') + labels[key];
                el.style.color = ok ? '#2ecc71' : 'rgba(255,255,255,0.6)';
            }
        }

        // Score
        const score = Object.values(checks).filter(Boolean).length;
        const levels = [
            { w: '0%',   color: '#e74c3c', label: '' },
            { w: '20%',  color: '#e74c3c', label: '🔴 Very Weak' },
            { w: '40%',  color: '#e67e22', label: '🟠 Weak' },
            { w: '60%',  color: '#f1c40f', label: '🟡 Fair' },
            { w: '80%',  color: '#2ecc71', label: '🟢 Strong' },
            { w: '100%', color: '#27ae60', label: '✅ Very Strong' },
        ];
        bar.style.width      = levels[score].w;
        bar.style.background = levels[score].color;
        txt.textContent      = levels[score].label;

        // Also recheck match if confirm has value
        checkMatch();
    }

    // ── Confirm password match ─────────────────────────────
    function checkMatch() {
        const pw  = document.getElementById('passwordInput').value;
        const cpw = document.getElementById('confirmInput').value;
        const msg = document.getElementById('matchMsg');
        if (!cpw) { msg.textContent = ''; return; }
        if (pw === cpw) {
            msg.textContent = '✅ Passwords match';
            msg.style.color = '#2ecc71';
        } else {
            msg.textContent = '❌ Passwords do not match';
            msg.style.color = '#e74c3c';
        }
    }

    // ── Toggle password visibility ─────────────────────────
    function togglePw() {
        const inp = document.getElementById('passwordInput');
        inp.type = inp.type === 'password' ? 'text' : 'password';
    }

    // ── Block submit if password too weak or not matching ──
    document.getElementById('regForm').addEventListener('submit', function(e) {
        const pw  = document.getElementById('passwordInput').value;
        const cpw = document.getElementById('confirmInput').value;
        const box = document.getElementById('otpStatus');

        if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[a-z]/.test(pw) ||
            !/[0-9]/.test(pw) || !/[\W_]/.test(pw)) {
            e.preventDefault();
            box.className = 'msg error';
            box.textContent = '❌ Password does not meet the requirements. Please check the checklist below the password field.';
            box.classList.remove('hidden');
            document.getElementById('passwordInput').focus();
            return;
        }
        if (pw !== cpw) {
            e.preventDefault();
            box.className = 'msg error';
            box.textContent = '❌ Passwords do not match. Please re-enter your password.';
            box.classList.remove('hidden');
            document.getElementById('confirmInput').focus();
            return;
        }
    });

</script>
</body>
</html>
