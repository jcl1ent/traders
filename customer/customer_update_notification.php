<?php
include("dbcon.php");

if (isset($_POST['custId'])) {
    $custId = $_POST['custId'];

    // Delete all notifications for the given admin
    $query = "DELETE FROM user_action_logs WHERE custId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $custId);
    $stmt->execute();

    $stmt->close();
}
?>