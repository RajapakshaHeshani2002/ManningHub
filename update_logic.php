<?php
$conn = new mysqli("localhost", "root", "", "manninghub");

if(isset($_POST['update_market'])) {
    $item_name = $_POST['item_name'];
    $price = $_POST['price'];
    
    // Image එක Upload කරන කොටස
    $image_name = $_FILES['veg_image']['name'];
    $target = "images/".basename($image_name);

    // Database එකේ දැනටමත් මේ Item එක තියෙනවා නම් Update කරන්න, නැත්නම් අලුතින් ඇතුළත් කරන්න
    $check = $conn->query("SELECT * FROM vegetable_prices WHERE veg_name='$item_name'");
    
    if($check->num_rows > 0) {
        $sql = "UPDATE vegetable_prices SET price='$price', veg_image='$image_name' WHERE veg_name='$item_name'";
    } else {
        $sql = "INSERT INTO vegetable_prices (veg_name, price, veg_image) VALUES ('$item_name', '$price', '$image_name')";
    }

    if($conn->query($sql)) {
        move_uploaded_file($_FILES['veg_image']['tmp_name'], $target);
        echo "<script>alert('Price Updated!'); window.location='admin_dashboard.php';</script>";
    }
}
?>