<?php
$page_title = "Admin Add User";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

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

    $email_template = "
        <h2>You have registered with Ronyx Trading</h2>
        <h4>Verify your email address to login using the link below:</h4>
        <br><br>
        <a href='http://localhost/traders_testing/verifyemail.php?token=$verify_token'>Verify Email</a>";
    $mail->Body = $email_template;

    try {
        $mail->send();
        echo 'Email has been sent';
    } catch (Exception $e) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}

if(isset($_POST['addUser_btn'])){
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $verify_token = md5(rand());
    $role = $_POST['role'];

    if (!preg_match("/^09\d{9}$/", $contact_number)) {
        // echo "<script>alert('Invalid contact number. It must start with 09 and be 11 digits long.')</script>";
        $_SESSION['status'] = "Invalid contact number. It must start with 09 and be 11 digits long.";
        header("Location: admin_addUser.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status'] = "Invalid email format.";
        header("Location: admin_addUser.php");
        // echo "<script>alert('Invalid email format.')</script>";
        exit();
    }
    // Validate passwords
    if ($password !== $confirm_password) {
        $_SESSION['status'] = "Passwords do not match.";
        header("Location: admin_addUser.php");
        // echo "<script>alert('Passwords do not match.')</script>";
        exit();
    }

    $check_email_query = "SELECT email FROM users WHERE email=?";
    $stmt = $con->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['status'] = "Email already exists.";
        header("Location: admin_addUser.php");
        //echo "<script>alert('Email already exists.')</script>";
        exit();
    }
        $fullName = $firstname . ' ' . $middlename . ' ' . $lastname;
        $insert_users_query = "INSERT INTO users (fullname, email, password, verify_token, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($insert_users_query);
        $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hash the password before storing
        $stmt->bind_param("sssss", $fullName, $email, $password_hash, $verify_token, $role);
        $query_run = $stmt->execute();

        // Get the last inserted userID
        $userId = $stmt->insert_id;

        if($role == 'customer'){
        $insert_customers_query = "INSERT INTO customers (userId, email, firstname, middlename, lastname, address, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($insert_customers_query);
        $stmt->bind_param("issssss", $userId, $email, $firstname, $middlename, $lastname, $address, $contact_number);
        $stmt->execute();
        if ($query_run) {
            sendemail_verify($firstname, $email, $verify_token);
            $_SESSION['status'] = "Customer Added Successfully! Please verify your email address.";
            header("Location: admin_addUser.php");
            exit();
        } else {
            $_SESSION['status'] = "Registration Failed";
            header("Location: admin_addUser.php");
            exit();
        }

        } else{
            $insert_staff_query = "INSERT INTO staffs (userId, email, firstname, middlename, lastname, address, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($insert_staff_query);
            $stmt->bind_param("issssss", $userId, $email, $firstname, $middlename, $lastname, $address, $contact_number);
            $stmt->execute();
            if ($query_run) {
                sendemail_verify($firstname, $email, $verify_token);
                $_SESSION['status'] = "Staff Added Successfully! Please verify your email address.";
                header("Location: admin_addUser.php");
                exit();
            } else {
                $_SESSION['status'] = "Registration Failed";
                header("Location: admin_addUser.php");
                exit();
            }
        }

        

        
}       
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="alert">               
                </div>
                <div class="card shadow">
                    <div class="card-header">
                        <a href="admin_userAccounts.php">
                            <button class="btn btn-secondary bg-gradient">
                                <i class="bi bi-arrow-90deg-left"> Back</i>
                            </button>
                        </a>
                        <h5 class="text-center">Add User Form</h5>
                    </div>
                    <div class="card-body">
                   
                        <form name ="addUserForm" action="" method="POST" onsubmit="return validateForm()">
                            <div class="form-group mb-3">
                                <label for="role">Role:</label>
                                <select id="role" name="role" required>
                                    <option value="customer">Customer</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="">First Name</label>
                                <input type="text" name="firstname" class="form-control">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="">Middle Name</label>
                                <input type="text" name="middlename" class="form-control">
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Last Name</label>
                                <input type="text" name="lastname" class="form-control">
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Address</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control">
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Email</label>
                                <input type="text" name="email" class="form-control">
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Password</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                            <div class="form-group d-flex justify-content-end">                           
                                <button type="submit" name="addUser_btn" class="btn btn-primary">Sign Up</button>
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
    var contact_number = document.forms["addUserForm"]["contact_number"].value;
    var email = document.forms["addUserForm"]["email"].value;
    var contact_pattern = /^09\d{9}$/; // Starts with 09 and followed by 9 digits
    var email_pattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/; // Basic email pattern

    if (!contact_pattern.test(contact_number)) {
        alert("Contact number must start with 09 and be 11 digits long.");
        return false; // Prevents form submission
    }

    if (!email_pattern.test(email)) {
        alert("Please enter a valid email address.");
        return false; // Prevents form submission
    }

    return true; // Allow form submission
}
</script>