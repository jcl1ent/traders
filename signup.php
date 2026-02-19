<?php 
session_start();
include __DIR__ . "/config.php";
$page_title = "Sign Up";
include("dbcon.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

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

    // build verification link and email body separately
    $verifyLink = url("verifyemail.php?token=$verify_token");
    $email_template = "
        <h2>You have registered with Ronyx Trading</h2>
        <h4>Verify your email address to login using the link below:</h4>
        <br><br>
        <a href='$verifyLink'>Verify Email</a>";
    $mail->Body = $email_template;

    try {
        $mail->send();
        echo 'Email has been sent';
    } catch (Exception $e) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}

if (isset($_POST['signup_btn'])) {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $verify_token = md5(rand());


    // if (strlen($password) < 8 || 
    //         !preg_match('/[A-Z]/', $password) || 
    //         !preg_match('/[a-z]/', $password) || 
    //         !preg_match('/[0-9]/', $password) || 
    //         !preg_match('/[\W_]/', $password)) {
    //         $_SESSION['status'] = "Password must be at least 8 characters long, include uppercase, lowercase, a number, and a special character.";
    //         header("Location: signup.php");
    //         // echo '<script>alert("Password must be at least 8 characters long, include uppercase, lowercase, a number, and a special character.");</script>';
    //         // echo "<script>window.location.href='signup.php';</script>";
    //         exit();
    //         if ($password !== $confirm_password) {
    //             $_SESSION['status'] = "Passwords do not match.";
    //             header("Location: signup.php");
    //             exit();
    //         }
    // }
    // Validate passwords
    

    // Check if email exists
    $check_email_query = "SELECT fullName, email FROM users WHERE email=?";
    $stmt = $con->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['status'] = "Email already exists.";
        redirect('signup.php');
        exit();
    } else {

        // Determine role based on email
        if (strpos($email, 'dmin') !== false) {
            $role = 'admin';
        }elseif(strpos($email, 'staff')!== false) {
            $role = 'staff';
        } else {
            $role = 'customer';
        }

        $fullName = $firstname . ' ' . $middlename . ' ' . $lastname;
        $insert_users_query = "INSERT INTO users (fullname, email, password, verify_token, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($insert_users_query);
        $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hash the password before storing
        $stmt->bind_param("sssss", $fullName, $email, $password_hash, $verify_token, $role);
        $query_run = $stmt->execute();

        // Get the last inserted userID
        $userId = $stmt->insert_id;

        // Now insert into customers table with the userID if the role is 'customer'
        if ($role === 'customer') {
            $insert_customers_query = "INSERT INTO customers (userId, email, firstname, middlename, lastname, address, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($insert_customers_query);
            $stmt->bind_param("issssss", $userId, $email, $firstname, $middlename, $lastname, $address, $contact_number);
            $stmt->execute();
        }

        // Insert into admin table if the role is 'admin'
        if ($role === 'admin') {
            $insert_admin_query = "INSERT INTO admin (userId) VALUES (?)";
            $stmt = $con->prepare($insert_admin_query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }
        if ($role === 'staff') {
            $insert_staff_query = "INSERT INTO staffs (userId, email, firstname, middlename, lastname, address, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($insert_staff_query);
            $stmt->bind_param("issssss", $userId, $email, $firstname, $middlename, $lastname, $address, $contact_number);
            $stmt->execute();
        }

        if ($query_run) {
            sendemail_verify($firstname, $email, $verify_token);
            $_SESSION['status'] = "Registration Complete! Please verify your email address.";
            redirect('signup.php');
            exit();
        } else {
            $_SESSION['status'] = "Registration Failed";
            redirect('signup.php');
            exit();
        }
    }
}

// Include headers AFTER all PHP logic and redirects
include("includes/header.php"); 
include("includes/navbar.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/signup.css">

</head>
<body>
    <div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <div class="card shadow">
                <div class="alert">
                    <?php
                        if (isset($_SESSION['status'])) {
                            echo "<h4>".$_SESSION['status']."</h4>";
                            unset($_SESSION['status']);
                        }
                    ?>
                </div>
                    <div class="card-header">
                        <h5>Registration Form</h5>
                    </div>
                    <div class="card-body">
                        <form name ="signupForm" action="" method="POST" onsubmit="return validateForm()">
                            <div class="form-group mb-2">
                                <label for="">First Name</label>
                                <input type="text" name="firstname" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Middle Name</label>
                                <input type="text" name="middlename" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Last Name</label>
                                <input type="text" name="lastname" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Address</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Email</label>
                                <input type="text" name="email" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Password</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label for="">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="signup_btn" class="btn btn-primary">Sign Up</button>
                            </div>
                        </form>    
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
</body>
</html>

<script>
function validateForm() {
    var contact_number = document.forms["signupForm"]["contact_number"].value;
    var email = document.forms["signupForm"]["email"].value;
    var contact_pattern = /^09\d{9}$/; // Starts with 09 and followed by 9 digits
    var email_pattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/; // Basic email pattern
    var password_pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/; // At least 8 characters, one uppercase, one lowercase, one number, and one special character

    if (!contact_pattern.test(contact_number)) {
        alert("Contact number must start with 09 and be 11 digits long.");
        return false; // Prevents form submission
    }

    if (!email_pattern.test(email)) {
        alert("Please enter a valid email address.");
        return false; // Prevents form submission
    }

    if(!password_pattern.test(password)) {
        alert("Password must be at least 8 characters long, include uppercase, lowercase, a number, and a special character.");
        return false; // Prevents form submission
    }

        var password = document.forms["signupForm"]["password"].value;
    var confirm_password = document.forms["signupForm"]["confirm_password"].value;

    if(password_pattern.test(password) && password !== confirm_password) {
        alert("Passwords do not match.");
        return false; // Prevents form submission
    }

    
    return true; // Allow form submission
}
</script>
