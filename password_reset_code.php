<?php
include("dbcon.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
function send_password_reset($get_fullName, $get_email,$token){
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
    $mail->addAddress($get_email, $get_fullName);

    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Reset Password Link';

    $email_template = "
        <h2>Hello</h2>
        <h4>You are receiving this email because we receive a Password Reset Request for your email.</h4>
        <br><br>
        <a href='http://localhost/traders_testing/password_change.php?token=$token&email=$get_email'>Reset Password</a>";

    $mail->Body = $email_template;

    try {
        $mail->send();
        echo 'Email has been sent';
    } catch (Exception $e) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}
if(isset($_POST['password_reset'])){
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $token = md5(rand());

    $check_email = "SELECT email FROM users WHERE email='$email' LIMIT 1";
    $check_email_run = mysqli_query($con, $check_email);

    if(mysqli_num_rows($check_email_run) > 0){
        $row=mysqli_fetch_array($check_email_run);
        $get_fullName = $row['fullName'];
        $get_email = $row['email'];

        $update_token = "UPDATE users SET verify_token='$token' WHERE email='$get_email' LIMIT 1";
        $update_token_run = mysqli_query($con, $update_token);

        if($update_token_run){
            send_password_reset($get_fullName, $get_email,$token);
            $_SESSION['status']= "Check your email for the Reset Password Link.";
            header("Location: password_reset.php");
            exit(0);

        }else{
            $_SESSION['status']="Something went wrong. #1";
            header("Location: password_reset.php");
            exit(0);
        }

    }else{
        $_SESSION['status']="No email found!";
        header("Location: password_reset.php");
        exit(0);
    }

}

if (isset($_POST['password_update'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);

    $token = mysqli_real_escape_string($con, $_POST['password_token']);
    
    if (!empty($token)) {
        if (!empty($new_password) && !empty($confirm_password)) {
            $check_token = "SELECT verify_token FROM users WHERE verify_token='$token' LIMIT 1";
            $check_token_run = mysqli_query($con, $check_token);

            if (mysqli_num_rows($check_token_run) > 0) {
                if ($new_password == $confirm_password) {
                    // Hash the new password before updating it in the database
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    $update_password = "UPDATE users SET password='$hashed_password' WHERE verify_token='$token' LIMIT 1";
                    $update_password_run = mysqli_query($con, $update_password);

                    if ($update_password_run) {
                        $new_token = md5(rand());
                        $update_to_new_token = "UPDATE users SET verify_token='$new_token' WHERE verify_token='$token' LIMIT 1";
                        $update_to_new_token_run = mysqli_query($con, $update_to_new_token);

                        $_SESSION['status'] = "Password Updated!";
                        header("Location: index.php");
                        exit(0);
                    } else {
                        $_SESSION['status'] = "Something went wrong.";
                        header("Location: password_change.php?token=$token&email=$email");
                        exit(0);
                    }
                } else {
                    $_SESSION['status'] = "Passwords do not match.";
                    header("Location: password_change.php?token=$token&email=$email");
                    exit(0);
                }
            } else {
                $_SESSION['status'] = "Invalid Token!";
                header("Location: password_change.php?token=$token&email=$email");
                exit(0);
            }
        } else {
            $_SESSION['status'] = "All fields are required!";
            header("Location: password_change.php?token=$token&email=$email");
            exit(0);
        }
    } else {
        $_SESSION['status'] = "No token available!";
        header("Location: password_change.php");
        exit(0);
    }
}

?>