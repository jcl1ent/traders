<?php
$page_title = "Admin User Accounts";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
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
            <div class="col-md-6 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">                    
                        <div class="card-body mb-3">
                        <?php
                            if (isset($_GET['pendservice'])) {
                                $reqserv = $_GET['pendservice'];

                                // SQL query to fetch a specific accepted service request
                                $query = "
                                    SELECT  
                                        users.fullName AS customerName, 
                                        CONCAT(staffs.firstname, ' ', staffs.lastname) AS staffName, 
                                        reqserv.*,
                                        acceptserv2.*,
                                        customers.address,
                                        customers.contact_number
                                    FROM acceptserv2
                                    INNER JOIN users ON acceptserv2.userId = users.userId
                                    INNER JOIN staffs ON acceptserv2.staffId = staffs.staffId
                                    INNER JOIN reqserv ON acceptserv2.reqserv = reqserv.reqserv
                                    INNER JOIN customers ON users.userId = customers.userId
                                    WHERE acceptserv2.pendservice = ? 
                                    LIMIT 1
                                ";
                                // Prepare the statement
                                $stmt = $con->prepare($query);
                                $stmt->bind_param("i", $reqserv); // Bind acceptedId as integer
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                // Check if a record was found
                                if ($result && $result->num_rows > 0) {
                                    // Fetch the data
                                    $row = $result->fetch_assoc();
                        ?> 
                            <form>
                                <h3 class="text-center pb-2">REQUEST SERVICE INFORMATION</h3>
                                
                                <div class="mb-3 row">
                                    <label for="staticCustId" class="col col-form-label">Customer Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['customerName']); ?>" disabled readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col col-form-label">Address:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="address" value="<?php echo htmlspecialchars($row['address']); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Contact Number:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="contact_number" value="<?php echo htmlspecialchars($row['contact_number']); ?>" disabled readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="staticCustId" class="col col-form-label">Staff Assigned:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['staffName']); ?>" disabled readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticFullName" class="col col-form-label">Service Number:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['pendservice']); ?>" disabled readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticEmail" class="col col-form-label">Service Type:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['servType']); ?>" disabled readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Description:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['description']); ?>" disabled readonly>                                                    
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Payment Option:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['payOpt']); ?>" disabled readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Payment Type:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['paymentType']); ?>" disabled readonly>                                                    
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Urgent:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['urgent']); ?>" disabled readonly>                                                    
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Payable:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['payable']); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Total Amount:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['totalAmount']); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Request Date:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['createDate']); ?>" disabled readonly>                                                    
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Accepted Date:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['acceptedDate']); ?>" disabled readonly>
                                    </div>
                                </div>
                                
                                <a href="admin_unassigned.php">
                                    <button type="button" class="btn btn-secondary">
                                        <i class="bi bi-arrow-90deg-left"></i> Back 
                                    </button>
                                </a>
                            </form>
                        <?php
                                } // End of if to check for a record
                                else {
                                    echo "<div class='alert alert-danger'>No records found.</div>";
                                }
                                $stmt->close(); // Close the prepared statement
                            } else {
                                echo "<div class='alert alert-warning'>No accepted ID provided.</div>";
                            }
                        ?>
                        </div> 
                    </div>
                </div> 
            </div> 
        </div> 
    </div> 
</div> 
</body>
</html>