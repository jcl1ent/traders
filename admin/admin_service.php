<?php
$page_title = "Admin Service Requests";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

// Handle deletion request
if (isset($_GET['delete'])) {
    $reqserv = $_GET['delete'];

    // Update the servArchive field to 1 for the selected request
    $query = "UPDATE reqserv SET servArchive = '1' WHERE reqserv = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $reqserv);
    if ($stmt->execute()) {
        echo "<script>
    window.location.href = 'admin_service.php';
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
<li class="nav-item">
        <a class="nav-link fs-5" href="admin_pendingService.php">Pendings</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="admin_acceptedService.php">Accepted</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="admin_declineService.php">Declined</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="admin_unassigned.php">Awaiting Schedule</a>
    </li>
    <li class="nav-item active">
        <a class="nav-link fs-5" href="admin_service.php"><p class="fs-6 text-center">Service Requests</p></a>
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
                                            <th scope="col">Service Request Number</th>
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
    WHERE reqserv.servArchive = '0' 
    ORDER BY reqserv.reqserv DESC
";
$stmt = $con->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Check if there is an mpId in accept_reqserv for the current service request
        $checkQuery = "SELECT mpId FROM acceptserv2 WHERE reqserv = ? AND mpId IS NOT NULL";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param('i', $row['reqserv']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        // Check if there is a record in acceptserv2 for the current service request
        $checkStaffQuery = "SELECT * FROM acceptserv2 WHERE reqserv = ? AND staffId IS NOT NULL AND mpId IS NULL";
        $checkStaffStmt = $con->prepare($checkStaffQuery);
        $checkStaffStmt->bind_param('i', $row['reqserv']);
        $checkStaffStmt->execute();
        $checkStaffResult = $checkStaffStmt->get_result();
        
        // Determine the redirection links
        if ($checkStaffResult->num_rows > 0) {
            // If staffId exists in acceptserv1
            $detailsLink = "admin_unassignedInfo2.php";
            $updateLink = "admin_serviceUpdate3.php";
        } elseif ($checkResult->num_rows > 0) {
            // If mpId exists in accept_reqserv
            $detailsLink = "admin_acceptedservInfo2.php";
            $updateLink = "admin_serviceUpdate.php";   
        } elseif ($row['servStatus'] === 'Pending Request') {
            // If status is Pending
            $detailsLink = "admin_serviceInfo2.php";
            $updateLink = "admin_serviceUpdate2.php";
        } else {
            // Default fallback
            $detailsLink = "admin_serviceInfo2.php";
            $updateLink = "admin_serviceUpdate2.php";
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
                    <a href="<?php echo $detailsLink; ?>?reqserv=<?php echo $row['reqserv']; ?>">
                        <button type="button" class="btn btn-primary d-flex align-items-center"><i class="bi bi-arrow-right-circle me-2"></i>
                        <span>Details</span></button>
                    </a>
                        <a href="<?php echo $updateLink; ?>?reqserv=<?php echo $row['reqserv']; ?>">
                            <button type="button" class="btn btn-success d-flex align-items-center">
                                <i class="bi bi-arrow-repeat me-2"></i>
                                <span>Update</span>
                            </button>
                        </a>

                    <a href="admin_service.php?delete=<?php echo $row['reqserv']; ?>" onclick="return confirm('Are you sure you want to delete this request?');">
                        <button type="button" class="btn btn-danger d-flex align-items-center"><i class="bi bi-trash3 me-2"></i>
                        <span>Delete</span></button>
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
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            const link = item.querySelector('.nav-link');
            item.addEventListener('click', function(e) {
                navItems.forEach(nav => {
                    resetNavStyle(nav.querySelector('.nav-link'));
                });
                applyClickStyle(link);
            });
            link.addEventListener('mouseover', function() {
                link.style.backgroundColor = '#007bff';
                link.style.color = 'white';
            });
            link.addEventListener('mouseout', function() {
                if (!item.classList.contains('active')) {
                    link.style.backgroundColor = ''; 
                    link.style.color = ''; 
                }
            });
        });

        function applyClickStyle(link) {
            link.style.backgroundColor = '#28a745'; 
            link.style.color = 'white'; 
        }

        function resetNavStyle(link) {
            link.style.backgroundColor = ''; 
            link.style.color = ''; 
        }
    });
</script>
</body>
</html>