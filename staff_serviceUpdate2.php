<?php
$page_title = "Staff Service Requests";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['update'])) {
    $reqserv = $_POST['reqserv'];
    $contact_number = $_POST['contact_number'];

    if (!preg_match("/^09\d{9}$/", $contact_number)) {
        echo "<script>
                alert('Invalid contact number. It must start with 09 and be 11 digits long.');
                window.location.href = 'staff_serviceRequest.php.php';
              </script>";
        exit();
    }

    // Fetch current service request details
    $currentQuery = "SELECT * FROM reqserv WHERE reqserv = ?";
    $stmtCurrent = $con->prepare($currentQuery);
    $stmtCurrent->bind_param("i", $reqserv);
    $stmtCurrent->execute();
    $currentResult = $stmtCurrent->get_result();
    $currentRow = $currentResult->fetch_assoc();

    // Prepare variables with current values as defaults
    $servType = $_POST['servType'] ?? $currentRow['servType'];
    $description = $_POST['description'] ?? $currentRow['description'];
    $payOpt = $_POST['payOpt'] ?? $currentRow['payOpt'];
    $paymentType = $_POST['paymentType'] ?? $currentRow['paymentType'];
    $rate = $_POST['rate'] ?? $currentRow['rate'];
    $payable = $_POST['payable'] ?? $currentRow['payable'];
    $totalAmount = $_POST['totalAmount'] ?? $currentRow['totalAmount'];
    $address = $_POST['address'] ?? $currentRow['address'];
    $contact_number = $_POST['contact_number'] ?? $currentRow['contact_number'];
    $schedule = $_POST['schedule'] ?? $currentRow['schedule'];
    $manpowerId = $_POST['manpowerName'] ?? $currentRow['mpId'];
    $servStatus = $_POST['servStatus'] ?? $currentRow['servStatus'];

    if (strtotime($schedule) <= time()) {
        echo "<script>alert('Error: Schedule date and time must be in the future.');window.location.href = 'staff_serviceRequest.php;</script>";
        exit();
    }

    // Update the customers table (address and contact number)
    $updateCustomersQuery = "UPDATE customers 
                             INNER JOIN users ON customers.userId = users.userId
                             INNER JOIN reqserv ON users.userId = reqserv.userId
                             SET customers.address = ?, customers.contact_number = ?
                             WHERE reqserv.reqserv = ?";
    $stmtCustomers = $con->prepare($updateCustomersQuery);
    $stmtCustomers->bind_param("ssi", $address, $contact_number, $reqserv);
    $stmtCustomers->execute();

    // Update the accept_reqserv table (schedule and manpower ID)
    $updateAcceptReqservQuery = "UPDATE acceptserv2
                                 SET schedule = ?, mpId = ? 
                                 WHERE reqserv = ?";
    $stmtAcceptReqserv = $con->prepare($updateAcceptReqservQuery);
    $stmtAcceptReqserv->bind_param("sii", $schedule, $manpowerId, $reqserv);
    $stmtAcceptReqserv->execute();

    $balance = ($paymentType == 'Partial') ? $payable : 0;

    $updateStaffServQuery = "UPDATE payment
			     INNER JOIN acceptserv2 ON payment.pendservice = acceptserv2.pendservice
			     INNER JOIN reqserv ON acceptserv2.reqserv = reqserv.reqserv
			     SET payment.totalAmount = ?, payment.payable = ?, payment.paymentType = ?, payment.balance = ?
                 WHERE reqserv.reqserv = ?";
    $stmtStaffServ = $con->prepare($updateStaffServQuery);
    $stmtStaffServ->bind_param("iisii", $totalAmount, $payable, $paymentType, $balance, $reqserv);
    $stmtStaffServ->execute();

    // Assuming staffId is coming from a POST request
    if (isset($_POST['staffId'])) {
        $staffId = $_POST['staffId'];
        
        // Verify that staffId exists in the staffs table before updating
        $checkStaffQuery = "SELECT staffId FROM staffs WHERE staffId = ?";
        $stmtCheckStaff = $con->prepare($checkStaffQuery);
        $stmtCheckStaff->bind_param("i", $staffId);
        $stmtCheckStaff->execute();
        $staffResult = $stmtCheckStaff->get_result();
    }

    // Update reqserv table
    $updateReqservQuery = "UPDATE reqserv 
                           SET servType = ?, description = ?, payOpt = ?, paymentType = ?, rate = ?, payable = ?, totalAmount = ?, servStatus = ? 
                           WHERE reqserv = ?";
    $stmtReqserv = $con->prepare($updateReqservQuery);
    $stmtReqserv->bind_param("ssssdddsi", $servType, $description, $payOpt, $paymentType, $rate, $payable, $totalAmount, $servStatus, $reqserv);

    if ($stmtReqserv->execute()) {
        echo "<script>alert('Record updated successfully!');</script>";
    } else {
        echo "<div class='alert alert-danger'>Error updating record.</div>";
    }

    // Close statements
    $stmtCustomers->close();
    $stmtAcceptReqserv->close();
    $stmtReqserv->close();
    $stmtStaffServ->close();

    // Fetch current manpower ID
    $currentQuery = "SELECT mpId FROM acceptserv2 WHERE reqserv = ?";
    $stmtCurrent = $con->prepare($currentQuery);
    $stmtCurrent->bind_param("i", $reqserv);
    $stmtCurrent->execute();
    $currentResult = $stmtCurrent->get_result();

    if ($currentResult && $currentResult->num_rows > 0) {
        $currentRow = $currentResult->fetch_assoc();
        $currentManpowerId = $currentRow['mpId'];
        $newManpowerId = $_POST['manpowerName'];

        if ($newManpowerId !== $currentManpowerId) {
            $updateOldManpowerQuery = "UPDATE manpower SET mpStatus = 'Available' WHERE mpId = ?";
            $stmtOldManpower = $con->prepare($updateOldManpowerQuery);
            $stmtOldManpower->bind_param("i", $currentManpowerId);
            if (!$stmtOldManpower->execute()) {
                echo "<div class='alert alert-danger'>Error updating old manpower status.</div>";
            }
            $stmtOldManpower->close();

            $updateNewManpowerQuery = "UPDATE manpower SET mpStatus = 'Not Available' WHERE mpId = ?";
            $stmtNewManpower = $con->prepare($updateNewManpowerQuery);
            $stmtNewManpower->bind_param("i", $newManpowerId);
            if (!$stmtNewManpower->execute()) {
                echo "<div class='alert alert-danger'>Error updating new manpower status.</div>";
            }
            $stmtNewManpower->close();

            $updateAcceptReqservQuery = "UPDATE acceptserv2  SET mpId = ? WHERE reqserv = ?";
            $stmtAcceptReqserv = $con->prepare($updateAcceptReqservQuery);
            $stmtAcceptReqserv->bind_param("ii", $newManpowerId, $reqserv);
            if (!$stmtAcceptReqserv->execute()) {
                echo "<div class='alert alert-danger'>Error updating manpower in accept_reqserv.</div>";
            }
            $stmtAcceptReqserv->close();
        }
    }
    $stmtCurrent->close();

    if(isset($manpowerId)){
        if(stripos($servStatus, 'completed') !== false){
            $updateManpowerStatus = "UPDATE manpower SET mpStatus = 'Available' WHERE mpId = ?";
            $stmtUpdateManpower = $con->prepare($updateManpowerStatus);
            $stmtUpdateManpower->bind_param("i", $manpowerId);
            $stmtUpdateManpower->execute();
            $stmtUpdateManpower->close();
        }else{
            $updateManpowerStatus = "UPDATE manpower SET mpStatus = 'Not Available' WHERE mpId = ?";
            $stmtUpdateManpower = $con->prepare($updateManpowerStatus);
            $stmtUpdateManpower->bind_param("i", $manpowerId);
            $stmtUpdateManpower->execute();
            $stmtUpdateManpower->close();
        }
    }else{
        echo "Error: mpId is not set.";
    }
}

