<?php
$page_title = "Admin User Accounts";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");


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
                                    
                                        // Step 1: Fetch the custId based on userId from the users table
                                        $sql = "SELECT custId FROM customers WHERE userId = ?";
                                        $stmt = $con->prepare($sql);
                                        $stmt->bind_param("i", $userId);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                    
                                        // Check if custId exists
                                        if ($result->num_rows > 0) {
                                            $row = $result->fetch_assoc();
                                            $custId = $row['custId'];
                                    
                                            // Step 2: Now, fetch the customer information using custId
                                            $sql_customer = "SELECT * FROM customers WHERE custId = ?";
                                            $stmt_customer = $con->prepare($sql_customer);
                                            $stmt_customer->bind_param("i", $custId);
                                            $stmt_customer->execute();
                                            $customer_result = $stmt_customer->get_result();
                                    
                                            // Check if customer information exists
                                            if ($customer_result->num_rows > 0) {
                                                $customer = $customer_result->fetch_assoc();
                                                $fullName = $customer['firstname'] .' '. $customer['middlename'] .' '. $customer['lastname'];
                                                // Display customer information
                                                ?>
                                                <form>
                                                    <h3 class="text-center pb-2">CUSTOMER INFORMATION</h3>
                                                    <div class="mb-3 row">
                                                        <label for="staticCustId" class="col col-form-label">Customer ID:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['custId']; ?>"  disabled readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="staticFullName" class="col col-form-label">Full Name:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $fullName; ?>"  disabled readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="staticEmail" class="col col-form-label">Email:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['email']; ?>"  disabled readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="staticContact_number" class="col col-form-label">Contact Number:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['contact_number']; ?>"  disabled readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="staticAddress" class="col col-form-label">Address:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['address']; ?>"  disabled readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="staticCreateDate" class="col col-form-label">Registered Date:</label>
                                                        <div class="col">
                                                        <input class="form-control" type="text" value="<?php echo $customer['createDate']; ?>"  disabled readonly>                                                    
                                                        </div>
                                                    </div>
                                                    <a href="admin_userAccounts.php">
                                                        <button type="button" class="btn btn-secondary">
                                                            <i class="bi bi-arrow-90deg-left"></i> Back
                                                        </button>
                                                    </a>
                                                </form>
                                                <?php
                                            } else {
                                                echo "Customer information not found.";
                                            }
                                    
                                            $stmt_customer->close();
                                        } else {
                                            echo "No customer found for the provided userId.";
                                        }
                                    
                                        $stmt->close();
                                    } else {
                                        echo "No user ID provided.";
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