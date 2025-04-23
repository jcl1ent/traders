<?php
$page_title = "Admin Pending Orders";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['acceptOrder'])) {
    $orderNo = $_POST['orderNo'];

    // Get all staff IDs
    $staff_query = "SELECT staffId FROM staffs";
    $staff_result = $con->query($staff_query);
    $staffIds = [];
    while ($staff = $staff_result->fetch_assoc()) {
        $staffIds[] = $staff['staffId'];
    }

    // Fetch the last 2 assigned staff for the order (to check for alternation)
    $lastAssigned_query = "SELECT assignedStaff FROM orders ORDER BY orderNo DESC LIMIT 2";
    $lastAssigned_result = $con->query($lastAssigned_query);
    $lastAssignedStaff = [];
    while ($row = $lastAssigned_result->fetch_assoc()) {
        $lastAssignedStaff[] = $row['assignedStaff'];
    }

    // Fetch assigned staff for the order
    $assignedStaff_query = "SELECT assignedStaff FROM orders WHERE orderNo = ?";
    $stmt_assigned = $con->prepare($assignedStaff_query);
    $stmt_assigned->bind_param("i", $orderNo);
    $stmt_assigned->execute();
    $assignedStaff_result = $stmt_assigned->get_result();
    $assignedStaff = null;

    if ($assignedStaff_result->num_rows > 0) {
        $assignedStaff_row = $assignedStaff_result->fetch_assoc();
        $assignedStaff = $assignedStaff_row['assignedStaff'];
    }

    $stmt_assigned->close();

    // Ensure no consecutive assignments
    if (is_null($assignedStaff)) {
        foreach ($staffIds as $staffId) {
            if (!in_array($staffId, $lastAssignedStaff)) {
                $assignedStaff = $staffId;
                break;
            }
        }
    }

    // Update order status and assign staff
    $accept_query = "UPDATE orders SET status = 'Order Confirmed', assignedStaff = ? WHERE orderNo = ?";
    $stmt = $con->prepare($accept_query);
    if ($stmt === false) {
        die('Error in prepare statement: ' . $con->error);
    }
    $stmt->bind_param("ii", $assignedStaff, $orderNo);

    if ($stmt->execute()) {
        // Log the assignment action for staff
        $log_action_query = "INSERT INTO user_action_logs (staffId, action, status) VALUES (?, ?, ?)";
        $action = 'You have been assigned to Order No. ' . $orderNo;
        $status = 'unread';
        $log_action_stmt = $con->prepare($log_action_query);
        if ($log_action_stmt === false) {
            die('Error in log action prepare statement: ' . $con->error);
        }
        $log_action_stmt->bind_param("iss", $assignedStaff, $action, $status);
        if (!$log_action_stmt->execute()) {
            die('Error executing log action: ' . $log_action_stmt->error);
        }
        $log_action_stmt->close();

        // Log the acceptance action for the customer
        $cust_query = "SELECT custId FROM orders WHERE orderNo = ?";
        $stmt_cust = $con->prepare($cust_query);
        if ($stmt_cust === false) {
            die('Error in customer query prepare statement: ' . $con->error);
        }
        $stmt_cust->bind_param("i", $orderNo);
        $stmt_cust->execute();
        $result_cust = $stmt_cust->get_result();
        if ($result_cust->num_rows > 0) {
            $custId = $result_cust->fetch_assoc()['custId'];

            $log_cust_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
            $cust_action = 'Your Order No. ' . $orderNo . ' has been Accepted.';
            $log_cust_stmt = $con->prepare($log_cust_query);
            if ($log_cust_stmt === false) {
                die('Error in log customer action prepare statement: ' . $con->error);
            }
            $log_cust_stmt->bind_param("iss", $custId, $cust_action, $status);
            if (!$log_cust_stmt->execute()) {
                die('Error executing log customer action: ' . $log_cust_stmt->error);
            }
            $log_cust_stmt->close();
        }

        // Fetch order details for payment insertion
        $order_query = "SELECT orderNo, custId, paymentType, totalPrice AS totalAmount, payable, (totalPrice - payable) AS balance 
                        FROM orders WHERE orderNo = ?";
        $stmt_order = $con->prepare($order_query);
        if ($stmt_order === false) {
            die('Error in order query prepare statement: ' . $con->error);
        }
        $stmt_order->bind_param("i", $orderNo);
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();

        // Check if there is order data
        if ($result_order->num_rows > 0) {
            $order_data = $result_order->fetch_assoc();
            $paymentStatus = 'To be paid'; // Define the payment status

            // Insert payment data into the payment table
            $payment_query = "INSERT INTO payment (orderNo, staffId, custId, totalAmount, payable, balance, paymentStatus, paymentType) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_payment = $con->prepare($payment_query);

            // Check for errors in preparing the payment query
            if ($stmt_payment === false) {
                die('Error in payment query prepare statement: ' . $con->error);
            }

            // Bind parameters for the insert statement
            $stmt_payment->bind_param(
                "iiidddss",
                $order_data['orderNo'],
                $assignedStaff, // Assuming $assignedStaff is defined earlier in your code
                $order_data['custId'],
                $order_data['totalAmount'],
                $order_data['payable'],
                $order_data['balance'],
                $paymentStatus,
                $order_data['paymentType']
            );

            // Execute the payment insertion
            if ($stmt_payment->execute()) {
                echo "<script>alert('Order Accepted.');</script>";
                echo '<script>window.location="admin_pendingOrders.php"</script>';
            } else {
                die('Error executing payment query: ' . $stmt_payment->error);
            }

            // Close the payment statement
            $stmt_payment->close();
        } else {
            // Handle the case where no order data was found
            echo "<script>alert('No order found.');</script>";
        }
        $stmt_order->close();
    } else {
        die('Error executing update query: ' . $stmt->error);
    }

    
}
if (isset($_POST['declineOrder'])) {
    $orderNo = $_POST['orderNo'];

    // Fetch the order and customer ID from the database
    $sql = "SELECT orderNo, custId FROM orders WHERE orderNo = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $orderNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $custId = $row['custId'];  // Get the customer ID from the result
    } else {
        echo "<script>alert('Order not found.')</script>";
        echo '<script>window.location="admin_pendingOrders.php"</script>';
        exit();
    }

    // Prepare and execute the decline action (log action and update order status)
    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'Your Order No. ' . $orderNo . ' has been Declined'; 
    $status = 'unread';  // Assuming you want to set the status to unread
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();

    // Update the order status to 'Order Declined'
    $decline_query = "UPDATE orders SET status = 'Order Declined' WHERE orderNo = ?";
    $stmt = $con->prepare($decline_query);
    $stmt->bind_param("i", $orderNo);

    if ($stmt->execute()) {
        // Optionally set a session message for success or failure
        echo "<script>alert('Order Declined Successfully.')</script>";
        echo '<script>window.location="admin_pendingOrders.php"</script>';
    } else {
        echo "<script>alert('Failed to decline order.')</script>";
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
<ul class="nav nav-tabs justify-content-end mt-2" id="navTabs">
<li class="nav-item active">
        <a class="nav-link fs-5" href="admin_pendingOrders.php">Pending Orders</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="admin_acceptedOrders.php">Accepted Orders</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="admin_declineOrders.php">Declined Orders</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="admin_orders.php">Orders</a>
    </li>
</ul>
<div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="dataTable" class="table table-hover table-bordered">
                                        <thead><h1 class="text-center mb-3">PENDING ORDERS</h1> 
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">Order ID</th>
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Product Name</th>                                                
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Payment Type</th>                                                   
                                                <th scope="col">Payable</th>
                                                <th scope="col">Total Amount</th>    
                                                <th scope="col">Order Date</th>                                                                    
                                                <th scope="col">Action</th>    
                                                                                  
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $query = "
                                                SELECT 
                                                    orders.orderNo, 
                                                    orders.fullName, 
                                                    GROUP_CONCAT(order_items.prodName SEPARATOR ', ') AS productNames, 
                                                    SUM(order_items.quantity) AS totalQuantity,
                                                    orders.paymentType, 
                                                    orders.payable, 
                                                    orders.totalPrice, 
                                                    orders.orderDate
                                                FROM orders
                                                INNER JOIN order_items ON orders.orderNo = order_items.orderNo
                                                WHERE orders.status = 'Pending Order'
                                                GROUP BY orders.orderNo
                                            ";
                                            $stmt = $con->prepare($query);
                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    ?>
                                                    <tr class="text-center">
                                                        <td data-label="Order ID"><?php echo $row['orderNo']; ?></td>
                                                        <td data-label="Customer Name"><?php echo $row['fullName']; ?></td>
                                                        <td data-label="Product Name"><?php echo $row['productNames']; ?></td>
                                                        <td data-label="Total Quantity"><?php echo $row['totalQuantity']; ?></td>
                                                        <td data-label="Payment Type"><?php echo $row['paymentType']; ?></td>
                                                        <td data-label="Payable"><?php echo $row['payable']; ?></td>
                                                        <td data-label="Total Amount"><?php echo $row['totalPrice']; ?></td>
                                                        <td data-label="Order Date"><?php echo $row['orderDate']; ?></td>
                                                        <td data-label="Actions">
                                                            <div class="buttons d-flex justify-content-center" style="gap:5px;">
                                                            <a href="#" data-bs-toggle="modal" data-bs-target="#acceptModal" data-order-no="<?php echo $row['orderNo']; ?>">
                                                            <button type="button" class="btn btn-success d-flex align-items-center">
                                                                <i class="bi bi-check-circle me-2"></i>
                                                                <span>Accept</span>
                                                            </button>
                                                            </a>
                                                            <button type="button" class="btn btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#declineModal" data-order-no="<?php echo $row['orderNo']; ?>">
                                                            <i class="bi bi-x-circle me-2"></i>
                                                            <span>Decline</span>
                                                            </button>
                                                            <a href="admin_orderInfo.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                                <button type="button" class="btn btn-primary me-2 d-flex align-items-center">
                                                                <i class="bi bi-arrow-right-circle me-2"></i>
                                                         <span>Details</span>
                                                                </button>
                                                            </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                            ?>

                                        </tbody>
                                    </table>
                                </div>    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="declineModalLabel">Confirm Decline</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to decline this order?
      </div>
      <div class="modal-footer">
        <form method="POST" action="admin_pendingOrders.php" id="declineForm">
          <input type="hidden" name="orderNo" id="declineOrderNo">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="declineOrder" class="btn btn-success">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="acceptModal" tabindex="-1" aria-labelledby="acceptModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="acceptModalLabel">Confirm Accept</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to accept this order?
      </div>
      <div class="modal-footer">
        <form method="POST" action="admin_pendingOrders.php" id="acceptForm">
          <input type="hidden" name="orderNo" id="acceptOrderNo">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="acceptOrder" class="btn btn-success">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>
    </body>
    </html>
<script type="text/javascript">
    var acceptModal = document.getElementById('acceptModal');
acceptModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget; // Button that triggered the modal
    var orderNo = button.getAttribute('data-order-no'); // Extract orderNo
    var inputOrderNo = acceptModal.querySelector('#acceptOrderNo'); // Get the hidden input in the modal
    inputOrderNo.value = orderNo; // Set the value of the hidden input to orderNo
});

