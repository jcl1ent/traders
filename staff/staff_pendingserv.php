<?php
$page_title = "Staff Service Requests";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

$loggedInUserId = $_SESSION['userId'];
$staffIdQuery = "SELECT staffId, firstname, middlename, lastname FROM staffs WHERE userId = '$loggedInUserId'";
$staffIdResult = mysqli_query($con, $staffIdQuery);

// Check if the query returned a result
if ($staffIdResult && mysqli_num_rows($staffIdResult) > 0) {
    $staffRow = mysqli_fetch_assoc($staffIdResult);
    $staffId = $staffRow['staffId']; // Assign the staffId to the variable
    
    // Concatenate the full name
    $fullName = $staffRow['firstname'] . ' ' . $staffRow['middlename'] . ' ' . $staffRow['lastname'];
} else {
    echo "<script>alert('Error: Staff ID not found.')</script>";
    exit();
}

if (isset($_POST['accept_request'])) {
    // Retrieve necessary POST data
    $mpId = $_POST['mpId']; // Get the mpId from the POST request
    $reqserv = $_POST['reqserv'];
    $schedule = $_POST['schedule'];
    $assignedDate = date('Y-m-d H:i:s');

    $sql = "SELECT adminId FROM admin LIMIT 1";  
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($adminId);
    $stmt->fetch();
    $stmt->close();

    // Retrieve custId based on reqserv userId from customers table
    $fetch_customer_query = "SELECT custId FROM customers WHERE userId = (SELECT userId FROM reqserv WHERE reqserv = ?)";
    $fetch_customer_stmt = $con->prepare($fetch_customer_query);
    $fetch_customer_stmt->bind_param("i", $reqserv);
    $fetch_customer_stmt->execute();
    $fetch_customer_stmt->store_result();

    if ($fetch_customer_stmt->num_rows > 0) {
        $fetch_customer_stmt->bind_result($custId);
        $fetch_customer_stmt->fetch();
    } else {
        echo "<script>alert('Error: Customer not found.')</script>";
        exit();
    }
    $fetch_customer_stmt->close();

    // Fetch totalAmount, paymentType, and payable from reqserv table
    $fetch_desc_price_query = "SELECT description, totalAmount, paymentType, payable, userId FROM reqserv WHERE reqserv = ?";
    $fetch_desc_price_stmt = $con->prepare($fetch_desc_price_query);
    $fetch_desc_price_stmt->bind_param("i", $reqserv);
    $fetch_desc_price_stmt->execute();
    $fetch_desc_price_stmt->store_result();

    if ($fetch_desc_price_stmt->num_rows > 0) {
        $fetch_desc_price_stmt->bind_result($description, $totalAmount, $paymentType, $payable, $userId);
        $fetch_desc_price_stmt->fetch();
    } else {
        echo "<script>alert('Error: No description or rate found for this request.')</script>";
        exit();
    }
    $fetch_desc_price_stmt->close();

    // Calculate balance based on payment type
    $balance = ($paymentType == 'Partial') ? $payable : 0;

    // Fetch pendservice from acceptserv2 table
    $fetch_pendservice_query = "SELECT pendservice FROM acceptserv2 WHERE reqserv = ?";
    $fetch_pendservice_stmt = $con->prepare($fetch_pendservice_query);
    $fetch_pendservice_stmt->bind_param("i", $reqserv);
    $fetch_pendservice_stmt->execute();
    $fetch_pendservice_stmt->store_result();

    if ($fetch_pendservice_stmt->num_rows > 0) {
        $fetch_pendservice_stmt->bind_result($pendservice);
        $fetch_pendservice_stmt->fetch();
    } else {
        echo "<script>alert('Error: No pendservice found for this request.')</script>";
        exit();
    }
    $fetch_pendservice_stmt->close();

    // Check if reqserv already exists in acceptserv2 table
    $check_reqserv_query = "SELECT reqserv FROM acceptserv2 WHERE reqserv = ?";
    $check_reqserv_stmt = $con->prepare($check_reqserv_query);
    $check_reqserv_stmt->bind_param("i", $reqserv);
    $check_reqserv_stmt->execute();
    $check_reqserv_stmt->store_result();

    if ($check_reqserv_stmt->num_rows == 0) {
        // If not present, insert it into acceptserv2 table first
        $insert_reqserv_query = "INSERT INTO acceptserv2 (reqserv) VALUES (?)";
        $insert_reqserv_stmt = $con->prepare($insert_reqserv_query);
        $insert_reqserv_stmt->bind_param("i", $reqserv);

        if (!$insert_reqserv_stmt->execute()) {
            echo "<script>alert('Error: Unable to insert into reqserv table.')</script>";
            exit();
        }

        $insert_reqserv_stmt->close();
    }

    $check_reqserv_stmt->close();

    // Begin the transaction
    $con->begin_transaction();

    try {

    $update_query1 = "UPDATE acceptserv2 
    SET mpId = ?, schedule = ?, status = 'Assigned'
    WHERE reqserv = ?"; 
    $update_stmt1 = $con->prepare($update_query1);
    $update_stmt1->bind_param("isi", $mpId, $schedule, $reqserv);

    if (!$update_stmt1->execute()) {
    throw new Exception('Error updating accept_reqserv table.');
    }


        // Update the manpower status to Not Available
        $update_mp_status = "UPDATE manpower SET mpStatus = 'Not Available' WHERE mpId = ?";
        $update_stmt = $con->prepare($update_mp_status);
        $update_stmt->bind_param("i", $mpId);
        if (!$update_stmt->execute()) {
            throw new Exception('Error updating manpower status.');
        }
        $update_stmt->close();

        // Update the service status
        $update_serv_status = "UPDATE reqserv SET servStatus = 'Pending Service' WHERE reqserv = ?";
        $update_stmt = $con->prepare($update_serv_status);
        $update_stmt->bind_param("i", $reqserv);
        if (!$update_stmt->execute()) {
            throw new Exception('Error updating service status.');
        }
        $update_stmt->close();

        // Insert data into payment table
        $payment_query = "INSERT INTO payment (pendservice, staffId, custId, totalAmount, paymentType, payable, balance, paymentDate, paymentStatus)  
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'To be Paid')";
        $payment_stmt = $con->prepare($payment_query);
        $payment_stmt->bind_param("iiidsss", $pendservice, $staffId, $custId, $totalAmount, $paymentType, $payable, $balance);

        if (!$payment_stmt->execute()) {
            throw new Exception('Error inserting into payment table.');
        }
        $payment_stmt->close();

        // Commit the transaction if everything is successful
        $con->commit();

        // Redirect after successful operation
        echo "<script>window.location.href = 'staff_pendingserv.php';</script>";

    } catch (Exception $e) {
        // If any exception occurs, rollback the transaction and display error
        $con->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "')</script>";
        exit();
    }
    $log_action_query = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
    $action = $fullName. ' has assigned a manpower and schedule to Service No.' .$pendservice; 
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $adminId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();

    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = $fullName. ' has assigned a manpower and schedule to your Service No.' .$pendservice. ' and you can now process your payment'; 
    $status = 'unread';
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
</head>
<body>
<ul class="nav nav-tabs justify-content-end mt-2" id="navTabs">
<li class="nav-item active">
        <a class="nav-link fs-5" href="staff_pendingserv.php">Pendings</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="staff_acceptedService.php">Accepted</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="staff_servdecline.php">Declined</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="staff_serviceRequest.php">Service Requests</a>
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
                                    <h1 class="text-center mb-3">PENDING SERVICE REQUESTS</h1>
                                    <tr class="text-center">
                                        <th scope="col">Request Number</th>
                                        <th scope="col">Customer Name</th>
                                        <th scope="col">Staff Name</th>
                                        <th scope="col">Urgent</th>
                                        <th scope="col">Service Type</th>
                                        <th scope="col">Payment Option</th>
                                        <th scope="col">Payable</th>
                                        <th scope="col">Total Amount</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    // SQL query to fetch pending service requests for the logged-in staff
                                    $query = "
                                        SELECT  
                                            users.fullName AS customerName,
                                            CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                            acceptserv2.pendservice,
                                            reqserv.*
                                        FROM acceptserv2
                                        INNER JOIN users ON acceptserv2.userId = users.userId
                                        INNER JOIN reqserv ON acceptserv2.reqserv = reqserv.reqserv
                                        INNER JOIN staffs ON acceptserv2.staffId = staffs.staffId
                                        WHERE acceptserv2.staffId = '$staffId' AND acceptserv2.status = 'Unassigned' AND reqserv.servArchive = '0'
                                        ORDER BY acceptserv2.pendservice DESC
                                    ";
                                    // Execute the query
                                    $result = mysqli_query($con, $query);

                                    if ($result) {
                                        if (mysqli_num_rows($result) > 0) {
                                            // Fetch and display each row of the pending service data
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                <tr class="text-center">
                                                    <td><?php echo htmlspecialchars($row['pendservice']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['customerName']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['staffName']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['urgent']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['servType']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['payOpt']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['payable']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['totalAmount']); ?></td>
                                                    <td>
                                                    <div class="d-flex justify-content-center align-items-center">
                                                    <button type="button" class="btn btn-success d-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#assignManpowerModal" data-reqserv-no="<?php echo $row['reqserv']; ?>">
                                                        <i class="bi bi-person-check me-2"></i>
                                                        <span>Assign</span>
                                                    </button>
                                                    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="location.href='staff_pendservInfo.php?pendservice=<?php echo $row['pendservice']; ?>'">
                                                        <i class="bi bi-arrow-right-circle me-2"></i>
                                                        <span>Details</span>
                                                    </button>
                                                    </div>
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
<!-- Assign Manpower Modal -->
<div class="modal fade" id="assignManpowerModal" tabindex="-1" aria-labelledby="assignManpowerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignManpowerModalLabel">Assign Manpower</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="assignManpowerForm">
                    <input type="hidden" name="reqserv" id="reqservInput" value="">
                    <div class="mb-3">
                        <label for="mpId" class="form-label">Select Manpower:</label>
                        <select class="form-select" name="mpId" id="mpSelect" required>
                            <option value="" disabled selected>Select Manpower</option>
                            <?php
                            // Fetch available manpower with expertise levels
                            $manpowerQuery = "
                                SELECT m.mpId, m.fullName, 
                                       me.expertise1, me.expertise2, me.expertise3, me.expertise4
                                FROM manpower m
                                LEFT JOIN manpower_expertise me ON m.mpId = me.mpId
                                WHERE m.mpArchive = 0 AND m.mpStatus = 'Available'";
                            $manpowerResult = $con->query($manpowerQuery);
                            while ($manpowerRow = $manpowerResult->fetch_assoc()) {
                                // Store expertise as a JSON object in data-expertise
                                $expertise = json_encode([
                                    'expertise1' => $manpowerRow['expertise1'],
                                    'expertise2' => $manpowerRow['expertise2'],
                                    'expertise3' => $manpowerRow['expertise3'],
                                    'expertise4' => $manpowerRow['expertise4']
                                ]);
                                echo "<option value='{$manpowerRow['mpId']}' data-expertise='{$expertise}'>{$manpowerRow['fullName']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="schedule" class="form-label">Schedule Date and Time:</label>
                        <input type="datetime-local" class="form-control" name="schedule" required>
                    </div>
                    <div id="expertiseDetails" class="mt-3" style="display:none;">
                        <h5>Expertise Levels:</h5>
                        <ul id="expertiseList"></ul>
                    </div>
                    <button type="submit" name="accept_request" class="btn btn-primary">Accept</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<script>
    // Add event listener to the select dropdown
document.getElementById('mpSelect').addEventListener('change', function () {
    // Get selected option
    const selectedOption = this.options[this.selectedIndex];
    
    // Get expertise data stored in the 'data-expertise' attribute
    const expertiseData = selectedOption.getAttribute('data-expertise');
    
    if (expertiseData) {
        // Parse the expertise JSON data
        const expertise = JSON.parse(expertiseData);
        
        // Show expertise levels in the modal
        const expertiseList = document.getElementById('expertiseList');
        expertiseList.innerHTML = ''; // Clear previous list
        
        // Create list items for each expertise level
        Object.keys(expertise).forEach(key => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${key.replace('expertise', 'Expertise ')}:</strong> ${expertise[key]}`;
            expertiseList.appendChild(li);
        });
        
        // Show the expertise section
        document.getElementById('expertiseDetails').style.display = 'block';
    }
});

        // Script to set the reqserv number in the modal
        const assignButtons = document.querySelectorAll('[data-bs-target="#assignManpowerModal"]');
        assignButtons.forEach(button => {
            button.addEventListener('click', function() {
                const reqservNo = this.getAttribute('data-reqserv-no');
                document.getElementById('reqservInput').value = reqservNo; // Set the reqserv number in the hidden input
                console.log('Selected reqserv ID:', reqservNo);  // Debugging line
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1; // Months are zero-indexed, so add 1
        let dd = today.getDate();
        
        // Format the date as YYYY-MM-DD
        if (mm < 10) { mm = '0' + mm; }
        if (dd < 10) { dd = '0' + dd; }
        
        const formattedDate = `${yyyy}-${mm}-${dd}`;
        
        // Set the min attribute to the formatted date, disabling past dates
        document.querySelector('input[name="schedule"]').setAttribute('min', `${formattedDate}T00:00`);
    });
    // Function to set the reqserv ID dynamically when button is clicked
function setReqservId(reqservId) {
    document.getElementById('reqservInput').value = reqservId;
}
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