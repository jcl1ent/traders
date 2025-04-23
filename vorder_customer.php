<?php
include("logincode.php");
$page_title = "View Order";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Get userId from users table using email
    $sql = "SELECT userId FROM users WHERE email = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId);
    $stmt->fetch();
    $stmt->close();

    // Use userId to get address from customers table
    $sql = "SELECT address, custId, firstname, middlename, lastname FROM customers WHERE userId = ?";
    $stmt = $con->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Bind the results to variables
        $stmt->bind_result($address, $custId, $firstname, $middlename, $lastname);

        if (!$stmt->fetch()) {
            // If no result, set default values
            $address = '';
            $custId = null;
            $firstname = '';
            $middlename = '';
            $lastname = '';
        }

        $stmt->close();
    } else {
        // Handle the case where the statement couldn't be prepared
        die("Error preparing the SQL query: " . $con->error);
    }
    // Combine the full name (optional)
    $fullName = trim("$firstname $middlename $lastname");

    // Fetch the first available adminId from admin table
    $sql = "SELECT adminId FROM admin LIMIT 1";  
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($adminId);
    $stmt->fetch();
    $stmt->close();
}

if (isset($_POST['deleteOrder'])) {
    $orderNo = $_POST['orderNo'];

    // Start a transaction to ensure data integrity
    $con->begin_transaction();

    try {
        // Check if the order status is "Pending"
        $sql_check_status = "SELECT status FROM orders WHERE orderNo = ?";
        $stmt_check_status = $con->prepare($sql_check_status);
        $stmt_check_status->bind_param("i", $orderNo);
        $stmt_check_status->execute();
        $result_check_status = $stmt_check_status->get_result();

        if ($result_check_status->num_rows > 0) {
            $row_status = $result_check_status->fetch_assoc();
            if ($row_status['status'] !== 'Pending Order') {
                throw new Exception("Only orders with status 'Pending' can be deleted.");
            }
        } else {
            throw new Exception("Order not found.");
        }

        $stmt_check_status->close();

        // Retrieve the ordered products and their quantities
        $sql = "SELECT prodNo, quantity FROM order_items WHERE orderNo = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $orderNo);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $prodNo = $row['prodNo'];
            $quantity = $row['quantity'];

            // Restore the quantity of the product in the products table
            $sql_update_quantity = "UPDATE products SET quantity = quantity + ? WHERE prodNo = ?";
            $stmt_update = $con->prepare($sql_update_quantity);
            $stmt_update->bind_param("ii", $quantity, $prodNo);

            if (!$stmt_update->execute()) {
                throw new Exception("Error updating product quantity: " . $stmt_update->error);
            }

            $stmt_update->close();

            // Restore product status
            $sql_update_product_status = "UPDATE products SET productStatus = 'Available' WHERE prodNo = ?";
            $stmt_update = $con->prepare($sql_update_product_status);
            $stmt_update->bind_param("i", $prodNo);
            $stmt_update->execute();
            $stmt_update->close();
        }

        $stmt->close();

        $log_action_query2 = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
        $action = $fullName . ' cancelled its order request.';
        $status = 'unread';
        $log_action_stmt2 = $con->prepare($log_action_query2);
        $log_action_stmt2->bind_param("iss", $adminId, $action, $status);
        $log_action_stmt2->execute();
        $log_action_stmt2->close();

        // Fetch services from the database
        $services = [];
        $sql = "SELECT servCode, servName, rateService FROM services";
        $result = $con->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
        }
        // Delete the order items
        $sql_delete_items = "DELETE FROM order_items WHERE orderNo = ?";
        $stmt_delete_items = $con->prepare($sql_delete_items);
        $stmt_delete_items->bind_param("i", $orderNo);
        $stmt_delete_items->execute();
        $stmt_delete_items->close();

        // Delete the order
        $sql_delete_order = "DELETE FROM orders WHERE orderNo = ?";
        $stmt_delete_order = $con->prepare($sql_delete_order);
        $stmt_delete_order->bind_param("i", $orderNo);
        $stmt_delete_order->execute();
        $stmt_delete_order->close();

        // Commit the transaction
        $con->commit();

        echo "<script>alert('Order deleted successfully.')</script>";
        echo '<script>window.location="vorder_customer.php"</script>';

    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $con->rollback();
        echo "<script>alert('Failed to delete the order: " . $e->getMessage() . "')</script>";
        echo '<script>window.location="vorder_customer.php"</script>';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="dataTable" class="table table-hover table-bordered">
                                        <thead>
                                            <tr class="text-center">                                      
                                                <th scope="col">Order Number</th>
                                                <th scope="col">Product Name</th>
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Product Price</th>
                                                <th scope="col">Total Product Price</th>
                                                <th scope="col">Total Price</th>
                                                <th scope="col">Payment Type</th>
                                                <th scope="col">Payable</th>
                                                <th scope="col">Order Date</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Tracking Reference</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (isset($_SESSION['email']) && isset($_SESSION['custId'])) {
                                                $custId = $_SESSION['custId'];
                                            
                                                $query = "
                                                    SELECT 
                                                        orders.orderNo, 
                                                        GROUP_CONCAT(order_items.prodName SEPARATOR ', ') AS productNames, 
                                                        GROUP_CONCAT(order_items.prodPrice SEPARATOR ', ') AS prodPrices,
                                                        GROUP_CONCAT(order_items.totalProductPrice SEPARATOR ', ') AS totalProductPrices,
                                                        SUM(order_items.quantity) AS totalQuantity,
                                                        orders.paymentType, 
                                                        orders.payable, 
                                                        orders.totalPrice, 
                                                        orders.orderDate,
                                                        orders.status,
                                                        orders.orderTrackNo
                                                    FROM orders
                                                    INNER JOIN order_items ON orders.orderNo = order_items.orderNo
                                                    WHERE orders.custId = ?
                                                    GROUP BY orders.orderNo
                                                    ORDER BY orders.orderNo DESC
                                                ";
                                                
                                                $stmt = $con->prepare($query);
                                                $stmt->bind_param("i", $custId);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {                                                                
                                                        ?>
                                                        <tr class="text-center">                                                                    
                                                            <td><?php echo $row['orderNo']; ?></td>
                                                            <td><?php echo $row['productNames']; ?></td>
                                                            <td><?php echo $row['totalQuantity']; ?></td>
                                                            <td><?php echo $row['prodPrices']; ?></td>
                                                            <td><?php echo $row['totalProductPrices']; ?></td>
                                                            <td><?php echo $row['totalPrice']; ?></td>
                                                            <td><?php echo $row['paymentType']; ?></td>
                                                            <td><?php echo $row['payable']; ?></td>
                                                            <td><?php echo $row['orderDate']; ?></td>
                                                            <td><?php echo $row['status']; ?></td>
                                                            <?php if ($row['orderTrackNo'] == NULL) { ?>
                                                                <td> </td>
                                                                <?php }else {?>
                                                                <td>
                                                                    <a href="<?php echo htmlspecialchars($row['orderTrackNo']); ?>">
                                                                        <?php echo htmlspecialchars($row['orderTrackNo']); ?>
                                                                    </a>
                                                                </td>
                                                                
                                                            <?php }?>
                                                            <td>
                                                                <div class="actions d-flex justify-content-center" style="gap: 5px;">
                                                                    <?php if ($row['status'] === 'Pending Order') { ?>
                                                                        <button type="button" class="btn btn-danger d-flex align-items-center" 
                                                                                data-bs-toggle="modal" 
                                                                                data-bs-target="#deleteModal" 
                                                                                data-order-no="<?php echo $row['orderNo']; ?>">
                                                                                <i class="bi bi-trash3 me-2"></i>
                                                                                <span>Delete</span>
                                                                        </button>
                                                                    <?php } ?>
                                                                    <a href="vorderInfo_customer.php?orderNo=<?php echo $row['orderNo']?>">
                                                                        <button type="button" class="btn btn-primary me-2 d-flex align-items-center"><i class="bi bi-arrow-right-circle me-2"></i>
                                                                        <span>Details</span></button>
                                                                    </a>
                                                                    <?php 
                                                                    echo "<!-- Debugging: status: " . $row['status'] . " -->";
                                                                    if ($row['status'] === 'Order Delivered') { ?>
                                                                        <div class="text-center">
                                                                            <form action="order_feedback.php" method="GET" class="d-inline">
                                                                                <input type="hidden" name="userId" value="<?php echo $loggedUserId; ?>">
                                                                                <input type="hidden" name="trscnType" value="<?php echo htmlspecialchars($row['productNames']); ?>">
                                                                                <input type="hidden" name="orderNo" value="<?php echo htmlspecialchars($row['orderNo']); ?>">
                                                                                <button type="submit" class="btn btn-warning d-flex align-items-center">
                                                                                    <i class="bi bi-chat-dots me-2"></i>
                                                                                    <span>Feedback</span>
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    <?php } ?>
                                                            </td>                                                                   
                                                        </tr>
                                                        <?php 
                                                    }
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
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this order?
          </div>
          <div class="modal-footer">
            <form method="POST" action="vorder_customer.php" id="deleteForm">
              <input type="hidden" name="orderNo" id="orderNo">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
              <button type="submit" name="deleteOrder" class="btn btn-danger">Yes</button>
            </form>
            <script type="text/javascript">
        var deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var orderNo = button.getAttribute('data-order-no');
            var inputOrderNo = deleteModal.querySelector('#orderNo');
            inputOrderNo.value = orderNo;
        });
    </script>
          </div>
        </div>
      </div>
    </div>
</body>
</html>
