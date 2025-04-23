<?php
$page_title = "Admin Payments";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if(isset($_POST['uploadReceiptButton'])){
    $pymntNo = $_POST['pymntNo'];

    $update_query = "UPDATE payment SET paymentStatus = 'Paid' WHERE pymntNo = ?";
    $stmt = $con->prepare($update_query);
    $stmt->bind_param("i", $pymntNo);
    $stmt->execute();
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
<ul class="nav nav-tabs justify-content-end mt-3" id="navTabs">
    <li class="nav-item">
        <a class="nav-link fs-5" href="admin_paymentsService.php">Request Services Payment</a>
    </li>
    <li class="nav-item active">
        <a class="nav-link fs-5" href="admin_paymentsOrder.php">Orders Payment</a>
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
                                        <thead>
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">Payment Number</th>
                                                <th scope="col">Order Number</th>
                                                <th scope="col">Total Amount</th> 
                                                <th scope="col">Payable</th>                                   
                                                <th scope="col">Balance</th>                                               
                                                <th scope="col">Payment Type</th>  
                                                <th scope="col">Payment Status</th>
                                                <th scope="col">Reference Code</th>
                                                <th scope="col">Action</th>                                      
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php                                            
                                                // Get the information from the orders
                                                $query = "
                                                        SELECT * FROM payment
                                                        WHERE orderNo IS NOT NULL
                                                        ORDER BY pymntNo DESC";
                                                $stmt = $con->prepare($query);
                                                 // Use the customer's ID to fetch only their orders
                                                $stmt->execute();
                                                $result = $stmt->get_result();

                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {                                                                
                                                        ?>
                                                        <tr class="text-center">                                                                    
                                                            <td data-label="Payment ID"><?php echo $row['pymntNo']; ?></td>
                                                            <td data-label="Order ID"><?php echo $row['orderNo']; ?></td>
                                                            <td data-label="Total Amount"><?php echo $row['totalAmount']; ?></td>
                                                            <td data-label="Payable"><?php echo $row['payable']; ?></td>
                                                            <td data-label="Balance"><?php echo $row['balance']; ?></td>
                                                            <td data-label="Payment Type"><?php echo $row['paymentType']; ?></td>                                                   
                                                            <td data-label="Payment Status"><?php echo $row['paymentStatus']; ?></td>
                                                            <td data-label="Payment Status"><?php echo $row['refCode']; ?></td>
                                                            <td data-label="Action">
                                                            <div class="actions d-flex justify-content-center" style="gap: 5px;">
                                                            <?php if (strtolower($row['paymentStatus']) == 'to be paid' || strpos($row['paymentStatus'], 'check') !== false) { ?>
                                                                    <!-- Display only "Check Payment" -->
                                                                    <a href="admin_paymentsOrderInfo.php?pymntNo=<?php echo $row['pymntNo']; ?>">
                                                                        <button type="button" class="btn btn-success d-flex align-items-center">
                                                                            <i class="bi bi-credit-card me-2"></i>
                                                                            <span>Check Payment</span>
                                                                        </button>
                                                                    </a>
                                                                <?php } else { ?>
                                                                    <!-- Display both "Check Payment" and "View Receipt" -->
                                                                    <a href="admin_paymentsOrderInfo.php?pymntNo=<?php echo $row['pymntNo']; ?>">
                                                                        <button type="button" class="btn btn-success d-flex align-items-center">
                                                                            <i class="bi bi-credit-card me-2"></i>
                                                                            <span>Check Payment</span>
                                                                        </button>
                                                                    </a>
                                                                    <a href="admin_payment_print.php?pymntNo=<?php echo $row['pymntNo']; ?>">
                                                                        <button type="button" class="btn btn-primary d-flex align-items-center">
                                                                            <i class="bi bi-file-earmark-text me-2"></i>
                                                                            <span>View Receipt</span>
                                                                        </button>
                                                                    </a>
                                                                <?php } ?>
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
</body>
</html>
<script>
// JavaScript to handle immediate style change and redirection
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
        //link.style.transition = 'background-color 0.2s, color 0.2s'; // Optional: smooth transition
    }

    // Function to reset styles when the tab is no longer active
    function resetNavStyle(link) {
        link.style.backgroundColor = ''; // Reset background color
        link.style.color = ''; // Reset text color
    }
});
</script>