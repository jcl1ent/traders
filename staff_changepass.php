<?php
$page_title = "Staff Change Password";
include("logincode.php");
include("dbcon.php");
include("sidebar_staff.php");
include("includes/header.php");

if (isset($_POST['UpdatePass'])) {
    $userId = $_GET['userId'];

    // Fetch existing user data
    $query = "SELECT password FROM users WHERE userId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        //password strength
        if (strlen($new_password) < 8 || 
        !preg_match('/[A-Z]/', $new_password) || 
        !preg_match('/[a-z]/', $new_password) || 
        !preg_match('/[0-9]/', $new_password) || 
        !preg_match('/[\W_]/', $new_password)) {
        echo '<script>alert("Password must be at least 8 characters long, include uppercase, lowercase, a number, and a special character.");</script>';
        echo "<script>window.location.href='staff_changepass.php?userId=$userId';</script>";
        exit();
        }

        // Validate passwords
        if (empty($new_password) || empty($confirm_new_password)) {
            echo "<script>alert('Password fields cannot be empty.');</script>";
            echo "<script>window.location.href='staff_changepass.php?userId=$userId';</script>";
            exit();
        }

        if ($new_password !== $confirm_new_password) {
            echo "<script>alert('Passwords do not match.');</script>";
            echo "<script>window.location.href='staff_changepass.php?userId=$userId';</script>";
            exit();
        }



        // Hash the password
        $hashed_password = password_hash($confirm_new_password, PASSWORD_BCRYPT);

        // Update password in the database
        $query = "UPDATE users SET password = ? WHERE userId = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("si", $hashed_password, $userId);

        if ($stmt->execute()) {
            echo "<script>alert('Password updated successfully.');</script>";
            echo "<script>window.location.href='updateStaffDetails.php?userId=$userId';</script>";
        } else {
            echo "<script>alert('Error updating password: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid user ID.');</script>";
        echo "<script>window.location.href='dashboard_staff.php';</script>";
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
                <div class="col-md-6 col-lg-6 mx-auto">
                    <div class="card shadow">
                        <div class="card-header">                  
                            <div class="card-body mb-3">
                                
                                <?php 
                                     if (isset($_GET['userId'])) {
                                         $userId = $_GET['userId'];
 
                                         // Debugging: Check if userId is being passed correctly
                                         
 
                                         $query = "SELECT * FROM users WHERE userId = ?";
                                         $stmt_role = $con->prepare($query);
 
                                         if (!$stmt_role) {
                                             echo "Debug: Error preparing statement: " . $con->error;
                                         }
 
                                         $stmt_role->bind_param("i",  $userId);
                                         $stmt_role->execute();
                                         $result_role = $stmt_role->get_result();
 
                                         if ($result_role->num_rows > 0) {
                                             $row = $result_role->fetch_assoc();
                                             $pass = $row['password'];
 
                                             // Debugging: Check if role is retrieved successfully
                                            
                                                     ?>
                                                     <form action="staff_changepass.php?userId=<?php echo $userId; ?>" method="POST" autocomplete="off">
                                                     <!-- <input type="hidden" name="userId" value="<?php echo $row['userId']; ?>"> -->
                                                     <div class="text-center mb-3">
                                                         <label for="userId" class="col col-form-label"><strong>User ID: <?php echo "$userId" ?></strong></label>
                                                         <div class="col">
                                                                                                           
                                                         </div>
                                                     </div>
                                                     <div class="mb-3 row">
                                                         <label for="email" class="col col-form-label">Email:</label>
                                                         <div class="col">
                                                         <input class="form-control" type="text" value="<?php echo $row['email']; ?>" readonly>                                                    
                                                         </div>
                                                     </div>                                   
    
                                                     <div class="mb-3 row">
                                                         <label for="new_password" class="col col-form-label">New Password:</label>
                                                         <div class="col">
                                                             <input class="form-control" type="password" name="new_password" id="new_password" autocomplete="new-password">
                                                         </div>
                                                     </div>
                                                     <div class="mb-3 row">
                                                         <label for="confirm_new_password" class="col col-form-label">Confirm New Password:</label>
                                                         <div class="col">
                                                         <input class="form-control" type="password" name="confirm_new_password">                                                    
                                                         </div>
                                                     </div>
                                                                                                        
                                                     <div class="button d-flex justify-content-center pt-3 mt-3" style="gap: 15px;">
                                                     <a href="updateStaffDetails.php?userId=<?php echo $userId ?>" >
                                                         <button type="button" class="btn btn-secondary bg-gradient">
                                                             Back
                                                         </button>                                                       
                                                     </a>
                                                     <button class="btn btn-success" type="submit" name="UpdatePass">Save</button>
                                                     </div>
                                                     </form>
                                                     <?php            
                                                
                                             }else{
                                                 echo "No role found for this user";
                                             }                                          
                                     }else{
                                         echo 'Invalid user ID';
                                         echo "<script>window.location.href='updateStaffDetails.php?userId= $userId';</script>";
                                     }
                                ?>      
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  
</body>
</html>
