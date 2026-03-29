<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role'])) {
    echo json_encode(['success' => false]);
    exit();
}

$role   = $_SESSION['role'];
$action = $_POST['action'] ?? '';

if ($action === 'mark_all_read') {
    $conn->query("UPDATE notifications SET is_read=1 WHERE role='$role' OR role='all'");
    echo json_encode(['success' => true]);

} elseif ($action === 'mark_one_read' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $conn->query("UPDATE notifications SET is_read=1 WHERE id=$id");
    echo json_encode(['success' => true]);

} elseif ($action === 'get_count') {
    $r = $conn->query("SELECT COUNT(*) c FROM notifications WHERE (role='$role' OR role='all') AND is_read=0");
    $count = $r ? $r->fetch_assoc()['c'] : 0;
    echo json_encode(['count' => $count]);

} else {
    echo json_encode(['success' => false]);
}
?>
