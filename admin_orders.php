<?php
$page_title = "Admin Orders";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['deleteOrder'])) {
    $orderNo = $_POST['orderNo'];
    $con->begin_transaction();

    try {
        // Step 1: Delete payment associated with the order
        $delete_payment = "DELETE FROM payment WHERE orderNo = ?";
        $stmt = $con->prepare($delete_payment);
        $stmt->bind_param("i", $orderNo);
        $stmt->execute();

        // Step 2: Restore product quantities from `order_items`
        $sql = "SELECT prodNo, quantity FROM order_items WHERE orderNo = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $orderNo);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $prodNo = $row['prodNo'];
            $quantity = $row['quantity'];

            // Update the product quantity
            $sql_update_quantity = "UPDATE products SET quantity = quantity + ? WHERE prodNo = ?";
            $stmt_update = $con->prepare($sql_update_quantity);
            $stmt_update->bind_param("ii", $quantity, $prodNo);
            if (!$stmt_update->execute()) {
                throw new Exception("Error updating product quantity: " . $stmt_update->error);
            }
            $stmt_update->close();

            // Update product status to 'Available' if restored quantity is > 0
            $sql_update_product_status = "UPDATE products SET productStatus = 'Available' WHERE prodNo = ? AND quantity > 0";
            $stmt_update = $con->prepare($sql_update_product_status);
            $stmt_update->bind_param("i", $prodNo);
            $stmt_update->execute();
            $stmt_update->close();
        }

        // Step 3: Delete items from `order_items`
        $sql_delete_items = "DELETE FROM order_items WHERE orderNo = ?";
        $stmt_delete_items = $con->prepare($sql_delete_items);
        $stmt_delete_items->bind_param("i", $orderNo);
        $stmt_delete_items->execute();
        $stmt_delete_items->close();

        // Step 4: Delete the order from `orders`
        $sql_delete_order = "DELETE FROM orders WHERE orderNo = ?";
        $stmt_delete_order = $con->prepare($sql_delete_order);
        $stmt_delete_order->bind_param("i", $orderNo);
        $stmt_delete_order->execute();
        $stmt_delete_order->close();

        // Commit transaction
        $con->commit();

        echo "<script>alert('Order deleted successfully.')</script>";
        echo '<script>window.location="admin_orders.php"</script>';

    } catch (Exception $e) {
        // Rollback transaction on failure
        $con->rollback();
        echo "<script>alert('Failed to delete the order: " . $e->getMessage() . "')</script>";
        echo '<script>window.location="admin_orders.php"</script>';
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
<ul class="nav nav-tabs justify-content-end mt-2" id="navTabs">
<li class="nav-item ">
        <a class="nav-link fs-5" href="admin_pendingOrders.php">Pending Orders</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="admin_acceptedOrders.php">Accepted Orders</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="admin_declineOrders.php">Declined Orders</a>
    </li>
    <li class="nav-item active">
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
                                        <thead><h1 class="text-center mb-3">ORDERS</h1> 
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">Order ID</th>
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Product Name</th>                                                
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Payment Type</th>                                                   
                                                <th scope="col">Payable</th>
                                                <th scope="col">Total Amount</th>    
                                                <th scope="col">Order Date</th> 
                                                <th scope="col">Order Status</th>                       
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
                                                    orders.orderDate,
                                                    orders.status
                                                FROM orders
                                                INNER JOIN order_items ON orders.orderNo = order_items.orderNo
                                                GROUP BY orders.orderNo
                                                ORDER BY orders.orderDate desc
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
                                                        <td data-label="Product Names"><?php echo $row['productNames']; ?></td>                                                       
                                                        <td data-label="Quantity"><?php echo $row['totalQuantity']; ?></td>
                                                        <td data-label="Payment Type"><?php echo $row['paymentType']; ?></td>
                                                        <td data-label="Payable"><?php echo $row['payable']; ?></td>
                                                        <td data-label="Total Amount"><?php echo $row['totalPrice']; ?></td>
                                                        <td data-label="Order Date"><?php echo $row['orderDate']; ?></td>
                                                        <td data-label="Order Status"><?php echo $row['status']; ?></td>
                                                        <td data-label="Actions" class="d-flex justify-content-center" style="gap: 8px;">
                                                            <?php if($row['status'] == 'Declined'){ ?>
                                                                <a href="admin_orderInfo.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                                    <button type="button" class="btn btn-primary d-flex align-items-center">
                                                                    <i class="bi bi-arrow-right-circle me-2"></i>
                                                                    <span>Details</span>
                                                                    </button>
                                                                </a>
                                                                <button type="button" class="btn btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#deleteModal" data-order-no="<?php echo $row['orderNo']; ?>">
                                                                <i class="bi bi-trash3 me-2"></i>
                                                                 <span>Delete</span></button>
                                                                </button>
                                                            <?php }else{ ?>
                                                            <a href="admin_orderInfo.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                                <button type="button" class="btn btn-primary d-flex align-items-center">
                                                                <i class="bi bi-arrow-right-circle me-2"></i>
                                                                <span>Details</span>
                                                                </button>
                                                            </a>
                                                            <a href="admin_updateOrder.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                                <button type="button" class="btn btn-success d-flex align-items-center">
                                                                <i class="bi bi-arrow-repeat me-2"></i>
                                                                <span>Update</span>
                                                                </button>
                                                            </a>
                                                            <button type="button" class="btn btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#deleteModal" data-order-no="<?php echo $row['orderNo']; ?>">
                                                            <i class="bi bi-trash3 me-2"></i>
                                                            <span>Delete</span></button>
                                                            </button>
                                                            <?php } ?>
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
        <form method="POST" action="admin_orders.php" id="deleteForm">
          <input type="hidden" name="orderNo" id="orderNo">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="deleteOrder" class="btn btn-danger">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>
    </body>
<script type="text/javascript">
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var orderNo = button.getAttribute('data-order-no'); // Extract pdngReqsNo
        var inputorderNo = deleteModal.querySelector('#orderNo'); // Get the hidden input inside the form
        inputorderNo.value = orderNo; // Set the pdngReqsNo value in the hidden input
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