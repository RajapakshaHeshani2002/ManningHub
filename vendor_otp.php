<?php
include 'db.php';

$phone = $_GET['phone'];

if(isset($_POST['verify'])){
$otp_entered = $_POST['otp'];

$result = mysqli_query($conn,"SELECT * FROM vendors WHERE phone='$phone'");
$row = mysqli_fetch_assoc($result);

if($otp_entered == $row['otp']){
    mysqli_query($conn,"UPDATE vendors SET is_verified=1 WHERE phone='$phone'");
    echo "Verified Successfully!<br>";
    echo "Your Stall Number: ".$row['stall_number'];
}else{
    echo "Invalid OTP";
}
}
?>

<form method="post">
Enter OTP:<input type="text" name="otp"><br>
<button name="verify">Verify</button>
</form>
