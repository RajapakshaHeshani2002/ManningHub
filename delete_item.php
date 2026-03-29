<?php
$conn = new mysqli("localhost", "root", "", "manninghub");
if(isset($_POST['id'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM items WHERE id=$id");
}
header("Location: admin_dashboard.php");
?>