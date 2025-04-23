<?php
$page_title = "Admin Service Requests";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['accept_request'])) {
    // Retrieve necessary POST data
    $reqserv = $_POST['reqserv'];
    $acceptedDate = date('Y-m-d H:i:s');

    // Fetch details from reqserv table
    $fetch_user_query = "SELECT userId, servType, paymentType, payable, totalAmount, staffId FROM reqserv WHERE reqserv = ?";
    $fetch_user_stmt = $con->prepare($fetch_user_query);
    $fetch_user_stmt->bind_param("i", $reqserv);
    $fetch_user_stmt->execute();
    $fetch_user_stmt->store_result();

    if ($fetch_user_stmt->num_rows > 0) {
        $fetch_user_stmt->bind_result($userId, $servType, $paymentType, $payable, $totalAmount, $staffId);
        $fetch_user_stmt->fetch();
    } else {
        echo "<script>alert('Error: No such request found.')</script>";
        exit();
    }
    $fetch_user_stmt->close();

    $fetch_staff_query = "SELECT staffId FROM staffs WHERE branch = ? LIMIT 1";
    $fetch_staff_stmt = $con->prepare($fetch_staff_query);
    $fetch_staff_stmt->bind_param("s", $branch);
    $fetch_staff_stmt->execute();
    $fetch_staff_stmt->bind_result($staffId);
    $fetch_staff_stmt->fetch();
    $fetch_staff_stmt->close();

    if (empty($staffId)) {
        echo "<script>alert('Error: No staff available in the matched branch.')</script>";
        exit();
    }

    // Query to fetch custId associated with userId
    $fetch_cust_query = "SELECT custId FROM customers WHERE userId = ?";
    $fetch_cust_stmt = $con->prepare($fetch_cust_query);
    $fetch_cust_stmt->bind_param("i", $userId);
    $fetch_cust_stmt->execute();
    $fetch_cust_stmt->bind_result($custId);
    $fetch_cust_stmt->fetch();

    $fetch_cust_stmt->close();

    // Insert data into acceptserv1 and acceptserv2 tables
    $accept_query2 = "INSERT INTO acceptserv2 (reqserv, staffId, userId, acceptedDate, status) VALUES (?, ?, ?, ?, ?)";
    $accept_stmt2 = $con->prepare($accept_query2);
    $status = 'Unassigned'; 
    $accept_stmt2->bind_param("iiiss", $reqserv, $staffId, $userId, $acceptedDate, $status);

    if ($accept_stmt2->execute()) {
        // Update servStatus to 'Accepted' in reqserv table
        $update_status_query = "UPDATE reqserv SET servStatus = 'Accepted and Awaiting Schedule' WHERE reqserv = ?";
        $update_status_stmt = $con->prepare($update_status_query);
        $update_status_stmt->bind_param("i", $reqserv);
        if (!$update_status_stmt->execute()) {
            echo "<script>alert('Error updating servStatus to Accepted.')</script>";
            exit();
        }
        $update_status_stmt->close();

        // Success message
        echo "<script>alert('Staff has been successfully assigned for Request No. $reqserv');</script>";
    } else {
        echo "<script>alert('Error assigning staff. Please try again.')</script>";
    }
    $accept_stmt2->close();
    // Log the action in user_actions_logs
    $log_action_query = "INSERT INTO user_action_logs (staffId, action, status) VALUES (?, ?, ?)";
    $action = 'You have been assigned in Request No.' .$reqserv; 
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $staffId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();

    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'Your Request No.' .$reqserv . ' has been Accepted'; 
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();
}
// Decline Request logic
if (isset($_POST['decline_request'])) {
    $reqserv = $_POST['reqserv']; 
    $reason = $_POST['reason'];
    $declineDate = date('Y-m-d H:i:s');

    // Fetch userId from pending_reqserv table
    $fetch_user_query = "SELECT userId FROM reqserv WHERE reqserv = ?";
    $fetch_user_stmt = $con->prepare($fetch_user_query);
    $fetch_user_stmt->bind_param("i", $reqserv);
    $fetch_user_stmt->execute();
    $fetch_user_stmt->store_result();

    if ($fetch_user_stmt->num_rows > 0) {
        $fetch_user_stmt->bind_result($userId);
        $fetch_user_stmt->fetch();
    } else {
        echo "<script>alert('Error: No such request found.')</script>";
        exit();
    }
    $fetch_user_stmt->close();

    // Inserting the declined request into declined_reqserv
    $decline_query = "INSERT INTO declined_reqserv (reqserv, userId, reason, declineDate) VALUES (?, ?, ?, ?)";
    $decline_stmt = $con->prepare($decline_query);
    $decline_stmt->bind_param("iiss", $reqserv, $userId, $reason, $declineDate);

    if ($decline_stmt->execute()) {
        // Update servStatus to 'Decline' in reqserv table
        $update_status_query = "UPDATE reqserv SET servStatus = 'Request Declined' WHERE reqserv = ?";
        $update_status_stmt = $con->prepare($update_status_query);
        $update_status_stmt->bind_param("i", $reqserv);
        if (!$update_status_stmt->execute()) {
            echo "<script>alert('Error updating servStatus to Decline.')</script>";
            exit();
        }
        $update_status_stmt->close();
    }

    // Log the action in user_actions_logs
    $loggedUserId = $_SESSION['userId']; // assuming userId is stored in session
    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'Your Request No.' .$reqserv. 'has been Declined';
    $status = 'Unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
</head>
<body>
<ul class="nav nav-tabs justify-content-end mt-2" id="navTabs">
<li class="nav-item active">
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
    <li class="nav-item">
        <a class="nav-link fs-5" href="admin_service.php"><p class="fs-6 text-center">Service Requests</p></a>
    </li>
</ul>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover table-bordered">
                                <thead>
                                    <tr class="text-center"><h1 class="text-center mb-3">PENDING SERVICE REQUESTS</h1>
                                        <th scope="col">Request Number</th>
                                        <th scope="col">Customer Name</th>
                                        <th scope="col">Urgent</th>
                                        <th scope="col">Service Type</th>
                                        <th scope="col">Payment Option</th>
                                        <th scope="col">Payable</th>
                                        <th scope="col">Total Amount</th>
                                        <th scope="col">Request Date</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    $query = "
                                        SELECT users.fullName, users.userId, reqserv.* 
                                        FROM users
                                        INNER JOIN reqserv ON users.userId = reqserv.userId
                                        WHERE reqserv.servStatus = 'Pending Request' AND reqserv.servArchive = '0'
                                        ORDER BY reqserv.reqserv DESC
                                    ";
                                    $result = $con->query($query);

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            ?>
                                            <tr class="text-center">
                                                <td><?php echo $row['reqserv']; ?></td>
                                                <td><?php echo $row['fullName']; ?></td>
                                                <td><?php echo $row['urgent']; ?></td>
                                                <td><?php echo $row['servType']; ?></td>
                                                <td><?php echo $row['payOpt']; ?></td>
                                                <td><?php echo $row['payable']; ?></td>
                                                <td><?php echo $row['totalAmount']; ?></td>
                                                <td><?php echo $row['createDate']; ?></td>
                                                <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                <form action="admin_pendingService.php" method="POST" class="m-0">
                                                    <input type="hidden" name="reqserv" value="<?php echo $row['reqserv']; ?>">
                                                    <button type="submit" name="accept_request" class="btn btn-success d-flex align-items-center gap-2">
                                                        <i class="bi bi-check-circle"></i> 
                                                        <span>Accept</span>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-danger d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#declineModal" data-pdngreqs-no="<?php echo $row['reqserv']; ?>">
                                                    <i class="bi bi-x-circle"></i> 
                                                    <span>Decline</span>
                                                </button>
                                                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" onclick="location.href='admin_serviceInfo.php?reqserv=<?php echo $row['reqserv']; ?>'">
                                                    <i class="bi bi-arrow-right-circle"></i> 
                                                    <span>Details</span>
                                                </button>
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

<!-- Assign Staff Modal -->
<div class="modal fade" id="assignStaffModal" tabindex="-1" aria-labelledby="assignStaffLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignStaffLabel">Assign Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="admin_pendingService.php" method="POST">
                    <input type="hidden" name="reqserv" id="reqserv">
                    <p>Staff will be automatically assigned based on the branch.</p>
                    <button type="submit" name="accept_request" class="btn btn-success">Accept</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Decline Modal -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="declineModalLabel">Decline Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reqserv" id="declinePdngReqsNo">
                    <div class="form-group mb-3">
                        <label for="reason">Reason for Declining</label>
                        <textarea name="reason" id="reason" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="decline_request" class="btn btn-danger">Decline</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
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

document.addEventListener('DOMContentLoaded', function () {
    var assignStaffModal = document.getElementById('assignStaffModal');
    assignStaffModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var reqserv = button.getAttribute('data-pdngreqs-no');
        var modalInput = assignStaffModal.querySelector('#reqserv');
        modalInput.value = reqserv;
    });

    var declineModal = document.getElementById('declineModal');
    declineModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var reqserv = button.getAttribute('data-pdngreqs-no');
        var modalInput = declineModal.querySelector('#declinePdngReqsNo');
        modalInput.value = reqserv;
    });
});
</script>
