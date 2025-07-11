<?php
$page_title = "Admin Update Ticket";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['updateTicket'])) {
    $tickNo = $_POST['tickNo'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    // Update the ticket details
    $updateQuery = "UPDATE ticket SET title = ?, description = ?, status = ? WHERE tickNo = ?";
    $stmtUpdate = $con->prepare($updateQuery);
    $stmtUpdate->bind_param("sssi", $title, $description, $status, $tickNo);

    if ($stmtUpdate->execute()) {
        echo "<script>alert('Ticket information updated successfully');</script>";
        echo "<script>window.location.href='staff_tickets.php';</script>";
    } else {
        echo "<script>alert('Error updating ticket information');</script>";
    }
    $stmtUpdate->close();

    // Fetch userId from the ticket table
    $sql = "SELECT userId, status FROM ticket WHERE tickNo = ?";
    $stmt = $con->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $tickNo);
        $stmt->execute();
        $stmt->bind_result($userId, $status);

        if ($stmt->fetch()) {
            $stmt->close();

            // Fetch customer details from the customers table using the userId
            $sqlCustomer = "SELECT address, custId, firstname, middlename, lastname FROM customers WHERE userId = ?";
            $stmtCustomer = $con->prepare($sqlCustomer);

            if ($stmtCustomer) {
                $stmtCustomer->bind_param("i", $userId);
                $stmtCustomer->execute();
                $stmtCustomer->bind_result($address, $custId, $firstname, $middlename, $lastname);

                if ($stmtCustomer->fetch()) {
                    // Combine the full name
                    $fullName = trim("$firstname $middlename $lastname");
                } else {
                    // Set default values if no customer data is found
                    $address = '';
                    $custId = null;
                    $fullName = 'Unknown Customer';
                }

                $stmtCustomer->close();
            } else {
                die("Error preparing the SQL query for customers: " . $con->error);
            }
        } else {
            echo "<script>alert('No userId found for the provided ticket number');</script>";
        }
    } else {
        die("Error preparing the SQL query for ticket: " . $con->error);
    }

    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'The status of your Ticket No.' .$tickNo. 'is now ' . $status; 
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
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
                        if (isset($_GET['tickNo'])) {
                            $tickNo = $_GET['tickNo'];
                            
                            // Fetch the manpower data based on the provided mpId
                            $query = "SELECT * FROM ticket WHERE tickNo = ?";
                            $stmt = $con->prepare($query);
                            $stmt->bind_param("i", $tickNo);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                        ?>    
                            <form action="admin_updateTicket.php?mpId=<?php echo $tickNo; ?>" method="POST">                                       
                                <div class="mb-3 row">
                                    <label for="tickNo" class="col col-form-label">Ticket ID:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="tickNo" value="<?php echo $row['tickNo']; ?>" readonly>                                                    
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="title" class="col col-form-label">Title:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="title" value="<?php echo $row['title']; ?>" readonly>                                                   
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="description" class="col col-form-label">Description:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="description" value="<?php echo $row['description']; ?>">                                                   
                                    </div>
                                </div>                                
                                                            
                                <div class="mb-3 row">
                                    <label for="status" class="col col-form-label">Status:</label>
                                        <div class="col">
                                            <select class="form-select" name="status" aria-required> <!-- Correct name attribute -->
                                                <option value=""><?php echo $row['status']; ?></option>
                                                <option value="Pending">Pending</option>
                                                <option value="Open">Open</option>
                                                <option value="In-Progress">In-Progress</option>
                                                <option value="Resolved">Resolved</option>  
                                                <option value="Closed">Closed</option>
                                            </select>
                                        </div>
                                </div>
                                <div class="d-flex justify-content-center m-3" style="gap: 10px;">                                    
                                <a href="admin_tickets.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-90deg-left"></i> Back 
                                </a>
                                <button type="submit" name="updateTicket" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise"></i> Update
                                </button>
                                </div>                                 
                            </form>
                        <?php
                            } else {
                                echo "<p>Ticket information not found.</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p>No Ticket ID provided.</p>";
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
