<?php
include 'db.php';

if (isset($_POST['submit'])) {
    $name      = $conn->real_escape_string(trim($_POST['item_name']));
    $price     = floatval($_POST['new_price']);
    $min_price = floatval($_POST['min_price']);
    $max_price = floatval($_POST['max_price']);

    // Validate price band
    if ($min_price >= $max_price) {
        header("Location: admin_dashboard.php?error=band");
        exit();
    }
    if ($price < $min_price || $price > $max_price) {
        header("Location: admin_dashboard.php?error=range");
        exit();
    }

    // Handle image upload
    $image = '';
    if (!empty($_FILES['product_image']['name'])) {
        $image  = basename($_FILES['product_image']['name']);
        $target = "image/" . $image;
        move_uploaded_file($_FILES['product_image']['tmp_name'], $target);
    }

    // Insert or update
    $check = $conn->query("SELECT id, veg_image FROM items WHERE veg_name='$name' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $existing = $check->fetch_assoc();
        if (empty($image)) $image = $existing['veg_image'];
        $conn->query("UPDATE items SET price='$price', min_price='$min_price', max_price='$max_price', veg_image='$image' WHERE veg_name='$name'");
    } else {
        $conn->query("INSERT INTO items (veg_name, price, min_price, max_price, veg_image) VALUES ('$name','$price','$min_price','$max_price','$image')");
    }

    header("Location: admin_dashboard.php?success=1");
}
?>