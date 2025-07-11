<?php
$page_title = "Admin Accept Information";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_GET['pdngReqsNo'])) {
    $pdngReqsNo = $_GET['pdngReqsNo'];
    if(isset($_GET['reqserv'])){
        $reqserv = $_GET['reqserv'];

        // Fetch the pending request details, including necessary values
        $sql = "SELECT * FROM pending_reqserv WHERE pdngReqsNo = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $pdngReqsNo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userId = $row['userId'];  // Assign userId for later use

            // Retrieve additional values needed for the insert
            $servType = $row['servType']; // Assuming serviceType is in pending_reqserv
            $prodName = $row['prodName']; // Assuming product is in pending_reqserv
            $quantity = $row['quantity']; // Assuming quantity is in pending_reqserv
            $payOpt = $row['payOpt']; // Assuming payOpt is in pending_reqserv
            $payable = $row['payable']; // Assuming payable is in pending_reqserv
            $totalAmount = $row['totalAmount']; // Assuming totalAmount is in pending_reqserv

        } else {
            echo "<p>Request not found.</p>";
            exit();
        }
    } else {
        echo "<p>No service type provided.</p>";
        exit();
    }
} else {
    echo "<p>No request ID or service type provided.</p>";
    exit();
}

if (isset($_POST['accept_button'])) {
    $staffId = $_POST['staffId'];
    $acceptedDate = date('Y-m-d H:i:s'); // Set the current date and time

    // Inserting the accepted request
    $accept_query = "INSERT INTO acceptserv1 (staffId, reqserv, userId, servType, prodName, quantity, payOpt, payable, totalAmount, acceptedDate) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $accept_stmt = $con->prepare($accept_query);
    $accept_stmt->bind_param("iiissisdds", $staffId, $reqserv, $userId, $servType, $prodName, $quantity, $payOpt, $payable, $totalAmount, $acceptedDate);

    if ($accept_stmt->execute()) {
        // Proceed to delete the request
        $delete_query = "DELETE FROM pending_reqserv WHERE pdngReqsNo = ?";
        $delete_stmt = $con->prepare($delete_query);
        $delete_stmt->bind_param('i', $pdngReqsNo);
        if ($delete_stmt->execute()) {
            echo "<script>alert('Request successfully accepted and removed.')</script>";
            echo '<script>window.location="admin_pendingService.php"</script>';
            exit();
        } else {
            echo "<script>alert('Error removing request from pending.')</script>";
        }
        $delete_stmt->close();
    } else {
        echo "<script>alert('Failed to accept the request.')</script>";
    }
    $accept_stmt->close();
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
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h5>Accept Service Request</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3 row">
                                <label for="">Service Request Number:</label>
                                <div class="col">
                                    <input class="form-control" type="text" value="<?php echo htmlspecialchars($pdngReqsNo); ?>" disabled readonly>
                                </div>
                            </div>

                            <!-- Dropdown for assigning staff -->
                            <div class="form-group">
                                <label for="staffSelect">Assign Staff</label>
                                <select class="form-control mb-3" id="staffSelect" name="staffId" required>
                                    <option value="" disabled selected>Select Staff</option>
                                    <?php
                                    // Fetch available staff from the database
                                    $staff_query = "SELECT staffId, firstname, middlename, lastname FROM staffs";
                                    $staff_result = $con->query($staff_query);

                                    if ($staff_result) {
                                        if ($staff_result->num_rows > 0) {
                                            while ($staff_row = $staff_result->fetch_assoc()) {
                                                // Concatenate staff full name
                                                $staffName = trim($staff_row['firstname']  . ' ' . $staff_row['lastname']);
                                                // Display staffId along with the staffName
                                                echo "<option value='{$staff_row['staffId']}'> {$staff_row['staffId']} - {$staffName}</option>";
                                            }
                                        } else {
                                            echo "<option value='' disabled>No staff available</option>";
                                        }
                                    } else {
                                        echo "<p>Error fetching staff: " . $con->error . "</p>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group d-flex justify-content-between">
                                <a href="admin_pendingService.php">
                                    <button type="button" class="btn btn-secondary">
                                        <i class="bi bi-arrow-90deg-left"></i> Back
                                    </button>
                                </a>
                                <button type="submit" name="accept_button" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