// Fetch data to display in form
if (isset($_GET['reqserv'])) {
    $reqserv = $_GET['reqserv'];

    // Fetch service request details
    $query = "SELECT  
                                    users.fullName AS customerName, 
                                    CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                    manpower.fullName AS manpowerName,
                                    reqserv.*, 
                                    acceptserv2.schedule, 
                                    acceptserv2.acceptedDate,
                                    staffs.staffId,
                                    manpower.mpId,
                                    customers.address,
                                    customers.contact_number,
                                    payment.paymentStatus
                                FROM reqserv
                                INNER JOIN users ON reqserv.userId = users.userId
                                INNER JOIN customers ON users.userId = customers.userId
                                INNER JOIN acceptserv2 ON reqserv.reqserv = acceptserv2.reqserv
                                INNER JOIN staffs ON acceptserv2.staffId = staffs.staffId
                                INNER JOIN manpower ON acceptserv2.mpId = manpower.mpId
                                 LEFT JOIN payment ON acceptserv2.pendservice = payment.pendservice
                                WHERE reqserv.reqserv = ? 
                                ORDER BY reqserv.reqserv DESC 
                                LIMIT 1";

    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $reqserv);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Fetch manpower names with conditions
        $manpowerQuery = "SELECT mpId, fullName FROM manpower WHERE mpStatus = 'available' AND mpArchive = 0";
        $manpowerResult = $con->query($manpowerQuery);

        // Fetch service types and rates
        $servicesQuery = "SELECT servName, rateService FROM services";
        $servicesResult = $con->query($servicesQuery);
    }
    $fetch_service_query = "SELECT 
    acceptserv2.pendservice, 
    CONCAT(customers.firstname, ' ', customers.middlename, ' ', customers.lastname) AS fullName,
    customers.custId,
    reqserv.servStatus AS status
    FROM reqserv
    INNER JOIN acceptserv2 ON reqserv.reqserv = acceptserv2.reqserv
    INNER JOIN customers ON reqserv.userId = customers.userId
    WHERE reqserv.reqserv = ?";
    $fetch_service_stmt = $con->prepare($fetch_service_query);
    $fetch_service_stmt->bind_param("i", $reqserv);
    $fetch_service_stmt->execute();
    $fetch_service_stmt->bind_result($pendservice, $fullName, $custId, $status);
    $fetch_service_stmt->fetch();
    $fetch_service_stmt->close();

    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'The status of your Service No.' .$pendservice. ' is now ' . $status; 
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">                    
                        <div class="card-body mb-3">
                            <form method="POST" action="">
                                <h3 class="text-center pb-2">SERVICE INFORMATION</h3>

                                <input type="hidden" name="reqserv" value="<?php echo $reqserv; ?>">

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Customer Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['customerName']); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Address:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="address" value="<?php echo htmlspecialchars($row['address']); ?>">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Contact Number:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="contact_number" value="<?php echo htmlspecialchars($row['contact_number']); ?>">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Assigned Staff:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['staffName']); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                <label for="manpowerName" class="col col-form-label">Assigned Manpower:</label>
                                <div class="col">
                                    <select class="form-control" name="manpowerName" id="manpowerName" onchange="updateManpowerStatus(this)">
                                        <option value="<?php echo $row['mpId']; ?>"><?php echo $row['manpowerName']; ?></option>
                                        <?php if ($manpowerResult): ?>
                                            <?php while ($manpower = $manpowerResult->fetch_assoc()): ?>
                                                <option value="<?php echo $manpower['mpId']; ?>"><?php echo $manpower['fullName']; ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                                <div class="mb-3 row">
                                    <label class="col col-form-label">Service Type:</label>
                                    <div class="col">
                                        <select class="form-control" name="servType" onchange="updateRate(this)" disabled readonly>
                                            <option value="">Select Service Type</option>
                                            <?php while ($serviceRow = $servicesResult->fetch_assoc()) { ?>
                                                <option value="<?php echo htmlspecialchars($serviceRow['servName']); ?>" 
                                                    data-rate="<?php echo htmlspecialchars($serviceRow['rateService']); ?>" 
                                                    <?php echo $row['servType'] == htmlspecialchars($serviceRow['servName']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($serviceRow['servName']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticEmail" class="col col-form-label">Urgent:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['urgent']); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Description:</label>
                                    <div class="col">
                                        <textarea class="form-control" name="description"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                    </div>
                                </div>
                                                
                                <div class="mb-3 row">
                                    <label class="col col-form-label">Payment Type:</label>
                                    <div class="col">
                                        <select class="form-control" name="paymentType" id="paymentType" onchange="updatePayableAndTotalAmount()">
                                            <option value="Full" <?php echo $row['paymentType'] == 'Full' ? 'selected' : ''; ?>>Full</option>
                                            <option value="Partial" <?php echo $row['paymentType'] == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Payable:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="payable" id="payable" value="<?php echo htmlspecialchars($row['payable']); ?>" readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Total Amount:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="totalAmount" id="totalAmount" value="<?php echo htmlspecialchars($row['totalAmount']); ?>" readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Schedule:</label>
                                    <div class="col">
                                        <input class="form-control" type="datetime-local" name="schedule" id="schedule" 
                                            value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($row['schedule']))); ?>">
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                <?php
                                    if (strpos($row['paymentStatus'], 'check') !==false || strtolower($row['paymentStatus']) == 'to be paid') {
                                ?>
                                <label class="col col-form-label">Status:</label>
                                <div class="col">
                                    <select class="form-select" name="servStatus" disabled>
                                        <option value="<?php echo $row['servStatus']; ?>" selected><?php echo $row['servStatus']; ?></option>
                                        <option value="Pending Request" <?php if ($row['servStatus'] == "Pending Request") echo 'disabled'; ?>>Pending Request</option>
                                        <option value="Request Declined" <?php if ($row['servStatus'] == "Request Declined") echo 'disabled'; ?>>Request Declined</option>
                                        <option value="Accepted and Awaiting Schedule" <?php if ($row['servStatus'] == "Accepted and Awaiting Schedule") echo 'disabled'; ?>>Accepted and Awaiting Schedule</option>
                                        <option value="Pending Service" <?php if ($row['servStatus'] == "Pending Service") echo 'disabled'; ?>>Pending Service</option>
                                        <option value="Service In-Progress" <?php if ($row['servStatus'] == "Service In-Progress") echo 'disabled'; ?>>Service In-Progress</option>
                                        <option value="Service Completed" <?php if ($row['servStatus'] == "Service Completed") echo 'disabled'; ?>>Service Completed</option>
                                    </select>
                                </div>
                                <?php 
                                    } else { 
                                ?>
                                <label class="col col-form-label">Status:</label>
                                <div class="col">
                                    <select class="form-select" name="servStatus">
                                        <option value="<?php echo $row['servStatus']; ?>" selected><?php echo $row['servStatus']; ?></option>
                                        <option value="Pending Request" <?php if ($row['servStatus'] == "Pending Request") echo 'disabled'; ?>>Pending Request</option>
                                        <option value="Request Declined" <?php if ($row['servStatus'] == "Request Declined") echo 'disabled'; ?>>Request Declined</option>
                                        <option value="Accepted and Awaiting Schedule" <?php if ($row['servStatus'] == "Accepted and Awaiting Schedule") echo 'disabled'; ?>>Accepted and Awaiting Schedule</option>
                                        <option value="Pending Service" <?php if ($row['servStatus'] == "Pending Service") echo 'disabled'; ?>>Pending Service</option>
                                        <option value="Service In-Progress" <?php if ($row['servStatus'] == "Service In-Progress") echo 'disabled'; ?>>Service In-Progress</option>
                                        <option value="Service Completed" <?php if ($row['servStatus'] == "Service Completed") echo 'disabled'; ?>>Service Completed</option>
                                    </select>
                                </div>
                                <?php 
                                    }
                                ?>
                            </div>

                                <div class="text-center">
                                    <button class="btn btn-primary" type="submit" name="update">Update</button>
                                    <a href="staff_serviceRequest.php" class="btn btn-secondary">Back</a>
                                </div>
                            </form>

                            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

                            <script>
                                    // Set the minimum date and time for the schedule input
                                    document.addEventListener('DOMContentLoaded', function () {
                                        const scheduleInput = document.getElementById('schedule');
                                        const now = new Date();

                                        // Format the current date and time to match the 'datetime-local' input
                                        const formattedNow = now.toISOString().slice(0, 16);
                                        scheduleInput.min = formattedNow;
                                    });
                                var currentManpowerId = "<?php echo $row['mpId']; ?>"; // Get the current manpower ID
                                    
                                function updateManpowerStatus(selectElement) {
                                    var newManpowerId = selectElement.value; // Get the newly selected manpower ID

                                    if (newManpowerId !== currentManpowerId) {
                                        // AJAX request to update the current manpower status to 'Available'
                                        $.ajax({
                                            type: "POST",
                                            url: "update_manpower_status.php", // URL of the PHP script to handle status updates
                                            data: { mpId: currentManpowerId, status: 'Available' },
                                            success: function(response) {
                                                console.log("Current manpower status updated to Available.");
                                            },
                                            error: function() {
                                                console.error("Error updating current manpower status.");
                                            }
                                        });

                                        // AJAX request to update the new manpower status to 'Not Available'
                                        $.ajax({
                                            type: "POST",
                                            url: "update_manpower_status.php",
                                            data: { mpId: newManpowerId, status: 'Not Available' },
                                            success: function(response) {
                                                console.log("New manpower status updated to Not Available.");
                                            },
                                            error: function() {
                                                console.error("Error updating new manpower status.");
                                            }
                                        });
                                    }
                                }
                                // Function to update the rate when service type changes
                                function updateRate(selectElement) {
                                    var rate = selectElement.options[selectElement.selectedIndex].getAttribute('data-rate');
                                    document.getElementById('rate').value = rate;
                                    updatePayableAndTotalAmount(); // Update amounts when rate changes
                                }

                                // Function to update payable and total amounts based on payment type
                                function updatePayableAndTotalAmount() {
                                    var rate = parseFloat(document.getElementById('rate').value) || 0;
                                    var paymentType = document.getElementById('paymentType').value;
                                    var payableAmount = 0;

                                    if (paymentType === 'Full') {
                                        payableAmount = rate;
                                    } else if (paymentType === 'Partial') {
                                        payableAmount = rate * 0.5; // Assuming partial payment is 50% of the rate
                                    }

                                    document.getElementById('payable').value = payableAmount.toFixed(2);
                                    document.getElementById('totalAmount').value = rate.toFixed(2);
                                }

                                function validateForm() {
                                const contactNumber = document.getElementById('contact_number').value;
                                const contactError = document.getElementById('contactError');

                                if (!/^09\d{9}$/.test(contactNumber)) {
                                    contactError.style.display = 'block';
                                    return false; // Prevent form submission
                                } else {
                                    contactError.style.display = 'none'; // Hide error if valid
                                    return true; // Allow form submission
                                }
                            }
                             </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
