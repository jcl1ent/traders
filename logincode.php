<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// load configuration/helpers (base URL, redirect etc.)
include_once __DIR__ . "/config.php";
include_once("dbcon.php");

date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

if (!function_exists('sendemail_verify')) {
    function sendemail_verify($firstname, $email, $verify_token) {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 0; // Set to 2 to enable debug output
        $mail->isSMTP();                                             // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';  
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ronyxtrading@gmail.com';                     // SMTP username
        $mail->Password   = 'hsmrppgadmxbyjnx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            // Enable implicit TLS encryption
        $mail->Port       = 587;

        $mail->setFrom('ronyxtrading@gmail.com', 'Ronyx Trading');
        $mail->addAddress($email, $firstname);

        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Email Verification';

        $verifyLink = url("verifyemail.php?token=$verify_token");
        $email_template = "
            <h2>You have registered with Ronyx Trading</h2>
            <h4>Verify your email address to login using the link below:</h4>
            <br><br>
            <a href='$verifyLink'>Verify Email</a>";
        $mail->Body = $email_template;

        try {
            $mail->send();
        // echo 'Email has been sent';
        } catch (Exception $e) {
        // echo 'Message could not be sent.';
        // echo 'Mailer Error: ' . $mail->ErrorInfo;
        }
    }
}

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

                    // Redirecting to dashboards
                    switch ($row['role']) {
                        case 'admin':
                            redirect('admin/admin_dashboard.php');
                            break;
                        case 'staff':
                            redirect('staff/staff_dashboard.php');
                            break;
                        default:
                            redirect('customer/dashboard.php');
                            break;
                    }
                    exit();
                } else {
                    $_SESSION['status'] = "Please verify your email first.";
                    sendemail_verify($row['fullName'], $email, $row['verify_token']);
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
    redirect('index.php');
}
?>