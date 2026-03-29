<?php
$conn = new mysqli("localhost", "root", "", "manninghub");

if (isset($_POST['post_ann'])) {
    // Admin dashboard එකෙන් එන දත්ත ලබා ගැනීම
    $title = $conn->real_escape_string($_POST['ann_title']);
    $message = $conn->real_escape_string($_POST['ann_message']);
    $category = $conn->real_escape_string($_POST['ann_category']);

    $sql = "INSERT INTO announcements (title, message, category) VALUES ('$title', '$message', '$category')";

    if ($conn->query($sql)) {
        // සාර්ථක නම් ආපහු dashboard එකට යවනවා
        header("Location: admin_dashboard.php?success=announcement_posted");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>