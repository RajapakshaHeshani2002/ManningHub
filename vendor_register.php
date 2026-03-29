<?php
include 'db.php';

if(isset($_POST['register'])){

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];

$stall = "S".rand(1000,9999);
$otp = rand(100000,999999);

mysqli_query($conn,"INSERT INTO vendors(name,email,phone,stall_number,password,otp) 
VALUES('$name','$email','$phone','$stall','$password','$otp')");

header("Location: vendor_otp.php?phone=$phone");
}
?>

<form method="post">
Name:<input type="text" name="name"><br>
Email:<input type="email" name="email"><br>
Phone:<input type="text" name="phone"><br>
Password:<input type="password" name="password"><br>
<button name="register">Register</button>
</form>
