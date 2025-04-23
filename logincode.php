<?php
session_start(); // Start the session
include("dbcon.php"); // Include the database connection

date_default_timezone_set('Asia/Manila'); // Set timezone to Philippines

if (isset($_POST['login_btn'])) {
    if (!empty(trim($_POST['email'])) && !empty(trim($_POST['password']))) {
        $email = mysqli_real_escape_string($con, $_POST['email']);
        $password = mysqli_real_escape_string($con, $_POST['password']);

        // Query to fetch user with the given email
        $login_query = "SELECT * FROM users WHERE email='$email'";
        $login_query_run = mysqli_query($con, $login_query);

        if (mysqli_num_rows($login_query_run) > 0) {
            $row = mysqli_fetch_array($login_query_run);

            if (password_verify($password, $row['password'])) {
                if ($row['verify_status'] == "1") {
                    // Set session variables for authenticated user
                    $_SESSION['authenticated'] = TRUE;
                    $_SESSION['auth_user'] = [
                        'username' => $row['fullName'],
                        'role' => $row['role'],
                        'email' => $row['email']
                    ];

                    // Fetch customer ID
                    $query = "SELECT custId FROM customers WHERE email = ?";
                    $stmt = $con->prepare($query);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->bind_result($custId);
                    $stmt->fetch();
                    $_SESSION['custId'] = $custId;
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['userId'] = $row['userId'];
                    $_SESSION['status'] = "Login Successful";

                    // Close the statement after fetching the result
                    $stmt->close();

                    // Update user status to "online"
                    $user_status = "online";
                    $stmt = $con->prepare("UPDATE users SET user_status = ? WHERE userId = ?");
                    $stmt->bind_param("si", $user_status, $_SESSION['userId']);
                    $stmt->execute();
                    $stmt->close();

                    // Log the login time in users_log
                    $loginTime = date("Y-m-d H:i:s");
                    $stmt = $con->prepare("INSERT INTO users_log (loginTime, userId) VALUES (?, ?)");
                    $stmt->bind_param("si", $loginTime, $_SESSION['userId']);
                    $stmt->execute();
                    $stmt->close();

                    // Redirect to respective dashboards
                    switch ($row['role']) {
                        case 'admin':
                            header("Location: admin_dashboard.php");
                            break;
                        case 'staff':
                            header("Location: staff_dashboard.php");
                            break;
                        default:
                            header("Location: dashboard.php");
                            break;
                    }
                    exit();
                } else {
                    $_SESSION['status'] = "Please verify your email first.";
                }
            } else {
                $_SESSION['status'] = "Invalid email or password.";
            }
        } else {
            $_SESSION['status'] = "Your email is not registered.";
        }
    } else {
        $_SESSION['status'] = "All fields are required.";
    }
    // Redirect back to the login page with status
    header("Location: index.php");
    exit();
}
?>