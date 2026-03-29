<div class="admin-container">
    <h2>Update Market Prices</h2>
    <form action="update_logic.php" method="POST" enctype="multipart/form-data">
        <select name="veg_id" required>
            <?php
            $conn = new mysqli("localhost", "root", "", "manninghub");
            $res = $conn->query("SELECT * FROM vegetable_prices");
            while($row = $res->fetch_assoc()) {
                echo "<option value='".$row['id']."'>".$row['veg_name']."</option>";
            }
            ?>
        </select>
        <input type="number" name="new_price" placeholder="New Price (Rs.)" required>
        <input type="file" name="veg_image"> <button type="submit" name="update_price">Update Now</button>
    </form>
</div>