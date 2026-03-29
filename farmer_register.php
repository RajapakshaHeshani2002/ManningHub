<?php
include 'db.php';

if(isset($_POST['register'])){
$name=$_POST['name'];
$nic=$_POST['nic'];
$phone=$_POST['phone'];
$location=$_POST['location'];
$crop=$_POST['crop'];
$password=$_POST['password'];

mysqli_query($conn,"INSERT INTO farmers(name,nic,phone,location,crop,password)
VALUES('$name','$nic','$phone','$location','$crop','$password')");

header("Location: farmer_login.php");
}
?>
<form method="post">
Name:<input type="text" name="name"><br>
NIC:<input type="text" name="nic"><br>
Phone:<input type="text" name="phone"><br>
Location:<input type="text" name="location"><br>
Crop:<input type="text" name="crop"><br>
Password:<input type="password" name="password"><br>
<button name="register">Register</button>
</form>
