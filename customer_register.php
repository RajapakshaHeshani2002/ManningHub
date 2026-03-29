<?php
include 'db.php';

if(isset($_POST['register'])){
$name=$_POST['name'];
$nic=$_POST['nic'];
$phone=$_POST['phone'];
$password=$_POST['password'];

mysqli_query($conn,"INSERT INTO customers(name,nic,phone,password)
VALUES('$name','$nic','$phone','$password')");

header("Location: customer_login.php");
}
?>
<form method="post">
Name:<input type="text" name="name"><br>
NIC:<input type="text" name="nic"><br>
Phone:<input type="text" name="phone"><br>
Password:<input type="password" name="password"><br>
<button name="register">Register</button>
</form>
