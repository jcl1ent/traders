<?php
include("logincode.php");
$page_title = "View Service";
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

if (isset($_POST['deleteReqsNo'])) {
    // Make sure to assign $_POST['reqserv'] to a variable
    $reqserv = $_POST['reqserv']; 

    // Prepare and execute the delete statement
    $delete_query = "UPDATE reqserv SET servStatus = 'Request Cancelled' WHERE reqserv = ?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $reqserv);

    if ($stmt->execute()) {
        echo "<script>alert('Service Request deleted successfully.')</script>";
        echo '<script>window.location="vservice_customer.php"</script>';
    } else {
        echo "<script>alert('Failed to delete Service Request.')</script>";
        echo '<script>window.location="vservice_customer.php"</script>';
    }

    $stmt->close();

    $log_action_query2 = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
        $action = $fullName . ' cancelled its service request.';
        $status = 'unread';
        $log_action_stmt2 = $con->prepare($log_action_query2);
        $log_action_stmt2->bind_param("iss", $adminId, $action, $status);
        $log_action_stmt2->execute();
        $log_action_stmt2->close();
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
    <li class="nav-item active">
        <a class="nav-link fs-5" href="vservice_customer.php">Requested Services</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="vserviceAcc_customer.php">Accepted Services</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="vserviceDec_customer.php">Declined Services</a>
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
                                                <th scope="col">Request Number</th>
                                                <th scope="col">Service Type</th>
                                                <th scope="col">Description</th>
                                                <th scope="col">Payment Option</th>
                                                <th scope="col">Payment Type</th>
                                                <th scope="col">Urgent</th>
                                                <th scope="col">Total Amount</th>
                                                <th scope="col">Payable</th> 
                                                <th scope="col">Branch</th>  
                                                <th scope="col">Request Date</th>    
                                                <th scope="col">Action</th>                                      
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
if (isset($_SESSION['email']) && isset($_SESSION['userId']) && isset($_SESSION['custId'])) {
    $userId = $_SESSION['userId'];
    $email = $_SESSION['email'];
    $custId = $_SESSION['custId'];

    // Get the information from the orders with descending order
    $vreqServ_query = "SELECT s.branch, r.*
                        FROM reqserv r
                        JOIN staffs s ON r.staffId = s.staffId 
                        WHERE r.userId = ? 
                        AND r.servStatus = 'Pending Request'AND r.servArchive = '0'
    ORDER BY r.reqserv DESC";
    $stmt_vServ = $con->prepare($vreqServ_query);
    $stmt_vServ->bind_param("i", $userId);
    $stmt_vServ->execute();
    $result_vServ = $stmt_vServ->get_result();

    if ($result_vServ->num_rows > 0) {
        while ($row = $result_vServ->fetch_assoc()) {                                                                                                                      
            ?>
            <tr class="text-center">
                <td data-label="Request Number"><?php echo $row['reqserv']; ?></td>                                                                    
                <td data-label="Service Type"><?php echo $row['servType']; ?></td>
                <td data-label="Description"><?php echo $row['description']; ?></td>   
                <td data-label="Payment Option"><?php echo $row['payOpt']; ?></td> 
                <td data-label="Payment Type"><?php echo $row['paymentType']; ?></td>
                <td data-label="Rate"><?php echo $row['urgent']; ?></td>
                <td data-label="Total Amount"><?php echo $row['totalAmount']; ?></td>  
                <td data-label="Payable"><?php echo $row['payable']; ?></td> 
                <td data-label="Branch"><?php echo $row['branch']; ?></td>    
                <td data-label="Request Date"><?php echo $row['createDate']; ?></td>
                <td data-label="Delete">
                    <button type="button" class="btn btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#deleteModal" data-reqserv="<?php echo $row['reqserv']; ?>" data-description="<?php echo $row['description']; ?>">
    <i class="bi bi-trash3 me-2"></i>
    <span>Delete</span>
</button>
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
        Are you sure you want to delete the Service Request?<br>
      </div>
      <div class="modal-footer">
        <form method="POST" action="vservice_customer.php" id="deleteForm">
          <input type="hidden" name="reqserv" id="reqserv">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="deleteReqsNo" class="btn btn-danger">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<script type="text/javascript">
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var reqserv = button.getAttribute('data-reqserv'); // Extract reqserv
        var inputReqsNo = deleteModal.querySelector('#reqserv'); // Get the hidden input inside the form
        inputReqsNo.value = reqserv; // Set the reqserv value in the hidden input

        // Update modal with service details
        deleteModal.querySelector('#modal-description').textContent = description;
    });

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
