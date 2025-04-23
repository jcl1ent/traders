<?php
$page_title = "Admin Service Information";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['update'])) {
    $reqserv = $_POST['reqserv'];
    $contact_number = $_POST['contact_number'];

    if (!preg_match("/^09\d{9}$/", $contact_number)) {
        echo "<script>
                alert('Invalid contact number. It must start with 09 and be 11 digits long.');
                window.location.href = 'admin_service.php';
              </script>";
        exit();
    }

    $currentQuery = "SELECT * FROM reqserv WHERE reqserv = ?";
    $stmtCurrent = $con->prepare($currentQuery);
    $stmtCurrent->bind_param("i", $reqserv);
    $stmtCurrent->execute();
    $currentResult = $stmtCurrent->get_result();
    $currentRow = $currentResult->fetch_assoc();

    $servType = $_POST['servType'] ?? $currentRow['servType'];
    $description = $_POST['description'] ?? $currentRow['description'];
    $payOpt = $_POST['payOpt'] ?? $currentRow['payOpt'];
    $paymentType = $_POST['paymentType'] ?? $currentRow['paymentType'];
    $urgent = $_POST['urgent'] ?? $currentRow['urgent'];
    $payable = $_POST['payable'] ?? $currentRow['payable'];
    $totalAmount = $_POST['totalAmount'] ?? $currentRow['totalAmount'];
    $address = $_POST['address'] ?? $currentRow['address'];
    $servStatus = $_POST['servStatus'] ?? $currentRow['servStatus'];

    $updateCustomersQuery = "UPDATE customers 
                             INNER JOIN users ON customers.userId = users.userId
                             INNER JOIN reqserv ON users.userId = reqserv.userId
                             SET customers.address = ?, customers.contact_number = ?
                             WHERE reqserv.reqserv = ?";
    $stmtCustomers = $con->prepare($updateCustomersQuery);
    $stmtCustomers->bind_param("ssi", $address, $contact_number, $reqserv);
    $stmtCustomers->execute();

    $updateReqservQuery = "UPDATE reqserv 
                           SET servType = ?, description = ?, payOpt = ?, paymentType = ?, urgent = ?, payable = ?, totalAmount = ?, servStatus = ? 
                           WHERE reqserv = ?";
    $stmtReqserv = $con->prepare($updateReqservQuery);
    $stmtReqserv->bind_param("sssssddsi", $servType, $description, $payOpt, $paymentType, $urgent, $payable, $totalAmount, $servStatus, $reqserv);

    if ($stmtReqserv->execute()) {
        echo "<script>alert('Record updated successfully!');</script>";
    } else {
        echo "<div class='alert alert-danger'>Error updating record.</div>";
    }

    $stmtCustomers->close();
    $stmtReqserv->close();

}

if (isset($_GET['reqserv'])) {
    $reqserv = $_GET['reqserv'];
    $query = "SELECT  
                users.fullName AS customerName, 
                reqserv.*, 
                customers.address,
                customers.contact_number
              FROM reqserv
              INNER JOIN users ON reqserv.userId = users.userId
              INNER JOIN customers ON users.userId = customers.userId
              WHERE reqserv.reqserv = ? 
              ORDER BY reqserv.reqserv DESC 
              LIMIT 1";

    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $reqserv);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

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
                                <h3 class="text-center pb-2">REQUEST SERVICE INFORMATION</h3>

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
                                <label class="col col-form-label">Urgent:</label>
                                <div class="col">
                                    <!-- <input class="form-control" type="text" name="rate" id="rate" value="<?php echo htmlspecialchars($row['urgent']); ?>"> -->
                                    <select class="form-select" name="urgent">
                                        <option value="<?php echo $row['urgent']; ?>" selected><?php echo $row['urgent']; ?></option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Description:</label>
                                    <div class="col">
                                        <textarea class="form-control" name="description"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                        <label class="col col-form-label">Payment Method</label>
                        <div class="col">
                        <select class="form-select" name="payOpt">
                        <option value="<?php echo $row['payOpt']; ?>" selected><?php echo $row['payOpt']; ?></option>
                            <option value="Check">Check</option>
                            <option value="Cash on Delivery">Cash on Delivery</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
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
</div>
                                <div class="text-center">
                                    <button class="btn btn-primary" type="submit" name="update">Update</button>
                                    <a href="admin_service.php" class="btn btn-secondary">Back</a>
                                </div>
                            </form>

                            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>