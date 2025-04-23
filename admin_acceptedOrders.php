<?php
include("logincode.php");
$page_title = "Admin Pending Orders";

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
<ul class="nav nav-tabs justify-content-end mt-2" id="navTabs">
<li class="nav-item ">
        <a class="nav-link fs-5" href="admin_pendingOrders.php">Pending Orders</a>
    </li>
    <li class="nav-item active">
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
                                        <thead><h1 class="text-center mb-3">ACCEPTED ORDERS</h1> 
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">Order ID</th>
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Product Name</th>                                                
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Payment Type</th>                                                   
                                                <th scope="col">Payable</th>
                                                <th scope="col">Total Amount</th>   
                                                <th scope="col">Tracking Reference</th>
                                                <th scope="col">Order Date</th>                                                                    
                                                <th scope="col"></th>    
                                                                                  
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
                                                 orders.orderTrackNo
                                             FROM orders
                                             INNER JOIN order_items ON orders.orderNo = order_items.orderNo                                                                                                                                 
                                             WHERE orders.status != 'Pending Order' AND orders.status != 'order Declined'
                                             GROUP BY orders.orderNo
                                            ";
                                            $stmt = $con->prepare($query);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    ?>
                                                    <tr class="text-center">
                                                        <td data-label="Order ID" style="width: 5%;"><?php echo $row['orderNo']; ?></td>
                                                        <td data-label="Customer Name" style="width: 10%;"><?php echo $row['fullName']; ?></td>
                                                        <td data-label="Service Type" style="width: 10%;"><?php echo $row['productNames']; ?></td>                                                       
                                                        <td data-label="Quantity" style="width: 10%;"><?php echo $row['totalQuantity']; ?></td>
                                                        <td data-label="Payment Type" style="width: 10%;"><?php echo $row['paymentType']; ?></td>
                                                        <td data-label="Payable" style="width: 10%;"><?php echo $row['payable']; ?></td>
                                                        <td data-label="Total Amount" style="width: 10%;"><?php echo $row['totalPrice']; ?></td>
                                                        <?php if($row['orderTrackNo'] == NULL){ ?>
                                                        <td data-label="Tracking Number" style="width: 15%;"><a href="addTrackNo.php?orderNo=<?php echo $row['orderNo']; ?>">Add Tracking Reference</a></td>
                                                        <?php } else { ?>
                                                            <td data-label="Tracking Number" style="width: 15%;">
                                                                <a href="<?php echo htmlspecialchars($row['orderTrackNo']); ?>">
                                                                    <?php echo htmlspecialchars($row['orderTrackNo']); ?>
                                                                </a>
                                                            </td>
                                                        <?php } ?>
                                                        <td data-label="Total Amount" style="width: 10%;"><?php echo $row['orderDate']; ?></td>
                                                        <td data-label="Actions" style="width: 10%;">
                                                            <a href="admin_orderInfo.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                                <button type="button" class="btn  btn-primary">
                                                                    Details
                                                                </button>
                                                            </a>                                                                                                                       
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
    </body>
    </html>
<script type="text/javascript">
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