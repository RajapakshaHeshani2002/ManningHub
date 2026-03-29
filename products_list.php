<?php
include 'db.php';

if(isset($_POST['delete_id'])){
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM items WHERE id=$id");
    header("Location: products_list.php?msg=deleted");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-HUB | Products List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --sidebar-bg: #1e272e; 
            --primary: #27ae60; 
            --bg-light: #f4f7f6;
            --text-dark: #2d3436;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--bg-light); display: flex; }

        /* Sidebar */
        .sidebar { width: 280px; height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; }
        .sidebar-header { padding: 30px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { color: #2ecc71; }
        .sidebar-menu { list-style: none; padding: 20px 10px; }
        .sidebar-menu li a { color: #d1d8e0; text-decoration: none; padding: 12px 15px; display: flex; align-items: center; gap: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar-menu li a.active { background: var(--primary); color: white; }

        /* Main Content */
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 30px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h1 { font-size: 24px; color: var(--text-dark); }

        .search-container { position: relative; width: 350px; }
        .search-container input { width: 100%; padding: 12px 40px 12px 15px; border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); outline: none; }
        .search-container i { position: absolute; right: 15px; top: 15px; color: #b2bec3; }

        /* Table Styling */
        .table-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        table { width: 100%; border-collapse: collapse; }
        table th { text-align: left; padding: 15px; background: #f8f9fa; color: #636e72; font-size: 13px; text-transform: uppercase; }
        table td { padding: 15px; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
        
        .prod-img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; }
        .price-tag { color: var(--primary); font-weight: 700; }
        
        /* Action Buttons */
        .btn-edit { color: #3498db; background: #ebf5fb; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; margin-right: 5px; transition: 0.3s; }
        .btn-edit:hover { background: #3498db; color: white; }
        .btn-delete { color: #e74c3c; background: #fdedec; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .btn-delete:hover { background: #e74c3c; color: white; }

        .btn-add { background: var(--primary); color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-add:hover { background: #219150; transform: translateY(-2px); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header"><h2>M-HUB</h2><p style="font-size: 11px;">ADMIN PANEL</p></div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Overview</a></li>
            <li><a href="products_list.php" class="active"><i class="fas fa-seedling"></i> Products List</a></li>
            <li><a href="#"><i class="fas fa-file-invoice-dollar"></i> Price History</a></li>
            <li><a href="#"><i class="fas fa-user-friends"></i> Vendors</a></li>
            <li style="margin-top: 20px;"><a href="logout.php" style="color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1>Manage Products</h1>
                <p style="color: #7f8c8d; font-size: 14px;">Manage all products available in the system from here.</p>
            </div>
            <a href="admin_dashboard.php" class="btn-add"><i class="fas fa-plus"></i> Add New Product</a>
        </div>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px;">Product Inventory</h3>
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="pSearch" placeholder="Search by name..." onkeyup="filterProducts()">
                </div>
            </div>

            <table id="pTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Ref. Price</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th>Price Band</th>
                        <th>Category</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM items ORDER BY veg_name ASC");
                    if($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                            $img = (!empty($row['veg_image']) && file_exists('image/'.$row['veg_image'])) ? 'image/'.$row['veg_image'] : 'image/default.png';
                    ?>
                    <tr>
                        <td style="color: #95a5a6;">#<?php echo $row['id']; ?></td>
                        <td><img src="<?php echo $img; ?>" class="prod-img"></td>
                        <td><strong><?php echo htmlspecialchars($row['veg_name']); ?></strong></td>
                        <td><span class="price-tag">Rs. <?php echo number_format($row['price'], 2); ?></span></td>
                        <td>
                            <?php if(!empty($row['min_price'])): ?>
                            <span style="color:#27ae60;font-weight:700;font-size:13px;">Rs.<?php echo number_format($row['min_price'],0); ?></span>
                            <?php else: ?><span style="color:#bbb;font-size:12px;">-</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if(!empty($row['max_price'])): ?>
                            <span style="color:#e74c3c;font-weight:700;font-size:13px;">Rs.<?php echo number_format($row['max_price'],0); ?></span>
                            <?php else: ?><span style="color:#bbb;font-size:12px;">-</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if(!empty($row['min_price']) && !empty($row['max_price'])): ?>
                            <span style="background:#eafaf1;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;color:#27ae60;border:1px solid #a9dfbf;">
                                Rs.<?php echo number_format($row['min_price'],0); ?> - <?php echo number_format($row['max_price'],0); ?>
                            </span>
                            <?php else: ?>
                            <span style="background:#f8f9fa;padding:4px 10px;border-radius:20px;font-size:11px;color:#bbb;">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td><span style="background: #f1f2f6; padding: 4px 10px; border-radius: 20px; font-size: 12px;">Vegetable</span></td>
                        <td style="text-align: center;">
                            <div class="action-btns">
                                <button class="btn-edit" onclick="alert('Use the Admin Dashboard to edit prices.')"><i class="fas fa-edit"></i></button>
                                <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="9" style="text-align: center; padding: 40px;">No products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function filterProducts() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("pSearch");
        filter = input.value.toUpperCase();
        table = document.getElementById("pTable");
        tr = table.getElementsByTagName("tr");
        for (i = 1; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[2];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    </script>
</body>
</html>