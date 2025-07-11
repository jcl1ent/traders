<?php
$page_title = "Admin Accept Information";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_GET['orderNo'])) {
    $orderNo = $_GET['orderNo'];

    // Check if the order exists in the database and fetch custId
    $sql = "SELECT orderNo, custId FROM orders WHERE orderNo = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $orderNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $orderNo = $row['orderNo']; // Assign orderNo for later use
        $custId = $row['custId'];  // Fetch custId for later use
    } else {
        echo "<p>Order not found.</p>";
        exit();
    }
} else {
    echo "<p>No order ID provided.</p>";
    exit();
}

if (isset($_POST['acceptOrder'])) {
    $orderNo = $_POST['orderNo'];
    $assignedStaff = $_POST['staffId'];

    // Update order status to Accepted
    $accept_query = "UPDATE orders SET status = 'Order Confirmed', assignedStaff = ? WHERE orderNo = ?";
    $stmt = $con->prepare($accept_query);
    $stmt->bind_param("ii", $assignedStaff, $orderNo);

    if ($stmt->execute()) {
        // Fetch all order data needed for payment
        $order_query = "SELECT orderNo, custId, paymentType, totalPrice AS totalAmount, payable, (totalPrice - payable) AS balance 
                        FROM orders WHERE orderNo = ?";
        $stmt_order = $con->prepare($order_query);
        $stmt_order->bind_param("i", $orderNo);
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();

        // Log the action in user_actions_logs
        $log_action_query = "INSERT INTO user_action_logs (staffId, action, status) VALUES (?, ?, ?)";
        $action = 'You have been assigned to Order No. ' . $orderNo; 
        $status = 'unread';
        $log_action_stmt = $con->prepare($log_action_query);
        $log_action_stmt->bind_param("iss", $assignedStaff, $action, $status);
        $log_action_stmt->execute();
        $log_action_stmt->close();

        $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
        $action = 'Your Order No. ' . $orderNo. ' has been Accepted and you can now process your payment'; 
        $log_action_stmt = $con->prepare($log_action_query);
        $log_action_stmt->bind_param("iss", $custId, $action, $status); // Use the fetched custId
        $log_action_stmt->execute();
        $log_action_stmt->close();

        if ($result_order->num_rows > 0) {
            $order_data = $result_order->fetch_assoc();
            $paymentStatus = 'To be paid';
            // Insert fetched data into payment table
            $payment_query = "INSERT INTO payment (orderNo, staffId, custId, totalAmount, payable, balance, paymentStatus, paymentType) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_payment = $con->prepare($payment_query);
            $stmt_payment->bind_param(
                "iiidddss",
                $order_data['orderNo'],
                $assignedStaff,
                $order_data['custId'], // Use custId from the order data
                $order_data['totalAmount'],
                $order_data['payable'],
                $order_data['balance'],
                $paymentStatus,
                $order_data['paymentType']
            );

            if ($stmt_payment->execute()) {
                echo "<script>alert('Order Accepted Successfully.')</script>";
                echo '<script>window.location="admin_pendingOrders.php"</script>';
            } else {
                echo "<script>alert('Failed to store payment data.')</script>";
            }

            $stmt_payment->close();
        } else {
            echo "<script>alert('Order data not found.')</script>";
        }

        $stmt_order->close();
    } else {
        echo "<script>alert('Failed to accept order.')</script>";
        echo '<script>window.location="admin_pendingOrders.php"</script>';
    }

    $stmt->close();
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
                <div class="card shadow">
                    <div class="card-header">
                        <h5>Accept Service Request</h5>
                    </div>
                    <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3 row">
                            <label for="">Service Request Number:</label>
                            <div class="col">
                                <input class="form-control" type="text" value="<?php echo htmlspecialchars($orderNo); ?>" readonly>
                                <input type="hidden" name="orderNo" value="<?php echo htmlspecialchars($orderNo); ?>"> <!-- Hidden orderNo field -->
                            </div>
                        </div>

                        <!-- Dropdown for assigning staff -->
                        <div class="form-group">
                            <label for="staffSelect">Assign Staff</label>
                            <select class="form-control mb-3" id="staffSelect" name="staffId" required>
                                <option value="" disabled selected>Select Staff</option>
                                <?php
                                // Fetch available staff from the database
                                $staff_query = "SELECT staffId, firstname, middlename, lastname FROM staffs";
                                $staff_result = $con->query($staff_query);

                                if ($staff_result) {
                                    if ($staff_result->num_rows > 0) {
                                        while ($staff_row = $staff_result->fetch_assoc()) {
                                            $staffName = trim($staff_row['firstname']  . ' ' . $staff_row['lastname']);
                                            echo "<option value='{$staff_row['staffId']}'> {$staff_row['staffId']} - {$staffName}</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No staff available</option>";
                                    }
                                } else {
                                    echo "<p>Error fetching staff: " . $con->error . "</p>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group d-flex justify-content-between">
                            <a href="admin_pendingOrders.php">
                                <button type="button" class="btn btn-secondary">
                                    <i class="bi bi-arrow-90deg-left"></i> Back
                                </button>
                            </a>
                            <button type="submit" name="acceptOrder" class="btn btn-primary">Accept</button>
                            
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

