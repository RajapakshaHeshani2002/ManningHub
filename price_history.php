<?php
// Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "manninghub");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// සෙවුම් පරාමිතීන් (Search Filters)
$search_query = "";
if(isset($_GET['search_item'])){
    $item_name = $conn->real_escape_string($_GET['search_item']);
    $search_query = " WHERE veg_name LIKE '%$item_name%' ";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-HUB | Price History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --sidebar-bg: #1e272e; 
            --primary: #27ae60; 
            --warning: #f1c40f;
            --bg-light: #f4f7f6;
            --text-dark: #2d3436;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--bg-light); display: flex; }

        /* Sidebar */
        .sidebar { width: 280px; height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; }
        .sidebar-header { padding: 30px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { list-style: none; padding: 20px 10px; }
        .sidebar-menu li a { color: #d1d8e0; text-decoration: none; padding: 12px 15px; display: flex; align-items: center; gap: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar-menu li a.active { background: var(--primary); color: white; }

        /* Content */
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 30px; }
        
        .header-card { 
            background: linear-gradient(135deg, var(--primary), #2ecc71);
            color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }

        .filter-section {
            background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; gap: 15px; align-items: center;
        }

        .filter-section input { padding: 10px; border: 1px solid #dfe6e9; border-radius: 8px; width: 250px; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }

        /* History Table */
        .history-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 15px; background: #f8f9fa; color: #636e72; font-size: 13px; }
        table td { padding: 15px; border-bottom: 1px solid #f1f2f6; }
        
        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-updated { background: #e8f8f0; color: var(--primary); }
        
        .price-text { font-family: 'Courier New', Courier, monospace; font-weight: bold; color: #2c3e50; }
        
        @media print {
            .sidebar, .filter-section, .btn-print { display: none; }
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header"><h2>M-HUB</h2></div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Overview</a></li>
            <li><a href="products_list.php"><i class="fas fa-seedling"></i> Products List</a></li>
            <li><a href="price_history.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Price History</a></li>
            <li><a href="#"><i class="fas fa-user-friends"></i> Vendors</a></li>
            <li><a href="logout.php" style="color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header-card">
            <div>
                <h1>Price History Logs</h1>
                <p>View the report of market price changes from here.</p>
            </div>
            <button onclick="window.print()" class="btn-filter" style="background: white; color: var(--primary);">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <form class="filter-section" method="GET">
            <i class="fas fa-filter" style="color: #b2bec3;"></i>
            <input type="text" name="search_item" placeholder="Search by item name..." value="<?php echo isset($_GET['search_item']) ? $_GET['search_item'] : ''; ?>">
            <button type="submit" class="btn-filter">Filter History</button>
            <a href="price_history.php" style="text-decoration:none; color:#7f8c8d; font-size:13px;">Clear</a>
        </form>

        <div class="history-container">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product Name</th>
                        <th>Logged Price</th>
                        <th>Status</th>
                        <th>Market Sync</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // දැනට Items table එකේ තියෙන දත්ත පෙන්වීම (පසුව Price_History table එකක් සෑදිය හැක)
                    $sql = "SELECT veg_name, price, updated_at FROM items $search_query ORDER BY updated_at DESC";
                    $result = $conn->query($sql);

                    if($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td style="color: #7f8c8d;"><?php echo date('Y-M-d | h:i A', strtotime($row['updated_at'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['veg_name']); ?></strong></td>
                        <td><span class="price-text">Rs. <?php echo number_format($row['price'], 2); ?></span></td>
                        <td><span class="status-pill status-updated">Successfully Updated</span></td>
                        <td style="color: #27ae60;"><i class="fas fa-check-circle"></i> Live</td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 30px;">No history records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>