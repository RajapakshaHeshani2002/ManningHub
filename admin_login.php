<?php
include 'db.php';

if(isset($_POST['login'])){
$name=$_POST['name'];
$password=$_POST['password'];

$result=mysqli_query($conn,"SELECT * FROM admin 
WHERE name='$name' AND password='$password'");

if(mysqli_num_rows($result)>0){
header("Location: admin_dashboard.php");
}else{
echo "Login Failed";
}
}
?>

<form method="post">
Name:<input type="text" name="name"><br>
Password:<input type="password" name="password"><br>
<button name="login">Login</button>
</form>
