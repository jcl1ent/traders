<?php
$page_title = "User Details";
include("logincode.php");
include("dbcon.php");
include("includes/header.php");
if(isset($_SESSION['userId'])){
    $userId = $_SESSION['userId'];
    
    $roleQuery = "SELECT role FROM users WHERE userId = ?";
    $stmt = $con->prepare($roleQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $roleResult = $stmt->get_result();
    
    if($roleResult->num_rows > 0){
        $row = $roleResult->fetch_assoc();
        $role = $row['role'];
        if($role == 'customer'){
            include("sidebar.php");
        }elseif($role == 'admin'){
            include("sidebar_admin.php");
        }else{
            include("sidebar_staff.php");
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
                <div class="col-md-6 col-lg-6 mx-auto">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body mb-3">
                                <?php 
                                    if (isset($_GET['userId'])) {
                                        $userId = $_GET['userId'];

                                        // Debugging: Check if userId is being passed correctly
                                        

                                        $query = "SELECT role,password FROM users WHERE userId = ?";
                                        $stmt_role = $con->prepare($query);

                                        if (!$stmt_role) {
                                            echo "Debug: Error preparing statement: " . $con->error;
                                        }

                                        $stmt_role->bind_param("i",  $userId);
                                        $stmt_role->execute();
                                        $result_role = $stmt_role->get_result();

                                        if ($result_role->num_rows > 0) {
                                            $row = $result_role->fetch_assoc();
                                            $role = $row['role'];
                                            $pass = $row['password'];

                                            // Debugging: Check if role is retrieved successfully
                                           

                                            if ($role == 'customer') {
                                                $query = "SELECT * FROM customers WHERE userId = ?";
                                                $stmt = $con->prepare($query);
                                                $stmt->bind_param("i",  $userId);
                                                $stmt->execute();
                                                $custResult = $stmt->get_result();

                                                if ($custResult->num_rows > 0) {
                                                    $customer = $custResult->fetch_assoc();
                                                    $fullName = $customer['firstname'] . ' ' . $customer['middlename'] . ' ' . $customer['lastname'];
                                                    ?>
                                                    <form>
                                                    <div class="mb-3 row">
                                                        <label for="userId" class="col col-form-label">User Role:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $role; ?>" readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="userId" class="col col-form-label">User ID:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['userId']; ?>" readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="fullName" class="col col-form-label">Name:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $fullName; ?>" readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="address" class="col col-form-label">Address:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['address']; ?>" readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="contact_number" class="col col-form-label">Contact Number:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['contact_number']; ?>" readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="email" class="col col-form-label">Email:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['email']; ?>" readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="email" class="col col-form-label">Email:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $row['password']; ?>" readonly>                                                    
                                                        </div>
                                                    </div>
                                                    
                                                    <a href="dashboard.php">
                                                        <button type="button" class="btn btn-secondary bg-gradient">
                                                            Back
                                                        </button>
                                                    </a>
                                                    </form>
                                                    <?php
                                                } else {
                                                    echo "User information not found";
                                                }
                                                $stmt->close();
                                            } elseif ($role == 'staff') {
                                                $query = "SELECT * FROM staffs WHERE userId = ?";
                                                $stmt = $con->prepare($query);
                                                $stmt->bind_param("i", $userId);
                                                $stmt->execute();
                                                $staffResult = $stmt->get_result();

                                                if ($staffResult->num_rows > 0) {
                                                    $staff = $staffResult->fetch_assoc();
                                                    
                                                    ?>
                                                    <form>
                                                    <div class="mb-3 row">
                                                        <label for="userId" class="col col-form-label">User Role:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $role; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="userId" class="col col-form-label">User ID:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $staff['userId']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="fullName" class="col col-form-label">Name:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $fullName; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="fullName" class="col col-form-label">Contact Number:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $staff['contact_number']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="email" class="col col-form-label">Email:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $staff['email']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="email" class="col col-form-label">Hash Password:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $row['password'] ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <a href="staff_dashboard.php">
                                                        <button type="button" class="btn btn-secondary bg-gradient">
                                                            Back
                                                        </button>
                                                    </a>
                                                    </form>
                                                    <?php
                                                } else {
                                                    echo "Admin information not found";
                                                }
                                                $stmt->close();
                                            } elseif ($role == 'admin') {
                                                // Admin-specific details
                                                $query = "SELECT * FROM users WHERE userId = ?";
                                                $stmt = $con->prepare($query);
                                                $stmt->bind_param("i", $userId);
                                                $stmt->execute();
                                                $adminResult = $stmt->get_result();

                                                if ($adminResult->num_rows > 0) {
                                                    $admin = $adminResult->fetch_assoc();
                                                    
                                                    ?>
                                                    <form>
                                                    <div class="mb-3 row">
                                                        <label for="userId" class="col col-form-label">User Role:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $admin['role']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="userId" class="col col-form-label">User ID:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $admin['userId']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="fullName" class="col col-form-label">Name:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $admin['fullName']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="email" class="col col-form-label">Email:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $admin['email']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="email" class="col col-form-label">Hash Password:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $admin['password']; ?>" readonly>                                                
                                                        </div>
                                                    </div>
                                                    <a href="admin_dashboard.php">
                                                        <button type="button" class="btn btn-secondary bg-gradient">
                                                            Back
                                                        </button>
                                                    </a>
                                                    </form>
                                                    <?php
                                                } else {
                                                    echo "Admin information not found";
                                                }
                                                $stmt->close();
                                            }
                                        } else {
                                            echo "Invalid role or role not found";
                                        }
                                        $stmt_role->close();
                                    } else {
                                        echo "Invalid user ID";
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
