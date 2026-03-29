<?php
// මෙතැනදී Backend එකට අවශ්‍ය දත්ත සම්බන්ධ කිරීම් සිදු කළ හැක
// උදා: $conn = new mysqli("localhost", "root", "", "manning_db");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Management | ManningHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #27ae60;
            --secondary: #e67e22;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --bg: #f8f9fa;
            --white: #ffffff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            margin: 0;
            display: flex;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 260px;
            background: var(--dark);
            height: 100vh;
            color: white;
            padding: 20px;
            position: fixed;
        }

        .sidebar h2 { font-size: 20px; border-bottom: 1px solid #444; padding-bottom: 15px; margin-bottom: 25px; }
        .sidebar-link {
            display: block;
            color: #bdc3c7;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: 0.3s;
        }
        .sidebar-link.active, .sidebar-link:hover { background: var(--primary); color: white; }

        /* --- Main Content --- */
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 40px;
        }

        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* --- Price Update Form Card --- */
        .card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: flex-end;
        }

        .input-group { display: flex; flex-direction: column; }
        .input-group label { font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .input-group input, .input-group select {
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            outline: none;
        }

        .btn-update {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-update:hover { background: #219150; transform: translateY(-2px); }

        /* --- Price Table --- */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        table th {
            background: #f1f3f5;
            padding: 15px;
            text-align: left;
            font-size: 14px;
            color: #666;
        }

        table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 15px;
        }

        .price-up { color: var(--danger); font-weight: bold; }
        .price-down { color: var(--primary); font-weight: bold; }

        .badge-category {
            padding: 5px 12px;
            background: #e8f5e9;
            color: var(--primary);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>ManningHub Panel</h2>
        <a href="dashboard.php" class="sidebar-link">Dashboard</a>
        <a href="price_management.php" class="sidebar-link active">Price Management</a>
        <a href="stock_control.php" class="sidebar-link">Stock Control</a>
        <a href="users.php" class="sidebar-link">User Management</a>
        <a href="logout.php" class="sidebar-link" style="margin-top: 50px; color: #e74c3c;">Logout</a>
    </div>

    <div class="main-content">
        <div class="header-box">
            <h1>Update Market Prices</h1>
            <div style="color: #666;"><i class="fas fa-calendar"></i> Today: <?php echo date('Y-m-d'); ?></div>
        </div>

        <div class="card">
            <h3>Update Daily Rates</h3>
            <p style="color: #888; margin-bottom: 20px;">මෙතැනින් නව එළවළු මිල ගණන් පද්ධතියට ඇතුළත් කරන්න.</p>
            
            <form action="process_price.php" method="POST" class="form-grid">
                <div class="input-group">
                    <label>Vegetable / Fruit</label>
                    <select name="item_id" required>
                        <option value="">Select Item</option>
                        <option value="1">Carrot (කැරට්)</option>
                        <option value="2">Tomato (තක්කාලි)</option>
                        <option value="3">Leeks (ලීක්ස්)</option>
                        <option value="4">Green Chilli (අමු මිරිස්)</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Current Wholesale Price (Rs.)</label>
                    <input type="number" name="new_price" placeholder="e.g. 250" required>
                </div>

                <div class="input-group">
                    <label>Unit</label>
                    <select name="unit">
                        <option value="kg">Per Kg (කිලෝවකට)</option>
                        <option value="bundle">Per Bundle (මිටියකට)</option>
                    </select>
                </div>

                <button type="submit" name="update_price" class="btn-update">
                    <i class="fas fa-sync-alt"></i> Update Price
                </button>
            </form>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Current Live Prices</h3>
                <input type="text" placeholder="Search item..." style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; width: 250px;">
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Last Price</th>
                        <th>New Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Carrot (කැරට්)</strong></td>
                        <td><span class="badge-category">Vegetable</span></td>
                        <td>Rs. 220.00</td>
                        <td>Rs. 240.00</td>
                        <td><span class="price-up"><i class="fas fa-arrow-up"></i> 9.1%</span></td>
                        <td><button style="border:none; background:none; color:var(--secondary); cursor:pointer;"><i class="fas fa-edit"></i></button></td>
                    </tr>
                    <tr>
                        <td><strong>Leeks (ලීක්ස්)</strong></td>
                        <td><span class="badge-category">Vegetable</span></td>
                        <td>Rs. 190.00</td>
                        <td>Rs. 180.00</td>
                        <td><span class="price-down"><i class="fas fa-arrow-down"></i> 5.2%</span></td>
                        <td><button style="border:none; background:none; color:var(--secondary); cursor:pointer;"><i class="fas fa-edit"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>