<?php
$page_title = "Staff Orders";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

$loggedInUserId = $_SESSION['userId'];
$staffIdQuery = "SELECT staffId FROM staffs WHERE userId = '$loggedInUserId'";
$staffIdResult = mysqli_query($con, $staffIdQuery);

if ($staffIdResult && mysqli_num_rows($staffIdResult) > 0) {
    $staffRow = mysqli_fetch_assoc($staffIdResult);
    $staffId = $staffRow['staffId']; // Assign the staffId to the variable
} else {
    echo "<script>alert('Error: Staff ID not found.')</script>";
    exit();
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
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="dataTable" class="table table-hover table-bordered">
                                        <thead><h1 class="text-center mb-3">ORDERS</h1> 
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">Order Number</th>
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
                                                    orders.status,
                                                    orders.orderTrackNo
                                                FROM orders
                                                INNER JOIN order_items ON orders.orderNo = order_items.orderNo
                                                INNER JOIN staffs ON orders.assignedStaff = staffs.staffId
                                                WHERE orders.assignedStaff = '$staffId'
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
                                                        <td data-label="Service Type"><?php echo $row['productNames']; ?></td>                                                       
                                                        <td data-label="Quantity"><?php echo $row['totalQuantity']; ?></td>
                                                        <td data-label="Payment Type"><?php echo $row['paymentType']; ?></td>
                                                        <td data-label="Payable"><?php echo $row['payable']; ?></td>
                                                        <td data-label="Total Amount"><?php echo $row['totalPrice']; ?></td>
                                                        <td data-label="Total Amount"><?php echo $row['orderDate']; ?></td>
                                                        <?php if($row['orderTrackNo'] == NULL){ ?>
                                                        <td data-label="Tracking Number" style="width: 15%;"><a href="addTrackNo_staff.php?orderNo=<?php echo $row['orderNo']; ?>">Add Tracking Reference</a></td>
                                                        <?php } else { ?>
                                                            <td data-label="Tracking Number" style="width: 15%;">
                                                                <a href="<?php echo htmlspecialchars($row['orderTrackNo']); ?>">
                                                                    <?php echo htmlspecialchars($row['orderTrackNo']); ?>
                                                                </a>
                                                            </td>
                                                        <?php } ?>
                                                        <td data-label="Actions" class="d-flex justify-content-center" style="gap: 5px;">
                                                            <?php if($row['status'] == 'Declined'){ ?>
                                                                <a href="staff_orderInfo.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                                <button type="button" class="btn btn-primary d-flex align-items-center">
                                                                <i class="bi bi-arrow-right-circle me-2"></i>
                                                                <span>Details</span>
                                                                </button>
                                                                </a>
                                                               
                                                            <?php }else{ ?>
                                                            <a href="staff_orderInfo.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                            <button type="button" class="btn btn-primary d-flex align-items-center">
                                                                <i class="bi bi-arrow-right-circle me-2"></i>
                                                                <span>Details</span>
                                                                </button>
                                                            </a>
                                                            <a href="staff_updateOrder.php?orderNo=<?php echo $row['orderNo']; ?>">
                                                            <button type="button" class="btn btn-success d-flex align-items-center">
                                                                <i class="bi bi-arrow-repeat me-2"></i>
                                                                <span>Update</span>
                                                                </button>
                                                            </a>
                                                    
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
    </body>
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