var declineModal = document.getElementById('declineModal');
declineModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget; // Button that triggered the modal
    var orderNo = button.getAttribute('data-order-no'); // Extract orderNo
    var inputOrderNo = declineModal.querySelector('#declineOrderNo'); // Get the hidden input in the modal
    inputOrderNo.value = orderNo; // Set the value of the hidden input to orderNo
});
    document.addEventListener("DOMContentLoaded", function() {
    // Get all the nav items
    const navItems = document.querySelectorAll('.nav-item');

    // Loop through each nav item and add a click event listener
    navItems.forEach(item => {
        const link = item.querySelector('.nav-link');

        // Set up the click event for immediate style change and redirection
        item.addEventListener('click', function(e) {
            // Apply the color changes immediately
            navItems.forEach(nav => {
                // Reset all other nav items
                resetNavStyle(nav.querySelector('.nav-link'));
            });

            // Apply active styles to the clicked link
            applyClickStyle(link);
        });

        // Add a hover effect using JavaScript
        link.addEventListener('mouseover', function() {
            link.style.backgroundColor = '#007bff';
            link.style.color = 'white';
        });

        link.addEventListener('mouseout', function() {
            if (!item.classList.contains('active')) {
                link.style.backgroundColor = ''; // Reset to default
                link.style.color = ''; // Reset to default
            }
        });
    });

    // Function to apply the click styles (background and text color change)
    function applyClickStyle(link) {
        link.style.backgroundColor = '#28a745'; // Green background
        link.style.color = 'white'; // White text
    }

    // Function to reset styles when the tab is no longer active
    function resetNavStyle(link) {
        link.style.backgroundColor = ''; // Reset background color
        link.style.color = ''; // Reset text color
    }
});
</script>