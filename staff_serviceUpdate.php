<?php
$page_title = "Staff Service Requests";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['update'])) {
    // Fetch the current values first
    $reqserv = $_POST['reqserv'];

    $contact_number = $_POST['contact_number'];
    if (!preg_match("/^09\d{9}$/", $contact_number)) {
        echo "<script>
                alert('Invalid contact number. It must start with 09 and be 11 digits long.');
                window.location.href = 'staff_serviceRequest.php';
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

    // Prepare variables
    $servType = $_POST['servType'] ?? $currentRow['servType'];
    $description = $_POST['description'] ?? $currentRow['description'];
    $payOpt = $_POST['payOpt'] ?? $currentRow['payOpt'];
    $paymentType = $_POST['paymentType'] ?? $currentRow['paymentType'];
    $rate = $_POST['rate'] ?? $currentRow['rate'];
    $payable = $_POST['payable'] ?? $currentRow['payable'];
    $totalAmount = $_POST['totalAmount'] ?? $currentRow['totalAmount'];
    $address = $_POST['address'] ?? $currentRow['address'];
    $contact_number = $_POST['contact_number'] ?? $currentRow['contact_number'];
    $servStatus = $_POST['servStatus'] ?? $currentRow['servStatus'];

    // Update the customers table (address and contact number)
    $updateCustomersQuery = "UPDATE customers 
                             INNER JOIN users ON customers.userId = users.userId
                             INNER JOIN reqserv ON users.userId = reqserv.userId
                             SET customers.address = ?, customers.contact_number = ?
                             WHERE reqserv.reqserv = ?";
    $stmtCustomers = $con->prepare($updateCustomersQuery);
    $stmtCustomers->bind_param("ssi", $address, $contact_number, $reqserv);
    $stmtCustomers->execute();

    $balance = ($paymentType == 'Partial') ? $payable : 0;

    $updateStaffServQuery = "UPDATE payment
			     INNER JOIN acceptserv2 ON payment.pendservice = acceptserv2.pendservice
			     INNER JOIN reqserv ON acceptserv2.reqserv = reqserv.reqserv
			     SET payment.totalAmount = ?, payment.payable = ?, payment.paymentType = ?, payment.balance = ?
                 WHERE reqserv.reqserv = ?";
    $stmtStaffServ = $con->prepare($updateStaffServQuery);
    $stmtStaffServ->bind_param("iisii", $totalAmount, $payable, $paymentType, $balance, $reqserv);
    $stmtStaffServ->execute();

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
    $stmtReqserv->close();
    $stmtStaffServ->close();
}
// Fetch data to display in form
if (isset($_GET['reqserv'])) {
    $reqserv = $_GET['reqserv'];

    // Fetch service request details
    $query = "SELECT  
                users.fullName AS customerName,
                CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName, 
                reqserv.*, 
                customers.address,
                customers.contact_number,
                payment.paymentStatus
              FROM reqserv
              INNER JOIN users ON reqserv.userId = users.userId
              INNER JOIN customers ON users.userId = customers.userId
              INNER JOIN acceptserv2 ON reqserv.reqserv = acceptserv2.reqserv
              INNER JOIN staffs ON acceptserv2.staffId = staffs.staffId
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

        // Fetch service types and rates
        $servicesQuery = "SELECT servName, rateService FROM services";
        $servicesResult = $con->query($servicesQuery);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="text-center">SERVICE INFORMATION</h3>
                    </div>
                    <div class="card-body mb-3">
                        <form method="post">
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
                                    <input class="form-control" type="text" name="address" value="<?php echo htmlspecialchars($row['address']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col col-form-label">Contact Number:</label>
                                <div class="col">
                                    <input class="form-control" type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($row['contact_number']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col col-form-label">Assigned Staff:</label>
                                <div class="col">
                                    <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['staffName']); ?>" disabled readonly>
                                </div>
                            </div>
                                <div class="mb-3 row">
                                    <label for="staticEmail" class="col col-form-label">Service Type:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['servType']); ?>" disabled readonly>
                                    </div>
                                </div>
                            <div class="mb-3 row">
                                <label class="col col-form-label">Urgent:</label>
                                <div class="col">
                                    <input class="form-control" type="text" name="rate" id="rate" value="<?php echo htmlspecialchars($row['urgent']); ?>" disabled readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col col-form-label">Description:</label>
                                <div class="col">
                                    <textarea class="form-control" name="description" ><?php echo htmlspecialchars($row['description']); ?></textarea>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col col-form-label">Payment Type:</label>
                                <div class="col">
                                    <select class="form-control" name="paymentType" id="paymentType" onchange="updatePayableAndTotalAmount()" >
                                        <option value="Full" <?php echo $row['paymentType'] == 'Full' ? 'selected' : ''; ?>>Full</option>
                                        <option value="Partial" <?php echo $row['paymentType'] == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col col-form-label">Payable:</label>
                                <div class="col">
                                    <input class="form-control" type="text" name="payable" id="payable" value="<?php echo htmlspecialchars($row['payable']); ?>" disabled readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col col-form-label">Total Amount:</label>
                                <div class="col">
                                    <input class="form-control" type="text" name="totalAmount" id="totalAmount" value="<?php echo htmlspecialchars($row['totalAmount']); ?>" disabled readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <?php
                                    if (strtolower($row['paymentStatus']) != 'to be checked' || strtolower($row['paymentStatus']) != 'to be paid') {
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Function to update payable and totalAmount based on paymentType
    function updatePayableAndTotalAmount() {
        // Get total amount from the backend PHP (already displayed in the input field)
        const totalAmount = parseFloat(document.getElementById('totalAmount').value) || 0;
        const paymentType = document.getElementById('paymentType').value;
        let payable = totalAmount;

        // If Partial payment is selected, calculate payable as half of the totalAmount
        if (paymentType === 'Partial') {
            payable = totalAmount / 2;
        } else if (paymentType === 'Full') {
            payable = totalAmount; // Full payment is the same as totalAmount
        }

        // Update the payable input field with the calculated value
        document.getElementById('payable').value = payable.toFixed(2);
    }

    // Initialize the form with the correct values
    document.addEventListener('DOMContentLoaded', function() {
        updatePayableAndTotalAmount(); // Ensure initial calculation is correct
    });
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
</body>
</html>