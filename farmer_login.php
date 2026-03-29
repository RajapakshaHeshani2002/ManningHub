<?php
include 'db.php';

if(isset($_POST['login'])){
$nic=$_POST['nic'];
$password=$_POST['password'];

$result=mysqli_query($conn,"SELECT * FROM farmers 
WHERE nic='$nic' AND password='$password'");

if(mysqli_num_rows($result)>0){
header("Location: farmer_dashboard.php");
}else{
echo "Login Failed";
}
}
?>

<form method="post">
NIC:<input type="text" name="nic"><br>
Password:<input type="password" name="password"><br>
<button name="login">Login</button>
</form>
