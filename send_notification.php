<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role    = in_array($_POST['role'], ['all','vendor','farmer','customer','admin']) ? $_POST['role'] : 'all';
    $type    = in_array($_POST['type'], ['info','warning','success','danger']) ? $_POST['type'] : 'info';
    $title   = $conn->real_escape_string(trim($_POST['title']   ?? ''));
    $message = $conn->real_escape_string(trim($_POST['message'] ?? ''));

    if (!empty($title) && !empty($message)) {
        $conn->query("INSERT INTO notifications (role, title, type, message, is_read, created_at)
                      VALUES ('$role', '$title', '$type', '$message', 0, NOW())");
        header("Location: admin_dashboard.php?notif_sent=$role"); exit();
    }
}
header('Location: admin_dashboard.php'); exit();
?>
