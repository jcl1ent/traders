<?php
include("dbcon.php");

if (isset($_POST['staffId'])) {
    $staffId = $_POST['staffId'];

    // Delete all notifications for the given admin
    $query = "DELETE FROM user_action_logs WHERE staffId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $staffId);
    $stmt->execute();

    $stmt->close();
}
?>