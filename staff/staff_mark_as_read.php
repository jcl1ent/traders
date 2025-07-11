<?php
include("dbcon.php");

if (isset($_POST['actionId'])) {
    $actionId = $_POST['actionId'];

    // Prepare the SQL query to update the status to 'read'
    $updateQuery = "UPDATE user_action_logs SET status = 'read' WHERE actionId = ?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("i", $actionId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // If the update is successful, return a success message
        echo "Notification marked as read.";
    } else {
        // If the update fails
        echo "Error marking notification as read.";
    }

    $stmt->close();
} else {
    echo "No actionId provided.";
}
?>