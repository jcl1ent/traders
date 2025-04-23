<?php
$page_title = "Staff Service Requests";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

$loggedInUserId = $_SESSION['userId'];
$staffIdQuery = "SELECT staffId FROM staffs WHERE userId = '$loggedInUserId'";
$staffIdResult = mysqli_query($con, $staffIdQuery);

// Check if the query returned a result
if ($staffIdResult && mysqli_num_rows($staffIdResult) > 0) {
    $staffRow = mysqli_fetch_assoc($staffIdResult);
    $staffId = $staffRow['staffId']; // Assign the staffId to the variable
} else {
    echo "<script>alert('Error: Staff ID not found.')</script>";
    exit();
}

// Handle deletion request
if (isset($_GET['delete'])) {
    $reqserv = $_GET['delete'];

    // Update the servArchive field to 1 for the selected request
    $query = "UPDATE reqserv SET servArchive = '1', servStatus = 'Request Deleted' WHERE reqserv = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $reqserv);
    if ($stmt->execute()) {
        echo "<script>
    window.location.href = 'staff_serviceRequest.php';
</script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
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
        <a class="nav-link fs-5" href="staff_pendingserv.php">Pendings</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="staff_acceptedService.php">Accepted</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="staff_servdecline.php">Declined</a>
    </li>
    <li class="nav-item active">
        <a class="nav-link fs-5" href="staff_serviceRequest.php">Service Requests</a>
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
                                        <h1 class="text-center mb-3">SERVICE REQUESTS</h1> 
                                        <tr class="text-center">                                 
                                            <th scope="col">Service Request ID</th>
                                            <th scope="col">Customer Name</th>
                                            <th scope="col">Urgent</th>
                                            <th scope="col">Service Type</th>
                                            <th scope="col">Payment Option</th>
                                            <th scope="col">Payment Type</th>    
                                            <th scope="col">Payable</th>
                                            <th scope="col">Total Amount</th>
                                            <th scope="col">Request Date</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Actions</th>                                                                 
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        $query = "
                                        SELECT users.fullName, users.userId, reqserv.*
                                        FROM reqserv
                                        INNER JOIN users ON reqserv.userId = users.userId
                                        INNER JOIN acceptserv2 ON reqserv.reqserv = acceptserv2.reqserv
                                        WHERE acceptserv2.staffId = '$staffId' AND reqserv.servArchive = '0'
                                        ORDER BY reqserv.reqserv DESC
                                    ";
                                    $stmt = $con->prepare($query);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            // Check if staffId matches for this request
                                            $checkStaffQuery = "
                                                SELECT * FROM acceptserv2
                                                WHERE reqserv = ? AND staffId = ? AND mpId IS NULL
                                            ";
                                            $checkStaffStmt = $con->prepare($checkStaffQuery);
                                            $checkStaffStmt->bind_param('ii', $row['reqserv'], $staffId);
                                            $checkStaffStmt->execute();
                                            $checkStaffResult = $checkStaffStmt->get_result();
                                                                                        
                                            // Check if mpId exists in accept_reqserv for the current service request
                                            $checkMpQuery = "
                                                SELECT mpId FROM acceptserv2
                                                WHERE reqserv = ? AND mpId IS NOT NULL
                                            ";
                                            $checkMpStmt = $con->prepare($checkMpQuery);
                                            $checkMpStmt->bind_param('i', $row['reqserv']);
                                            $checkMpStmt->execute();
                                            $checkMpResult = $checkMpStmt->get_result();
                                            
                                            // Determine the redirection links
                                            if ($checkStaffResult->num_rows > 0) {
                                                // If staffId exists in acceptserv1
                                                $detailsLink = "staff_pendservInfo2.php";
                                                $updateLink = "staff_serviceUpdate.php";
                                            } elseif ($checkMpResult->num_rows > 0) {
                                                // If mpId exists in accept_reqserv
                                                $detailsLink = "staff_acceptedservInfo2.php";
                                                $updateLink = "staff_serviceUpdate2.php";   
                                            } elseif ($row['servStatus'] === 'Pending Request') {
                                                // If status is Pending
                                                $detailsLink = "staff_servReqInfo.php";
                                                $updateLink = "";
                                            } 
                                            ?>
                                            <tr class="text-center">
                                                <td data-label="Request ID"><?php echo $row['reqserv']; ?></td>
                                                <td data-label="Customer Name"><?php echo $row['fullName']; ?></td>
                                                <td data-label="Customer Name"><?php echo $row['urgent']; ?></td>
                                                <td data-label="Service Type"><?php echo $row['servType']; ?></td>
                                                <td data-label="Payment Option"><?php echo $row['payOpt']; ?></td>
                                                <td data-label="Payment Type"><?php echo $row['paymentType']; ?></td>
                                                <td data-label="Payable"><?php echo $row['payable']; ?></td>
                                                <td data-label="Total Amount"><?php echo $row['totalAmount']; ?></td>
                                                <td><?php echo $row['createDate']; ?></td>
                                                <td data-label="Status"><?php echo $row['servStatus']; ?></td>
                                                <td data-label="Actions">
                                                    <div class="d-flex justify-content-center gap-2">
                                                    <?php if(strpos($row['servStatus'], 'Completed') === false) { ?>
                                                        <a href="<?php echo $detailsLink; ?>?reqserv=<?php echo $row['reqserv']; ?>">
                                                            <button type="button" class="btn btn-primary d-flex align-items-center"><i class="bi bi-arrow-right-circle me-2"></i>
                                                            <span>Details</span></button>
                                                        </a>
                                                        <?php if ($row['servStatus'] !== 'Declined') { ?>
                                                            <a href="<?php echo $updateLink; ?>?reqserv=<?php echo $row['reqserv']; ?>">
                                                            <button type="button" class="btn btn-success d-flex align-items-center">
                                                            <i class="bi bi-arrow-repeat me-2"></i>
                                                            <span>Update</span>
                                                            </button>
                                                            </a>
                                                        <?php } ?>
                                                        <a href="staff_serviceRequest.php?delete=<?php echo $row['reqserv']; ?>" onclick="return confirm('Are you sure you want to delete this request?');">
                                                            <button type="button" class="btn btn-danger d-flex align-items-center"><i class="bi bi-trash3 me-2"></i>
                                                            <span>Delete</span></button>
                                                        </a>
                                                        <?php } else { ?>
                                                            <a href="<?php echo $detailsLink; ?>?reqserv=<?php echo $row['reqserv']; ?>">
                                                            <button type="button" class="btn btn-primary d-flex align-items-center"><i class="bi bi-arrow-right-circle me-2"></i>
                                                            <span>Details</span></button>
                                                        </a>
                                                        <a href="staff_serviceRequest.php?delete=<?php echo $row['reqserv']; ?>" onclick="return confirm('Are you sure you want to delete this request?');">
                                                            <button type="button" class="btn btn-danger d-flex align-items-center"><i class="bi bi-trash3 me-2"></i>
                                                            <span>Delete</span></button>
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
<script>
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
</body>
</html>
