<?php
include("logincode.php");
$page_title = "Payments";
include("sidebar.php");
include("includes/header.php");
include("dbcon.php");

if (isset($_POST['submitReceipt'])) {
    // Check if files were uploaded and if the `pymntNo` is provided.

    $refCode = $_POST['refCode'];

    if (isset($_POST['pymntNo']) && isset($_FILES['receipts'])) {
        $pymntNo = $_POST['pymntNo']; 
        $receipts = $_FILES['receipts'];
        $uploadedImages = [];
        
        $sql = "SELECT staffId FROM payment";  
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $stmt->bind_result($staffId);
        $stmt->fetch();
        $stmt->close();

        $sql = "SELECT adminId FROM admin LIMIT 1";  
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $stmt->bind_result($adminId);
        $stmt->fetch();
        $stmt->close();

        // Check if the pymntNo exists in the payment table
        $checkQuery = "SELECT pymntNo FROM payment WHERE pymntNo = ?";
        $checkStmt = $con->prepare($checkQuery);
        $checkStmt->bind_param("i", $pymntNo);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            echo "<script>alert('Invalid Payment ID.');</script>";
            exit; // Stop execution if pymntNo is invalid
        }

        // Process each uploaded file (up to 3)
        for ($i = 0; $i < count($receipts['name']) && $i < 3; $i++) {
            if ($receipts['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $receipts['tmp_name'][$i];
                $name = basename($receipts['name'][$i]);
                $uploadDir = "uploads/receipts/";
                $filePath = $uploadDir . uniqid() . "_" . $name;

                // Ensure upload directory exists
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Move file to upload directory
                if (move_uploaded_file($tmp_name, $filePath)) {
                    $uploadedImages[] = $filePath; // Add file path to array
                }
            }
        }

        $log_action_query2 = "INSERT INTO user_action_logs (staffId, action, status) VALUES (?, ?, ?)";
        $action = $fullName . ' submitted its proof of payment of Payment No.' . $pymntNo;
        $status = 'unread';
        $log_action_stmt2 = $con->prepare($log_action_query2);
        $log_action_stmt2->bind_param("iss", $staffId, $action, $status);
        $log_action_stmt2->execute();
        $log_action_stmt2->close();
    
        $log_action_query2 = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
        $action = $fullName . ' submitted its proof of payment of Payment No.' . $pymntNo;
        $status = 'unread';
        $log_action_stmt2 = $con->prepare($log_action_query2);
        $log_action_stmt2->bind_param("iss", $adminId, $action, $status);
        $log_action_stmt2->execute();
        $log_action_stmt2->close();

        // Prepare to insert file paths into payment_images table
        $query = "INSERT INTO payment_images (pymntNo, paymentImg1, paymentImg2, paymentImg3) VALUES (?, ?, ?, ?)";
        $stmt = $con->prepare($query);

        // Make sure to bind null for images if not uploaded
        $paymentImg1 = isset($uploadedImages[0]) ? $uploadedImages[0] : null;
        $paymentImg2 = isset($uploadedImages[1]) ? $uploadedImages[1] : null;
        $paymentImg3 = isset($uploadedImages[2]) ? $uploadedImages[2] : null;

        $stmt->bind_param("isss", $pymntNo, $paymentImg1, $paymentImg2, $paymentImg3);
        if($stmt->execute()){
            echo "<script>alert('Payment receipt uploaded successfully.');</script>";

            $update_query = "UPDATE payment SET  paymentStatus = 'To be checked' WHERE pymntNo = ?";
            $update_stmt = $con->prepare($update_query);
            $update_stmt->bind_param("i", $pymntNo);
            $update_stmt->execute();

            $refCodeInsert = "UPDATE payment SET refCode = ? WHERE pymntNo = ?";
            $stmt = $con->prepare($refCodeInsert);
            $stmt->bind_param("si",$refCode, $pymntNo);
            $stmt->execute();


        };
    } else {
        echo "<script>alert('Please upload at least one image.');</script>";
    }
}
if (isset($_GET['fetch_receipts']) && isset($_GET['pymntNo'])) {
    $pymntNo = $_GET['pymntNo'];

    // Query to fetch images
    $query = "SELECT paymentImg1, paymentImg2, paymentImg3 FROM payment_images WHERE pymntNo = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $pymntNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            for ($i = 1; $i <= 3; $i++) {
                $imageColumn = 'paymentImg' . $i;
                if (!empty($row[$imageColumn])) {
                    echo '<div class="mb-2">
                            <a href="' . $row[$imageColumn] . '" target="_blank" style="display:inline-block; margin-right: 5px;">
                                <img src="' . $row[$imageColumn] . '" alt="Payment Receipt" class="img-thumbnail" style="max-width: 150px; height: auto; cursor: pointer;">
                            </a>
                          </div>';
                }
            }
        }
    } else {
        echo '<p>No receipts found for this payment.</p>';
    }
    exit; // Stop further execution of the script after handling the AJAX request
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<ul class="nav nav-tabs justify-content-end mt-3" id="navTabs">
    <li class="nav-item">
        <a class="nav-link fs-5" href="payments_customer.php">Request Services Payment</a>
    </li>
    <li class="nav-item active">
        <a class="nav-link fs-5" href="paymentsOrder_customer.php">Orders Payment</a>
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
                                        <tr class="text-center">
                                            <th scope="col">Payment Number</th>
                                            <th scope="col">Service Number</th>
                                            <th scope="col">Total Amount</th> 
                                            <th scope="col">Payable</th>                                   
                                            <th scope="col">Balance</th>                                               
                                            <th scope="col">Payment Type</th>  
                                            <th scope="col">Payment Status</th>
                                            <th scope="col">Reference Code</th>
                                            <th scope="col">Action</th>                                      
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (isset($_SESSION['email']) && isset($_SESSION['userId']) && isset($_SESSION['custId'])) {
                                            $userId = $_SESSION['userId'];
                                            $email = $_SESSION['email'];
                                            $custId = $_SESSION['custId'];

                                            // Get payment information for the customer
                                            $query = "SELECT * FROM payment WHERE custId = ? AND pendservice IS NOT NULL
                                                      ORDER BY pymntNo desc";
                                            $stmt = $con->prepare($query);
                                            $stmt->bind_param("i", $custId);  
                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {                                                                
                                                    ?>
                                                    <tr class="text-center">                                                                    
                                                        <td data-label="Payment ID"><?php echo $row['pymntNo']; ?></td>
                                                        <td data-label="Service ID"><?php echo $row['pendservice']; ?></td>
                                                        <td data-label="Total Amount"><?php echo $row['totalAmount']; ?></td>
                                                        <td data-label="Payable"><?php echo $row['payable']; ?></td>
                                                        <td data-label="Balance"><?php echo $row['balance']; ?></td>
                                                        <td data-label="Payment Type"><?php echo $row['paymentType']; ?></td>                                                   
                                                        <td data-label="Status"><?php echo $row['paymentStatus']; ?></td>
                                                        <td data-label="Status"><?php echo $row['refCode']; ?></td>
                                                        <td data-label="Action">
                                                            <div class="actions d-flex justify-content-center" style="gap: 5px;">
                                                                <?php                                    
                                                                if ($row['paymentStatus'] != 'Paid' && $row['paymentStatus'] != 'COD Paid' && $row['paymentStatus'] != 'Partially Paid') { ?>
                                                            <button type="button" class="btn btn-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#paymentModal" data-pymntno="<?php echo $row['pymntNo']; ?>">
                                                              <i class="bi bi-wallet2 me-2"></i>
                                                             <span>Pay</span>
                                                            </button>
                                                            <a href="customer_paymentInfo.php?pymntNo=<?php echo $row['pymntNo']; ?>">
                                                            <button type="button" class="btn btn-warning d-flex align-items-center">
                                                             <i class="bi bi-credit-card me-2"></i>
                                                             <span>Check Payment</span>
                                                            </button>
                                                                    </a>
                                                            <?php }else{?>
                                                                <a href="customer_paymentInfo.php?pymntNo=<?php echo $row['pymntNo']; ?>">
                                                                <button type="button" class="btn btn-warning d-flex align-items-center">
                                                             <i class="bi bi-credit-card me-2"></i>
                                                             <span>Check Payment</span>
                                                            </button>
                                                                    </a>
                                                            <a href="customer_paymentService_print.php?pymntNo=<?php echo $row['pymntNo']; ?>">
                                                            <button type="button" class="btn btn-primary d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-text me-2"></i>
                                                            <span>View Receipt</span>
                                                            </button>
                                                            <?php }?>
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
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Company Bank Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please use the following bank details to complete your payment:</p>
                <ul>
                    <li><strong>Bank Name:</strong> Metrobank</li>
                    <li><strong>Account Name:</strong> Ronyx Trading</li>
                    <li><strong>Account Number:</strong> 1575-558-075168</li>
                    
                </ul>
                <p>Once the payment is completed, please send us the payment receipt for verification.</p>

                <!-- File Input for Payment Screenshot -->
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="pymntNo" value="<?php echo $row['pymntNo']; ?>">
                    <div class="mb-3">
                        <label for="receiptUpload" class="form-label">Upload Payment Receipt <small>(Max 3 images)</small>:</label>
                        <input type="file" class="form-control" id="receiptUpload" name="receipts[]" accept="image/*" multiple>
                        <label for="refCode" class="form-label">Reference Code <small>(Transaction Code)</small>:</label>
                        <input type="text" class="form-control" id="refCode" name="refCode" >
                        <div id="imagePreview" class="mt-3 d-flex flex-wrap"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submitReceipt" class="btn btn-primary">Submit Receipt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>

<script>
    // Add this script to update pymntNo in modal on open
document.addEventListener('DOMContentLoaded', function() {
    var paymentModal = document.getElementById('paymentModal');
    paymentModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var pymntNo = button.getAttribute('data-pymntno'); // Extract info from data-* attributes
        paymentModal.querySelector('input[name="pymntNo"]').value = pymntNo; // Update the hidden input field
    });
});
document.getElementById('receiptUpload').addEventListener('change', function() {
    const maxFiles = 3;
    const files = Array.from(this.files);
    const imagePreview = document.getElementById('imagePreview');
    imagePreview.innerHTML = '';  // Clear existing previews

    if (files.length > maxFiles) {
        alert(`Please select up to ${maxFiles} images.`);
        this.value = '';  // Clear the file input
        return;
    }

    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'me-2 mb-2';
                img.style.width = '100px';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                imagePreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>