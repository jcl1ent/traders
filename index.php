<?php
session_start();
$page_title = "Ronyx Trading";
include("includes/header.php"); 
include("includes/navbar.php");
include("dbcon.php"); // Your database connection file
if (isset($_POST['login_btn'])) {   
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to get the user by email
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct
            $_SESSION['userId'] = $user['id']; // You can store user ID or other details in session
            $_SESSION['email'] = $user['email'];
            $_SESSION['status'] = "Login successful!";

            $loginTime = date('Y-m-d H:i:s');
            $userId = $user['id'];
            $logQuery = "INSERT INTO users_logs (userId, loginTime) VALUES (?, ?)";
            $logStmt = $con->prepare($logQuery);
            $logStmt->bind_param("is", $userId, $loginTime);
            if ($logStmt->execute()) {
                echo "Login time recorded successfully.";
            } else {
                echo "Error: " . $logStmt->error;
            }
            //header("Location: dashboard.php"); // Redirect to a dashboard or home page
            //exit();
        } else {
            // Password is incorrect
            $_SESSION['status'] = "Invalid password.";
            header("Location: index.php");
            exit();
        }
    } else {
        // Email not found
        $_SESSION['status'] = "Invalid email.";
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/index.css">
</head>
<body>
<div class="py-5">
    <div class="container d-flex justify-content-center">
        <div class="row align-items-center mt-5 pt-5">
            <div class="col-md-8 align-items-center text-light shadow">
                <h1><strong>RONYX TRADING ENGINEERING SERVICES</strong></h1>
                    <div class="about-us">          
                        <p>Empower your trades with precision engineering services,where expertise meets opportunity for seamless success in every transaction.</p>            
                    </div>
                </div>
            <div class="col-md-4 align-items-center">
                <!-- Column for Login Form -->
                <div class="card shadow">
                    <div class="card-header justify-content-end">
                        <div class="col-md-15">  <!-- To display the SESSION status -->
                            <?php
                                if (isset($_SESSION['status'])) {
                            ?>
                                    <div class="alert alert-success">
                                        <h5><?=$_SESSION['status']?></h5>
                                    </div>
                            <?php
                                unset($_SESSION['status']);          
                                }
                            ?>
                        </div>
                        <h5>Login</h5>
                    </div>
                    <div class="card-body">
                        <form action="logincode.php" method="POST">
                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input type="text" name="email" class="form-control" id="email">
                            </div>
                            <div class="form-group mb-3">
                                <label for="password">Password</label>
                                <input type="password" name="password" class="form-control" id="password">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="login_btn" class="btn btn-primary">Login</button>
                                <a href="password_reset.php" class="float-end">Forgot your password?</a>
                            </div>
                        </form>
                    </div> <!-- Close card-body -->
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php include("includes/footer.php")?>