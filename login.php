<?php
session_start();
include 'db.php';

$error = "";

if (isset($_POST['login'])) {
    $email_input = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // 1. Admin Login (Hardcoded)
    if ($email_input === "admin@manninghub.lk" && $password === "Admin@1234") {
        $_SESSION['role'] = 'admin';
        $_SESSION['name'] = 'System Admin';
        header("Location: admin_dashboard.php"); 
        exit();
    }

    // 2. Database Check
    $sql = "SELECT * FROM users WHERE email='$email_input' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {

            // Check approval status for vendors and farmers
            if (($user['role'] == 'vendor' || $user['role'] == 'farmer') && $user['status'] !== 'approved') {
                if ($user['status'] == 'pending') {
                    $error = "Your account is pending admin approval. Please wait.";
                } elseif ($user['status'] == 'rejected') {
                    $error = "Your account has been rejected. Please contact admin.";
                } elseif ($user['status'] == 'suspended') {
                    $error = "Your account has been suspended. Please contact admin.";
                }
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['name']    = $user['full_name'];

                if ($user['role'] == 'vendor') {
                    header("Location: vendor_dashboard.php");
                } elseif ($user['role'] == 'farmer') {
                    header("Location: farmer_dashboard.php");
                } else {
                    header("Location: customer_dashboard.php");
                }
                exit();
            }

        } else {
            $error = "Invalid Password! Please try again.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManningHub | Secure Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #27ae60;
            --secondary: #e67e22;
            --dark: #2c3e50;
        }

        body {
            font-family: 'Inter', sans-serif;
            /* Background image එක මුළු පිටුවටම */
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('image/image3.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-container {
            /* Registration Page එකේ වගේම Glassmorphism Effect එක */
            background: rgba(255, 255, 255, 0.15); 
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            color: white;
        }

        .logo-box { text-align: center; margin-bottom: 20px; }
        /* ඔයාගේ image1.png ලෝගෝ එක */
        .logo-box img { height: 90px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }

        h2 { color: #fff; text-align: center; margin-bottom: 8px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        p.tagline { text-align: center; color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 25px; }

        .input-group { margin-bottom: 20px; position: relative; text-align: left; }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #fff;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 41px;
            color: var(--primary);
            z-index: 1;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            box-sizing: border-box;
            transition: 0.3s;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.9); /* Input field එක සුදු පැහැයෙන් */
            color: #333;
        }

        .input-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 12px rgba(39,174,96,0.4);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }

        .btn-login:hover {
            background: #219150;
            transform: translateY(-2px);
        }

        .error-msg {
            background: rgba(255, 240, 240, 0.9);
            color: #d9534f;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 5px solid #d9534f;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: bold;
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        .footer-links a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            border-bottom: 1px solid var(--primary);
        }

        .footer-links a:hover { color: var(--primary); }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo-box">
        <img src="image/image1.png" alt="ManningHub Logo">
    </div>

    <h2>Login</h2>
    <p class="tagline">Welcome back! Please enter your details.</p>

    <?php if($error != ""): ?>
        <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label>Email Address</label>
            <i class="fas fa-user"></i>
            <input type="email" name="email" placeholder="Enter your email address" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>

        <button type="submit" name="login" class="btn-login">Sign In</button>
    </form>

    <div class="footer-links">
        Don't have an account? <a href="register.php">Register Now</a>
    </div>
</div>

</body>
</html>