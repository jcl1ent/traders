<?php
include("logincode.php"); 
$page_title = "View Service";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");
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
                                        customers.address,
                                        customers.contact_number,
                                        users.fullName AS customerName,
                                        CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                        acceptserv2.pendservice,
                                        acceptserv2.acceptedDate,
                                        manpower.fullName AS manpowerName,
                                        acceptserv2.schedule,
                                        reqserv.*
                                    FROM acceptserv2
                                    LEFT JOIN users ON acceptserv2.userId = users.userId
                                    LEFT JOIN reqserv ON acceptserv2.reqserv = reqserv.reqserv
                                    LEFT JOIN manpower ON acceptserv2.mpId = manpower.mpId
                                    LEFT JOIN staffs ON acceptserv2.staffId = staffs.staffId
                                    LEFT JOIN customers ON users.userId = customers.userId
                                            WHERE acceptserv2.pendservice = ? 
                                            ORDER BY acceptserv2.pendservice DESC
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
                                <h3 class="text-center pb-2">SERVICE INFORMATION</h3>
                                
                                <div class="mb-3 row">
                                    <label for="staticCustId" class="col col-form-label">Customer Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['customerName'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col col-form-label">Address:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="address" value="<?php echo htmlspecialchars($row['address'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col col-form-label">Contact Number:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="contact_number" value="<?php echo htmlspecialchars($row['contact_number'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="staticCustId" class="col col-form-label">Staff Assigned:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['staffName'] ?? 'None') ?>" disabled readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="staticCustId" class="col col-form-label">Manpower Assigned:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['manpowerName'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="staticFullName" class="col col-form-label">Service Number:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['pendservice'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticEmail" class="col col-form-label">Service Type:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['servType'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>
                            
                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Description:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['description'] ?? 'None'); ?>" disabled readonly>                                                    
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Payment Option:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['payOpt'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Payment Type:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['paymentType'] ?? 'None'); ?>" disabled readonly>                                                    
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Urgent:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['urgent'] ?? 'None'); ?>" disabled readonly>                                                    
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Payable:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['payable'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Total Amount:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['totalAmount'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Request Date:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['createDate'] ?? 'None'); ?>" disabled readonly>                                                    
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="staticCreateDate" class="col col-form-label">Schedule:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" value="<?php echo htmlspecialchars($row['schedule'] ?? 'None'); ?>" disabled readonly>
                                    </div>
                                </div>
                                <a href="vserviceAcc_customer.php">
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