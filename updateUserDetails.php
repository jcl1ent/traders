<?php
$page_title = "Edit Profile";
include("logincode.php");
include("dbcon.php");
include("sidebar.php");
include("includes/header.php");


if (isset($_POST['UpdateUser'])) {
    $userId =  $_GET['userId'];
    // Fetch existing user data
    $query = "SELECT address, contact_number, firstname, middlename, lastname, email FROM customers WHERE userId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();

        // Fallback to existing data if form input is empty
        $address = !empty($_POST['address']) ? $_POST['address'] : $customer['address'];
        $contact_number = !empty($_POST['contact_number']) ? $_POST['contact_number'] : $customer['contact_number'];
        $firstname = !empty($_POST['firstname']) ? $_POST['firstname'] : $customer['firstname'];
        $middlename = !empty($_POST['middlename']) ? $_POST['middlename'] : $customer['middlename'];
        $lastname = !empty($_POST['lastname']) ? $_POST['lastname'] : $customer['lastname'];
        $email = !empty($_POST['email']) ? $_POST['email'] : $customer['email'];
        $fullName = $firstname . ' ' . $middlename . ' ' . $lastname;

        // Validate contact number format
        if (!preg_match("/^09\d{9}$/", $contact_number)) {
            echo "<script>alert('Invalid contact number. It must start with 09 and be 11 digits long');</script>";
            echo "<script>window.location.href='updateUserDetails.php?userId= $userId';</script>";
            exit();
        }

        // Update users table
        $query = "UPDATE users SET email=?, fullName=? WHERE userId=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("ssi", $email, $fullName, $userId);

        if ($stmt->execute()) {
            // Update customers table
            $customer_query = "UPDATE customers SET address=?, contact_number=?, firstname=?, middlename=?, lastname=?, email=? WHERE userId=?";
            $customer_stmt = $con->prepare($customer_query);
            $customer_stmt->bind_param("ssssssi", $address, $contact_number, $firstname, $middlename, $lastname, $email, $userId);

            if ($customer_stmt->execute()) {
                echo "<script>alert('User Information updated successfully');</script>";
                echo "<script>window.location.href='updateUserDetails.php?userId= $userId';</script>";
            } else {
                echo "<script>alert('Error updating information');</script>";
            }
            $customer_stmt->close();
        } else {
            echo "<script>alert('Error updating information');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('User not found');</script>";
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
                        <div class="div d-flex justify-content-end" >
                                                    <a href="customer_changepass.php?userId=<?php echo $_SESSION['userId']; ?>" >
                                                        <button type="button" class="btn btn-white">
                                                            Change Password
                                                        </button>                                                       
                                                    </a>
                                                    </div>                 
                            <div class="card-body mb-3">
                                
                                <?php 
                                    if (isset($_GET['userId'])) {
                                        $userId = $_GET['userId'];

                                        // Debugging: Check if userId is being passed correctly
                                        

                                        $query = "SELECT password FROM users WHERE userId = ?";
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
                                           
                                                $query = "SELECT * FROM customers WHERE userId = ?";
                                                $stmt = $con->prepare($query);
                                                $stmt->bind_param("i",  $userId);
                                                $stmt->execute();
                                                $custResult = $stmt->get_result();

                                                if ($custResult->num_rows > 0) {
                                                    $customer = $custResult->fetch_assoc();
                                                    $fullName = $customer['firstname'] . ' ' . $customer['middlename'] . ' ' . $customer['lastname'];
                                                    ?>
                                                    <form action="updateUserDetails.php?userId=<?php echo $userId; ?>" method="POST">
                                                    <!-- <input type="hidden" name="userId" value="<?php echo $row['userId']; ?>"> -->
                                                    <div class="text-center mb-3">
                                                        <label for="userId" class="col col-form-label"><strong>User ID: <?php echo "$userId" ?></strong></label>
                                                        <div class="col">
                                                                                                          
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="email" class="col col-form-label">Email:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['email']; ?>">                                                    
                                                        </div>
                                                    </div> 
                                                    <div class="mb-3 row">
                                                        <label for="firstname" class="col col-form-label">First Name:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" name="firstname" value="<?php echo ucwords($customer['firstname']); ?>" >                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="middlename" class="col col-form-label">Middle Name:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" name="middlename" value="<?php echo ucwords($customer['middlename']); ?>" >                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="lastname" class="col col-form-label">Last Name:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" name="lastname" value="<?php echo ucwords($customer['lastname']); ?>" >                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="address" class="col col-form-label">Address:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" name="address" value="<?php echo ucwords($customer['address']); ?>" />                                                   
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="contact_number" class="col col-form-label">Contact Number:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" name="contact_number" value="<?php echo $customer['contact_number']; ?>" >                                                    
                                                        </div>
                                                    </div>
                                                                                                       
                                                    <div class="button" style="gap: 15px;">
                                                    <a href="dashboard.php" >
                                                        <button type="button" class="btn btn-secondary bg-gradient">
                                                            Back
                                                        </button>                                                       
                                                    </a>
                                                    <button class="btn btn-success" type="submit" name="UpdateUser">Save</button>
                                                    </div>
                                                    </form>
                                                    <?php
                                                } else {
                                                    echo "User information not found";
                                                }
                                                $stmt->close();
                                            } 
                                        }else{
                                            echo 'Invalid user ID';
                                            echo "<script>window.location.href='updateUserDetails.php?userId= $userId';</script>";
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
