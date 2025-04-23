<?php
$page_title = "Admin Service Information";
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
                                    if (isset($_GET['reqserv'])) {
                                        $reqserv = $_GET['reqserv'];
                                        
                                        // Fetch the service request based on the provided pdngReqsNo
                                        $query = "
                                            SELECT users.fullName, users.userId, reqserv.*,
                                            customers.address,
                                            customers.contact_number
                                            FROM users
                                            INNER JOIN reqserv ON users.userId = reqserv.userId
                                            INNER JOIN customers ON users.userId = customers.userId
                                            WHERE reqserv.reqserv = ?
                                        ";
                                        $stmt = $con->prepare($query);
                                        $stmt->bind_param("i", $reqserv); // Bind pdngReqsNo as string
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        if ($result->num_rows > 0) {
                                            $row = $result->fetch_assoc();

                                    ?>    
                                        <form>                                        
                                            <h3 class="text-center pb-2">REQUEST SERVICE INFORMATION</h3>
                                            <div class="mb-3 row">
                                                <label for="staticCustId" class="col col-form-label">Customer Name:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['fullName'] ?>"  disabled readonly>                                                    
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
                                                <label for="staticCustId" class="col col-form-label">Service Request Number:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['reqserv'] ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticEmail" class="col col-form-label">Service Type:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['servType']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Description:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['description']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Payment Option:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['payOpt']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Payment Type:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['paymentType']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Urgent:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['urgent']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Payable:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['payable']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Total Amount:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['totalAmount']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Create Date:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['createDate']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <a href="admin_pendingService.php">
                                                <button type="button" class="btn btn-secondary">
                                                    <i class="bi bi-arrow-90deg-left"></i> Back 
                                                </button>
                                            </a>
                                        </form>
                                        <?php
                                            } else {
                                                echo "Service Request Information not found.";
                                            }
                                            $stmt->close();
                                        } else {
                                            echo "No Service Request Number provided.";
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

