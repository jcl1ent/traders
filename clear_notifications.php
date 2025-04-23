<?php
include("dbcon.php");

if (isset($_POST['adminId'])) {
    $adminId = $_POST['adminId'];

    // Delete all notifications for the given admin
    $query = "DELETE FROM user_action_logs WHERE adminId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $adminId);
    $stmt->execute();

    $stmt->close();
}
?>