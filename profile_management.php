<?php
session_start();
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit();
}
include 'db.php';

$role = $_SESSION['role'];
$uid  = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? '';

// ── Dashboard links per role ─────────────────────────────────────────────────
$dash_map = [
    'admin'    => 'admin_dashboard.php',
    'vendor'   => 'vendor_dashboard.php',
    'farmer'   => 'farmer_dashboard.php',
    'customer' => 'customer_dashboard.php',
];
$dash = $dash_map[$role] ?? 'index.php';

// ── Role colours ─────────────────────────────────────────────────────────────
$role_colors = [
    'admin'    => '#e74c3c',
    'vendor'   => '#27ae60',
    'farmer'   => '#3498db',
    'customer' => '#8e44ad',
];
$rc = $role_colors[$role] ?? '#27ae60';

// ── Load user from users table ───────────────────────────────────────────────
$ures = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1");
$user = ($ures && $ures->num_rows > 0) ? $ures->fetch_assoc() : [];

// For admin — load from admin table
if ($role === 'admin') {
    $ares = $conn->query("SELECT * FROM admin LIMIT 1");
    $admin_row = ($ares && $ares->num_rows > 0) ? $ares->fetch_assoc() : [];
}

// For farmer — load category
$cat_name = '';
if ($role === 'farmer' && !empty($user['id'])) {
    $fres = $conn->query("SELECT f.*, pc.category_name FROM farmers f
                          LEFT JOIN product_categories pc ON f.category_id = pc.category_id
                          WHERE f.email='" . $conn->real_escape_string($user['email'] ?? '') . "' LIMIT 1");
    $farmer_row = ($fres && $fres->num_rows > 0) ? $fres->fetch_assoc() : [];
    $cat_name = $farmer_row['category_name'] ?? '';
}

// ────────────────────────────────────────────────────────────────────────────
// HANDLE: Update profile details
// ────────────────────────────────────────────────────────────────────────────
$success_msg = '';
$error_msg   = '';

if (isset($_POST['update_profile'])) {
    $new_name  = $conn->real_escape_string(trim($_POST['full_name']  ?? ''));
    $new_phone = $conn->real_escape_string(trim($_POST['phone']      ?? ''));
    $new_addr  = $conn->real_escape_string(trim($_POST['address']    ?? ''));
    $new_loc   = $conn->real_escape_string(trim($_POST['farm_location'] ?? ''));
    $new_crops = $conn->real_escape_string(trim($_POST['crops']      ?? ''));

    if (empty($new_name) || empty($new_phone)) {
        $error_msg = 'Name and phone number are required.';
    } else {
        if ($role === 'vendor') {
            // Update users table
            $conn->query("UPDATE users SET full_name='$new_name', phone='$new_phone', address='$new_addr' WHERE id=$uid");
            // Update vendors table
            $conn->query("UPDATE vendors SET name='$new_name', phone='$new_phone', address='$new_addr'
                          WHERE email='" . $conn->real_escape_string($user['email']) . "'");
        } elseif ($role === 'farmer') {
            $conn->query("UPDATE users SET full_name='$new_name', phone='$new_phone', farm_location='$new_loc', crops='$new_crops' WHERE id=$uid");
            $conn->query("UPDATE farmers SET name='$new_name', phone='$new_phone', location='$new_loc', crop='$new_crops'
                          WHERE email='" . $conn->real_escape_string($user['email']) . "'");
        } elseif ($role === 'customer') {
            $conn->query("UPDATE users SET full_name='$new_name', phone='$new_phone', address='$new_addr' WHERE id=$uid");
        } elseif ($role === 'admin') {
            $conn->query("UPDATE admin SET name='$new_name' WHERE id=1");
            $conn->query("UPDATE users SET full_name='$new_name' WHERE id=$uid");
        }

        // Update session name
        $_SESSION['name'] = $new_name;
        $success_msg = 'Profile updated successfully!';

        // Reload user data
        $ures = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1");
        $user = ($ures && $ures->num_rows > 0) ? $ures->fetch_assoc() : $user;
    }
}

// ────────────────────────────────────────────────────────────────────────────
// HANDLE: Change password
// ────────────────────────────────────────────────────────────────────────────
$pw_success = '';
$pw_error   = '';

if (isset($_POST['change_password'])) {
    $current_pw  = $_POST['current_password']  ?? '';
    $new_pw      = $_POST['new_password']      ?? '';
    $confirm_pw  = $_POST['confirm_password']  ?? '';

    // Get stored hash
    $stored_hash = $user['password'] ?? '';

    // Verify current password
    $pw_ok = false;
    if ($role === 'admin') {
        // Admin may have plain text password (legacy)
        $pw_ok = ($current_pw === ($admin_row['password'] ?? '')) || password_verify($current_pw, $stored_hash);
    } else {
        $pw_ok = password_verify($current_pw, $stored_hash);
    }

    if (!$pw_ok) {
        $pw_error = 'Current password is incorrect.';
    } elseif (strlen($new_pw) < 8) {
        $pw_error = 'New password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $new_pw)) {
        $pw_error = 'New password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_pw)) {
        $pw_error = 'New password must contain at least one number.';
    } elseif ($new_pw !== $confirm_pw) {
        $pw_error = 'New password and confirmation do not match.';
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE id=$uid");

        // Also update role-specific table
        if ($role === 'vendor') {
            $conn->query("UPDATE vendors SET password='$hashed' WHERE email='" . $conn->real_escape_string($user['email']) . "'");
        } elseif ($role === 'farmer') {
            $conn->query("UPDATE farmers SET password='$hashed' WHERE email='" . $conn->real_escape_string($user['email']) . "'");
        } elseif ($role === 'admin') {
            $conn->query("UPDATE admin SET password='$hashed' WHERE id=1");
        }

        $pw_success = 'Password changed successfully!';

        // Auto-notification
        if ($role !== 'admin') {
            $conn->query("INSERT INTO notifications (user_id, role, title, type, message, is_read, created_at)
                          VALUES ($uid, '$role', 'Password Changed', 'success',
                          'Your ManningHub account password was changed successfully. If you did not do this please contact admin immediately.', 0, NOW())");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | ManningHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#27ae60; --rc:<?php echo $rc; ?>; --dark:#0d1b2a; --sidebar:#1a2535; --bg:#f0f4f0; --danger:#e74c3c; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); display:flex; min-height:100vh; }

        /* ── SIDEBAR ── */
        .sidebar { width:240px; min-height:100vh; background:var(--sidebar); color:white; position:fixed; top:0; left:0; display:flex; flex-direction:column; }
        .sidebar-logo { padding:24px 20px 18px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-logo img { height:50px; margin-bottom:8px; display:block; }
        .sidebar-logo h2 { font-family:'Outfit',sans-serif; font-size:18px; font-weight:800; color:var(--rc); }
        .sidebar-logo p  { font-size:11px; color:rgba(255,255,255,.4); margin-top:2px; }
        .sidebar-menu { list-style:none; padding:14px 10px; flex:1; }
        .sidebar-menu li a { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.65); text-decoration:none; padding:10px 14px; border-radius:10px; font-size:13px; font-weight:500; transition:.2s; margin-bottom:3px; }
        .sidebar-menu li a:hover  { background:rgba(255,255,255,.08); color:white; }
        .sidebar-menu li a.active { background:var(--rc); color:white; }
        .sidebar-menu li a i { width:16px; text-align:center; font-size:14px; }
        .menu-label { font-size:10px; text-transform:uppercase; color:rgba(255,255,255,.3); letter-spacing:1px; padding:10px 14px 4px; display:block; }

        /* ── MAIN ── */
        .main { margin-left:240px; width:calc(100% - 240px); padding:28px; }

        /* ── PAGE HEAD ── */
        .page-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
        .page-head h1 { font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--dark); display:flex; align-items:center; gap:10px; }
        .page-head p  { font-size:13px; color:#888; margin-top:4px; }

        /* ── GRID LAYOUT ── */
        .profile-grid { display:grid; grid-template-columns:300px 1fr; gap:22px; align-items:start; }
        @media(max-width:900px) { .profile-grid { grid-template-columns:1fr; } }

        /* ── PROFILE CARD (left) ── */
        .profile-card { background:white; border-radius:18px; padding:28px; box-shadow:0 2px 14px rgba(0,0,0,.06); text-align:center; }
        .avatar { width:90px; height:90px; border-radius:50%; background:var(--rc); color:white; font-family:'Outfit',sans-serif; font-size:36px; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:0 4px 16px rgba(0,0,0,.15); }
        .profile-name { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--dark); margin-bottom:6px; }
        .role-badge { display:inline-block; padding:4px 16px; border-radius:20px; font-size:12px; font-weight:700; background:var(--rc); color:white; margin-bottom:14px; }
        .profile-meta { font-size:13px; color:#888; line-height:2; }
        .profile-meta strong { color:var(--dark); }
        .status-badge { display:inline-block; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; margin-top:10px; }
        .s-approved  { background:#eafaf1; color:#1e8449; }
        .s-pending   { background:#fef9ec; color:#856404; }
        .s-suspended { background:#fdecea; color:#c0392b; }

        /* info row */
        .info-row { display:flex; align-items:center; gap:8px; padding:10px 0; border-bottom:1px solid #f1f2f6; font-size:13px; }
        .info-row:last-child { border-bottom:none; }
        .info-row i { color:var(--rc); width:18px; text-align:center; }
        .info-row span { color:#888; font-size:12px; min-width:80px; }
        .info-row strong { color:var(--dark); }

        /* ── FORMS (right) ── */
        .forms-col { display:flex; flex-direction:column; gap:20px; }
        .section-box { background:white; border-radius:18px; padding:26px; box-shadow:0 2px 14px rgba(0,0,0,.06); }
        .sec-title { font-family:'Outfit',sans-serif; font-size:17px; font-weight:800; color:var(--dark); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .sec-title i { color:var(--rc); }

        .form-row   { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:12px; font-weight:700; color:#555; margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
        .form-group input, .form-group textarea {
            width:100%; padding:11px 14px; border:1.5px solid #e8e8e8;
            border-radius:9px; font-size:14px; outline:none; transition:.2s;
            font-family:'Segoe UI',sans-serif; color:var(--dark);
        }
        .form-group input:focus, .form-group textarea:focus { border-color:var(--rc); box-shadow:0 0 0 3px rgba(39,174,96,.08); }
        .form-group input.locked { background:#f8f9fa; color:#aaa; cursor:not-allowed; border-style:dashed; }
        .lock-hint { font-size:11px; color:#bbb; margin-top:4px; display:flex; align-items:center; gap:4px; }
        .form-group textarea { resize:vertical; min-height:80px; }

        .btn-save { background:var(--rc); color:white; border:none; padding:12px 28px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; transition:.2s; font-family:'Outfit',sans-serif; }
        .btn-save:hover { filter:brightness(.9); }

        /* password strength */
        .pw-strength { height:5px; border-radius:3px; margin-top:6px; transition:.3s; background:#f0f0f0; }
        .pw-hint { font-size:11px; margin-top:4px; color:#aaa; }

        /* alerts */
        .alert { padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#eafaf1; color:#1e8449; border-left:4px solid #27ae60; }
        .alert-error   { background:#fdecea; color:#c0392b; border-left:4px solid var(--danger); }

        .pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#aaa; font-size:15px; }
        .pw-wrap   { position:relative; }
        .pw-wrap input { padding-right:40px; }
    </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="image/image1.png" alt="Logo" onerror="this.style.display='none'">
        <h2>ManningHub</h2>
        <p><?php echo ucfirst($role); ?> Portal</p>
    </div>
    <ul class="sidebar-menu">
        <span class="menu-label">Navigation</span>
        <li><a href="<?php echo $dash; ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="live_prices.php"><i class="fas fa-chart-line"></i> Live Prices</a></li>
        <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
        <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <span class="menu-label">My Account</span>
        <li><a href="profile_management.php" class="active"><i class="fas fa-user-edit"></i> My Profile</a></li>
        <li><a href="logout.php" style="color:#e74c3c !important;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- ── MAIN ── -->
<div class="main">

    <div class="page-head">
        <div>
            <h1><i class="fas fa-user-edit" style="color:var(--rc);"></i> My Profile</h1>
            <p>Manage your account information and password</p>
        </div>
        <a href="<?php echo $dash; ?>" style="font-size:13px;color:var(--rc);text-decoration:none;font-weight:600;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="profile-grid">

        <!-- ── LEFT: Profile Info Card ── -->
        <div class="profile-card">
            <!-- Avatar -->
            <div class="avatar">
                <?php echo strtoupper(substr($user['full_name'] ?? $name, 0, 1)); ?>
            </div>

            <div class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?? $name); ?></div>
            <div class="role-badge"><i class="fas fa-<?php echo ['admin'=>'shield-alt','vendor'=>'store','farmer'=>'tractor','customer'=>'shopping-bag'][$role]??'user'; ?>"></i> <?php echo ucfirst($role); ?></div>

            <!-- Status -->
            <?php if ($role !== 'admin'): ?>
            <?php $st = $user['status'] ?? 'approved'; ?>
            <div class="status-badge s-<?php echo $st; ?>">
                <?php echo ['approved'=>'✅ Approved','pending'=>'⏳ Pending','suspended'=>'🚫 Suspended','rejected'=>'❌ Rejected'][$st] ?? ucfirst($st); ?>
            </div>
            <?php endif; ?>

            <div style="margin-top:20px;text-align:left;">
                <!-- Email -->
                <div class="info-row">
                    <i class="fas fa-envelope"></i>
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></strong>
                </div>

                <!-- Role-specific info -->
                <?php if ($role === 'vendor'): ?>
                <div class="info-row">
                    <i class="fas fa-store"></i>
                    <span>Stall</span>
                    <strong><?php echo htmlspecialchars($user['stall_number'] ?? 'N/A'); ?></strong>
                </div>
                <div class="info-row">
                    <i class="fas fa-id-card"></i>
                    <span>Permit</span>
                    <strong><?php echo htmlspecialchars($user['permit_number'] ?? 'N/A'); ?></strong>
                </div>
                <div class="info-row">
                    <i class="fas fa-tag"></i>
                    <span>Reg ID</span>
                    <strong><?php echo htmlspecialchars($user['vendor_reg_id'] ?? 'N/A'); ?></strong>
                </div>
                <?php elseif ($role === 'farmer'): ?>
                <div class="info-row">
                    <i class="fas fa-seedling"></i>
                    <span>Category</span>
                    <strong><?php echo htmlspecialchars($cat_name ?: 'N/A'); ?></strong>
                </div>
                <div class="info-row">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Location</span>
                    <strong><?php echo htmlspecialchars($user['farm_location'] ?? 'N/A'); ?></strong>
                </div>
                <?php endif; ?>

                <!-- Phone -->
                <div class="info-row">
                    <i class="fas fa-phone"></i>
                    <span>Phone</span>
                    <strong><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></strong>
                </div>

                <!-- Member Since -->
                <div class="info-row">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Member</span>
                    <strong><?php echo $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : 'N/A'; ?></strong>
                </div>
            </div>
        </div>

        <!-- ── RIGHT: Forms ── -->
        <div class="forms-col">

            <!-- ── EDIT DETAILS FORM ── -->
            <div class="section-box">
                <div class="sec-title"><i class="fas fa-user-edit"></i> Edit Profile Details</div>

                <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name"
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? $name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="text" name="phone"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="e.g. 0771234567" required>
                        </div>
                    </div>

                    <!-- Email — locked for all roles -->
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                               class="locked" readonly>
                        <div class="lock-hint"><i class="fas fa-lock"></i> Email cannot be changed. Contact admin if needed.</div>
                    </div>

                    <?php if ($role === 'vendor'): ?>
                    <!-- Vendor: locked identity fields -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stall Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['stall_number'] ?? ''); ?>" class="locked" readonly>
                            <div class="lock-hint"><i class="fas fa-lock"></i> Assigned by admin</div>
                        </div>
                        <div class="form-group">
                            <label>Permit Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['permit_number'] ?? ''); ?>" class="locked" readonly>
                            <div class="lock-hint"><i class="fas fa-lock"></i> Cannot be changed</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" placeholder="Your address..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <?php elseif ($role === 'farmer'): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Farm Location</label>
                            <input type="text" name="farm_location"
                                   value="<?php echo htmlspecialchars($user['farm_location'] ?? ''); ?>"
                                   placeholder="e.g. Nuwara Eliya">
                        </div>
                        <div class="form-group">
                            <label>Crops Grown</label>
                            <input type="text" name="crops"
                                   value="<?php echo htmlspecialchars($user['crops'] ?? ''); ?>"
                                   placeholder="e.g. Carrot, Cabbage">
                        </div>
                    </div>

                    <?php elseif ($role === 'customer'): ?>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" placeholder="Your address..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- ── CHANGE PASSWORD FORM ── -->
            <div class="section-box">
                <div class="sec-title"><i class="fas fa-lock"></i> Change Password</div>

                <?php if (!empty($pw_success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $pw_success; ?></div>
                <?php endif; ?>
                <?php if (!empty($pw_error)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $pw_error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="change_password" value="1">

                    <div class="form-group">
                        <label>Current Password *</label>
                        <div class="pw-wrap">
                            <input type="password" name="current_password" id="curPw" placeholder="Enter your current password" required>
                            <button type="button" class="pw-toggle" onclick="togglePw('curPw',this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password *</label>
                            <div class="pw-wrap">
                                <input type="password" name="new_password" id="newPw"
                                       placeholder="Min 8 chars, 1 uppercase, 1 number"
                                       oninput="checkStrength(this.value)" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('newPw',this)"><i class="fas fa-eye"></i></button>
                            </div>
                            <div class="pw-strength" id="pwBar"></div>
                            <div class="pw-hint"  id="pwHint">Enter a new password</div>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <div class="pw-wrap">
                                <input type="password" name="confirm_password" id="confPw"
                                       placeholder="Re-enter new password"
                                       oninput="checkMatch()" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('confPw',this)"><i class="fas fa-eye"></i></button>
                            </div>
                            <div class="pw-hint" id="matchHint"></div>
                        </div>
                    </div>

                    <div style="background:#f8f9fa;border-radius:10px;padding:12px 14px;font-size:12px;color:#888;margin-bottom:18px;line-height:1.8;">
                        <i class="fas fa-info-circle" style="color:var(--rc);"></i>
                        Password must be at least <strong>8 characters</strong>, contain at least
                        <strong>1 uppercase letter</strong> and <strong>1 number</strong>.
                    </div>

                    <button type="submit" class="btn-save" style="background:var(--dark);">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>

        </div><!-- end forms-col -->
    </div><!-- end profile-grid -->
</div><!-- end main -->

<script>
// Password strength checker
function checkStrength(val) {
    var bar  = document.getElementById('pwBar');
    var hint = document.getElementById('pwHint');
    var score = 0;
    if (val.length >= 8)              score++;
    if (/[A-Z]/.test(val))            score++;
    if (/[0-9]/.test(val))            score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    var colors = ['#e74c3c','#e67e22','#f1c40f','#27ae60'];
    var labels = ['Too weak','Weak','Good','Strong'];
    var w      = [25,50,75,100];

    if (val.length === 0) {
        bar.style.width = '0'; bar.style.background = '#f0f0f0';
        hint.textContent = 'Enter a new password'; hint.style.color = '#aaa';
    } else {
        var idx = Math.min(score - 1, 3);
        bar.style.width      = w[score-1] + '%';
        bar.style.background = colors[idx] || '#e74c3c';
        hint.textContent     = labels[idx] || 'Too weak';
        hint.style.color     = colors[idx] || '#e74c3c';
    }
    checkMatch();
}

function checkMatch() {
    var np  = document.getElementById('newPw').value;
    var cp  = document.getElementById('confPw').value;
    var h   = document.getElementById('matchHint');
    if (!cp) { h.textContent = ''; return; }
    if (np === cp) {
        h.textContent = '✓ Passwords match'; h.style.color = '#27ae60';
    } else {
        h.textContent = '✗ Passwords do not match'; h.style.color = '#e74c3c';
    }
}

function togglePw(id, btn) {
    var inp = document.getElementById(id);
    var showing = inp.type === 'text';
    inp.type = showing ? 'password' : 'text';
    btn.innerHTML = showing ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}
</script>
</body>
</html>
