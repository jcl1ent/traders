<?php
$page_title = "Admin Edit Customer";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if (isset($_POST['updateUser'])) {
    $custId = $_POST['custId'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $middlename = $_POST['middlename'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];

    // Update the customers table
    $updateQuery = "UPDATE customers SET firstname=?, lastname=?, middlename=?, address=?, contact_number=? WHERE custId=?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("sssssi", $firstname, $lastname, $middlename, $address, $contact_number, $custId);

    if ($stmt->execute()) {
        echo "Customer Information Updated Successfully!";
    } else {
        echo "Error updating customer information.";
    }
    $stmt->close();

    // Fetch the userId from customers table
    $fetchUserIdQuery = "SELECT userId FROM customers WHERE custId = ?";
    $stmt = $con->prepare($fetchUserIdQuery);
    $stmt->bind_param("i", $custId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userId = $row['userId'];
    } else {
        echo "Error fetching associated user ID.";
        return;
    }
    $stmt->close();

    // Update the users table
    $fullName = $firstname . ' ' . $middlename . ' ' . $lastname;
    $updateUserQuery = "UPDATE users SET fullName = ? WHERE userId = ?";
    $stmt = $con->prepare($updateUserQuery);
    $stmt->bind_param("si", $fullName, $userId);

    if ($stmt->execute()) {
        echo "User Information Updated Successfully!";
        echo "<script>window.location.href='admin_userAccounts.php';</script>";
    } else {
        echo "Error updating User Information.";
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                    </div>
                    <div class="card-body mb-3">
                        <?php 
                        if (isset($_GET['userId'])) {
                            $userId = $_GET['userId'];
                            
                            // Fetch the manpower data based on the provided mpId
                            $query = "SELECT customers.*,
                                            users.fullName
                                        FROM customers
                                        JOIN users ON users.userId = customers.userId
                                        WHERE customers.userId = ?";
                            $stmt = $con->prepare($query);
                            $stmt->bind_param("i", $userId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                        ?>    
                            <form action="" method="POST">                                       
                                <div class="m-3 row">
                                    <label for="mpId" class="col col-form-label">Customer ID:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="custId" value="<?php echo $row['custId']; ?>" readonly>                                                    
                                    </div>
                                </div>
                                <div class="m-3 row">
                                    <label for="fullName" class="col col-form-label">First Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="firstname" value="<?php echo ucwords($row['firstname']); ?>">                                                   
                                    </div>
                                </div>
                                <div class="m-3 row">
                                    <label for="fullName" class="col col-form-label">Middle Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="middlename" value="<?php echo ucwords($row['middlename']); ?>">                                                   
                                    </div>
                                </div>
                                <div class="m-3 row">
                                    <label for="fullName" class="col col-form-label">Last Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="lastname" value="<?php echo ucwords($row['lastname']); ?>">                                                   
                                    </div>
                                </div>
                                <div class="m-3 row">
                                    <label for="address" class="col col-form-label">Address:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="address" value="<?php echo ucwords($row['address']); ?>">                                                   
                                    </div>
                                </div>
                                <div class="m-3 row">
                                    <label for="contactNo" class="col col-form-label">Contact Number:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="contact_number" value="<?php echo $row['contact_number']; ?>">                                                   
                                    </div>
                                </div>                                                              
                                <div class="d-flex justify-content-center m-3" style="gap: 10px;">
                                <a href="admin_userAccounts.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-90deg-left"></i> Back 
                                </a>
                                <button type="submit" name="updateUser" class="btn btn-primary">
                                     Update
                                </button>
                                </div>
                            </form>
                        <?php
                            } else {
                                echo "<p>Customer information not found.</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p>No Customer ID provided.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>  
</body>
</html>
