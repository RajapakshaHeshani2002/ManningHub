<?php
include 'db.php';

$result = $conn->query("SELECT veg_name, price, min_price, max_price, veg_image FROM items ORDER BY veg_name ASC");

if ($result && $result->num_rows > 0) {
    for ($i = 0; $i < 2; $i++) {
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {

            $img_path = (!empty($row['veg_image']))
                ? "image/" . $row['veg_image']
                : "image/default.png";

            // Show price band if set, otherwise show single price
            if (!empty($row['min_price']) && !empty($row['max_price'])) {
                $price_display = 'Rs. ' . number_format($row['min_price'], 0)
                               . ' - Rs. ' . number_format($row['max_price'], 0);
            } else {
                $price_display = 'Rs. ' . number_format($row['price'], 2);
            }

            echo '<div class="ticker-item">';
            echo '<img src="' . $img_path . '" alt="' . htmlspecialchars($row['veg_name']) . '">';
            echo htmlspecialchars($row['veg_name']) . ': <span>' . $price_display . '</span>';
            echo '</div>';
        }
    }
} else {
    echo '<div class="ticker-item">No active market rates available.</div>';
}
?>
