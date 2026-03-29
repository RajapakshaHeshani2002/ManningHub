<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "manninghub");

// Connection එකේ ප්‍රශ්නයක් තියෙනවාද බලන්න
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['update_settings'])) {
    // Escape string භාවිතා කර දත්ත ආරක්ෂිතව ලබා ගැනීම
    $s_name = $conn->real_escape_string($_POST['system_name']);
    $email  = $conn->real_escape_string($_POST['contact_email']);
    $open   = $conn->real_escape_string($_POST['open_time']);
    $close  = $conn->real_escape_string($_POST['close_time']);
    $curr   = $conn->real_escape_string($_POST['currency']);
    $freq   = $conn->real_escape_string($_POST['update_freq']);
    $text   = $conn->real_escape_string($_POST['maintenance_text']); // මෙතනයි ප්‍රශ්නය තිබුණේ
    $reg    = $conn->real_escape_string($_POST['allow_reg']);

    // Query එක දැන් ආරක්ෂිතයි
    $sql = "UPDATE system_settings SET 
            system_name='$s_name', 
            contact_email='$email', 
            open_time='$open', 
            close_time='$close', 
            currency='$curr', 
            update_freq='$freq', 
            maintenance_text='$text', 
            allow_reg='$reg' 
            WHERE id=1";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Settings Updated Successfully!'); window.location='settings.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>