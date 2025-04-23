<?php
// unset($_SESSION['authenticated']);
// unset($_SESSION['auth_user']);
// $_SESSION['status'] = "Logged Out!";
// header("index.php");
include("logincode.php");
include("dbcon.php"); // Include the database connection

// Check if the user is logged in before logging out
if (isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
    $user_status = "offline";

    // Update user status to 'offline' in the database
    $stmt = $con->prepare("UPDATE users SET user_status = ? WHERE userId = ?");
    $stmt->bind_param("si", $user_status, $userId);
    if (!$stmt->execute()) {
        echo "Error updating user status: " . $stmt->error;
    }

    // Log the logout time in users_log
    $logoutTime = date("Y-m-d H:i:s");
    $stmt = $con->prepare("UPDATE users_log SET logoutTime = ? WHERE userId = ? ORDER BY activity_id DESC LIMIT 1");
    $stmt->bind_param("si", $logoutTime, $userId);
    if (!$stmt->execute()) {
        echo "Error updating logout time: " . $stmt->error;
    }

    // Unset session variables
    unset($_SESSION['authenticated']);
    unset($_SESSION['auth_user']);
    unset($_SESSION['userId']); // Optionally unset userId
    $_SESSION['status'] = "Logged Out!";

    // Redirect to index.php
    header("Location: index.php");
    exit(); // Stop further script execution
}
?>



