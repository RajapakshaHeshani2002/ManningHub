<?php
// 1. Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "manninghub");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. දත්ත UPDATE කිරීම (Save Button එක එබූ විට)
if (isset($_POST['update_settings'])) {
    $sys_name   = $_POST['system_name'];
    $sys_email  = $_POST['contact_email'];
    $open_time  = $_POST['open_time'];
    $close_time = $_POST['close_time'];
    $currency   = $_POST['currency'];
    $upd_freq   = $_POST['update_freq'];
    $ticker_msg = $_POST['maintenance_text'];
    $allow_reg  = $_POST['allow_reg'];

    // SQL Update Query - මෙහි Column names ඔයාගේ DB එකට සමාන විය යුතුය
    $sql = "UPDATE system_settings SET 
            system_name = '$sys_name', 
            contact_email = '$sys_email', 
            open_time = '$open_time', 
            close_time = '$close_time', 
            currency = '$currency', 
            update_freq = '$upd_freq', 
            maintenance_text = '$ticker_msg', 
            allow_registrations = '$allow_reg' 
            WHERE id=1";

    if ($conn->query($sql) === TRUE) {
        $msg = "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;'><i class='fas fa-check-circle'></i> Settings Updated Successfully!</div>";
    } else {
        $msg = "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;'>Error: " . $conn->error . "</div>";
    }
}

// 3. Database එකෙන් දත්ත ලබා ගැනීම (ERROR එක වැළැක්වීමට Safety Check එකක් සහිතව)
$res = $conn->query("SELECT * FROM system_settings WHERE id=1");

if ($res && $res->num_rows > 0) {
    $st = $res->fetch_assoc();
} else {
    // Database එකේ දත්ත නැතිනම් පෙන්විය යුතු Default අගයන් (කලින් ආපු Error එක මින් විසඳේ)
    $st = [
        'system_name' => 'ManningHub Portal',
        'contact_email' => 'admin@manninghub.lk',
        'open_time' => '04:00',
        'close_time' => '22:00',
        'currency' => 'Rs.',
        'update_freq' => 'Daily',
        'maintenance_text' => 'Welcome to ManningHub.',
        'allow_registrations' => 'Yes, Enable'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | ManningHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #27ae60; --dark: #2c3e50; --light: #f4f7f6; --white: #ffffff; }
        body { font-family: 'Inter', sans-serif; background-color: var(--light); margin: 0; display: flex; }
        .sidebar { width: 250px; height: 100vh; background: var(--dark); color: white; padding: 20px; position: fixed; }
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .settings-container { background: var(--white); padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); max-width: 850px; }
        .section-title { border-bottom: 2px solid var(--light); padding-bottom: 10px; margin-bottom: 25px; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
        input:focus { border-color: var(--primary); outline: none; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: #219150; transform: translateY(-2px); }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; background: #e8f5e9; color: var(--primary); font-weight: bold; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>ManningHub</h2>
        <p style="font-size: 12px; opacity: 0.7;">Admin Control Panel</p>
        <hr style="opacity: 0.2; margin: 20px 0;">
        <nav style="display: flex; flex-direction: column; gap: 15px;">
            <p><i class="fas fa-th-large"></i> Dashboard</p>
            <p><i class="fas fa-users"></i> Manage Users</p>
            <p style="color: var(--primary); font-weight: bold;"><i class="fas fa-cog"></i> Settings</p>
            <a href="index.php" style="color:white; text-decoration:none;"><i class="fas fa-eye"></i> View Website</a>
        </nav>
    </div>

    <div class="main-content">
        <?php if(isset($msg)) echo $msg; ?>

        <div class="settings-container">
            <div class="section-title">
                <i class="fas fa-tools"></i>
                <h2>System Configuration</h2>
                <span class="status-badge">System Live</span>
            </div>

            <form action="settings.php" method="POST">
                <div class="form-grid">
                    
                    <div class="form-group">
                        <label>System Name</label>
                        <input type="text" name="system_name" value="<?php echo htmlspecialchars($st['system_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($st['contact_email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Market Opening Time</label>
                        <input type="time" name="open_time" value="<?php echo $st['open_time']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Market Closing Time</label>
                        <input type="time" name="close_time" value="<?php echo $st['close_time']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="currency" value="<?php echo htmlspecialchars($st['currency']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Price Update Frequency</label>
                        <select name="update_freq">
                            <option value="Real-time" <?php if($st['update_freq'] == 'Real-time') echo 'selected'; ?>>Real-time</option>
                            <option value="Daily" <?php if($st['update_freq'] == 'Daily') echo 'selected'; ?>>Daily (Every Morning)</option>
                            <option value="Manual" <?php if($st['update_freq'] == 'Manual') echo 'selected'; ?>>Manual Only</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>System Maintenance Notice (Ticker Text)</label>
                        <textarea name="maintenance_text" rows="3"><?php echo htmlspecialchars($st['maintenance_text']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Allow New Registrations</label>
                        <select name="allow_reg">
                            <option value="Yes, Enable" <?php if($st['allow_registrations'] == 'Yes, Enable') echo 'selected'; ?>>Yes, Enable</option>
                            <option value="No, Disable" <?php if($st['allow_registrations'] == 'No, Disable') echo 'selected'; ?>>No, Disable Temporarily</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Admin System Version</label>
                        <input type="text" value="v1.0.4-stable" disabled style="background: #f9f9f9;">
                    </div>

                </div>

                <hr style="opacity: 0.1; margin: 20px 0;">
                
                <button type="submit" name="update_settings" class="btn-save">
                    <i class="fas fa-save"></i> Save System Settings
                </button>
            </form>
        </div>
    </div>

</body>
</html>