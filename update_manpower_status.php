<?php
include("dbcon.php"); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mpId = $_POST['mpId'];
    $status = $_POST['status'];

    // Prepare the update query
    $updateQuery = "UPDATE manpower SET mpStatus = ? WHERE mpId = ?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("si", $status, $mpId);

    if ($stmt->execute()) {
        echo "Status updated successfully.";
    } else {
        echo "Error updating status.";
    }

    $stmt->close();
    $con->close();
}
?>
