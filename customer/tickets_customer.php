<?php
include("logincode.php");
$page_title = "Raise Tickets";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    $sql = "SELECT adminId FROM admin LIMIT 1";  
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($adminId);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT staffId FROM staffs";  
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($staffId);
    $stmt->fetch();
    $stmt->close();

    // Get the userId from the users table
    $user_query = "SELECT userId FROM users WHERE email = ?";
    $stmt_user = $con->prepare($user_query);

    if (!$stmt_user) {
        die("User query prepare failed: " . $con->error);
    }

    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $stmt_user->bind_result($userId);
    $stmt_user->fetch();
    $stmt_user->close();

    if (empty($userId)) {
        die("No user found for email: $email");
    }

    // Get the custId from the customer table
    $custId_query = "SELECT custId FROM customers WHERE userId = ?";
    $stmt_custId = $con->prepare($custId_query);

    if (!$stmt_custId) {
        die("CustId query prepare failed: " . $con->error);
    }

    $stmt_custId->bind_param("i", $userId);
    $stmt_custId->execute();
    $stmt_custId->bind_result($custId);
    $stmt_custId->fetch();
    $stmt_custId->close();

    if (empty($custId)) {
        die("No custId found for userId: $userId");
    }

    // Get the custName from the customer table
    $cust_query = "SELECT CONCAT(firstname, ' ', middlename, ' ', lastname) AS custName FROM customers WHERE custId = ?";
    $stmt_cust = $con->prepare($cust_query);

    if (!$stmt_cust) {
        die("Customer query prepare failed: " . $con->error);
    }

    $stmt_cust->bind_param("i", $custId);
    $stmt_cust->execute();
    $stmt_cust->bind_result($custName);
    $stmt_cust->fetch();
    $stmt_cust->close();

    if (empty($custName)) {
        die("No customer found for custId: $custId");
    }

    if (isset($_POST['tickbtn_submit'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $status = "Pending";
        $issueDate = date('Y-m-d H:i:s');
    
        if (empty($title) || empty($description)) {
            die("Title or description cannot be empty.");
        }
    
        $insert_ticket = "INSERT INTO ticket (userId, custName, title, description, status, issueDate) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_ticket = $con->prepare($insert_ticket);
    
        if (!$stmt_ticket) {
            die("Ticket query prepare failed: " . $con->error);
        }
    
        $stmt_ticket->bind_param("isssss", $userId, $custName, $title, $description, $status, $issueDate);
        $query_run = $stmt_ticket->execute();
    
        if ($query_run) {           
            echo '<script>alert("Ticket submitted successfully")</script>';
        } else {
            die("Error submitting ticket: " . $stmt_ticket->error);
        }
    
        $stmt_ticket->close();
    }
    
} else {
    die("No customer ID found in session.");
}
    $log_action_query2 = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
    $action = $custName . ' submitted an inquiry ticket.';
    $status = 'unread';
    $log_action_stmt2 = $con->prepare($log_action_query2);
    $log_action_stmt2->bind_param("iss", $adminId, $action, $status);
    $log_action_stmt2->execute();
    $log_action_stmt2->close();

    $log_action_query2 = "INSERT INTO user_action_logs (staffId, action, status) VALUES (?, ?, ?)";
    $action = $custName . ' submitted an inquiry ticket.';
    $status = 'unread';
    $log_action_stmt2 = $con->prepare($log_action_query2);
    $log_action_stmt2->bind_param("iss", $staffId, $action, $status);
    $log_action_stmt2->execute();
    $log_action_stmt2->close();
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
                <div class="col-md-6">

                    <div class="card shadow">
                        <div class="card-header">
                            <h5>Submit A Ticket</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="form-group mb-3">
                                    <label for="">Title <span style="font-size: 0.9em; color: gray;">(Provide a Transaction Type and Number)</span></label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="">Description</label><br>
                                    <textarea class="form-control" name="description" aria-label="With textarea" required></textarea>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="tickbtn_submit" class="btn btn-primary">Submit</button>
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
