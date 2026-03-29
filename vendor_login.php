<?php
include 'db.php';

if(isset($_POST['login'])){
$stall = $_POST['stall'];
$password = $_POST['password'];

$result = mysqli_query($conn,"SELECT * FROM vendors 
WHERE stall_number='$stall' AND password='$password' AND is_verified=1");

if(mysqli_num_rows($result)>0){
    header("Location: vendor_dashboard.php");
}else{
    echo "Login Failed";
}
}
?>

<form method="post">
Stall Number:<input type="text" name="stall"><br>
Password:<input type="password" name="password"><br>
<button name="login">Login</button>
</form>
