<?php
$page_title = "View Receipt";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

// Ensure $pymntNo is set; this may come from a GET request or other means
$pymntNo = isset($_GET['pymntNo']) ? $_GET['pymntNo'] : 0;

// Fetch payment status and type if pymntNo is valid
$paymentStatus = null;
$paymentType = null;
if ($pymntNo > 0) {
    $statusQuery = "SELECT paymentStatus, paymentType FROM payment WHERE pymntNo = ?";
    $statusStmt = $con->prepare($statusQuery);
    $statusStmt->bind_param("i", $pymntNo);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    if ($statusResult->num_rows > 0) {
        $row = $statusResult->fetch_assoc();
        $paymentStatus = $row['paymentStatus'];
        $paymentType = $row['paymentType'];
    }
    $statusStmt->close();
}

if (isset($_POST['acceptPayment'])) {
    // Get the payment number and payment type from the form submission
    $pymntNo = $_POST['pymntNo'];
    $paymentDate = date('Y-m-d H:i:s');
    
    // Update the payment status in the database based on paymentType
    if ($paymentType == 'COD') {
        $newStatus = 'COD Paid';
    } elseif ($paymentType == 'Partial' || $paymentType === 'partial') { 
        $newStatus = 'Partially Paid';
    } elseif(strtolower($paymentType) === 'full') {
        $newStatus = 'Paid';
    }

    $updateQuery = "UPDATE payment SET paymentStatus = ?, paymentDate = ? WHERE pymntNo = ?";
    
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("ssi", $newStatus, $paymentDate, $pymntNo);
    $stmt->execute();
    $stmt->close();
    
    echo "<script>alert('Payment Accepted.')</script>";
    echo '<script>window.location="staff_paymentsService.php"</script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">  
                                <h2 class="card-title text-center">Receipts</h2>
                                <?php 
                                if ($pymntNo > 0) {
                                    $imgQuery = "SELECT paymentImg1, paymentImg2, paymentImg3 FROM payment_images WHERE pymntNo = ?";
                                    $imgStmt = $con->prepare($imgQuery);
                                    $imgStmt->bind_param("i", $pymntNo);
                                    $imgStmt->execute();
                                    $resultImg = $imgStmt->get_result();

                                    // Check if images exist and display them
                                    if ($resultImg->num_rows > 0) {
                                        while ($rowImg = $resultImg->fetch_assoc()) {
                                            for ($i = 1; $i <= 3; $i++) {
                                                $imageColumn = 'paymentImg' . $i;
                                                if (!empty($rowImg[$imageColumn])) {
                                                    echo '<div class="mb-3 text-center">
                                                            <img src="' . htmlspecialchars($rowImg[$imageColumn]) . '" alt="Payment Receipt" class="img-thumbnail" style="max-width: 600px; height: auto;">
                                                          </div>';
                                                }
                                            }
                                        }
                                    } else {
                                        echo '<p class="text-center">No receipts found for this payment.</p>';
                                    }
                                }
                                ?>
                                <div class="back mb-3 d-flex justify-content-center" style="gap: 15px;">
                                    <a href="staff_paymentsService.php">
                                        <button type="button" class="btn btn-secondary">
                                            <i class="bi bi-arrow-90deg-left"></i> Back
                                        </button>
                                    </a>
                                    <!-- Form for Accept Payment button -->
                                    <form method="POST" action="">                                        
                                        <input type="hidden" name="pymntNo" value="<?php echo htmlspecialchars($pymntNo); ?>">
                                        <?php if($paymentStatus != 'Paid' && $paymentStatus != 'COD Paid' && $paymentStatus != 'Partially Paid'){ ?>
                                        <button type="submit" class="btn btn-primary" name="acceptPayment">
                                             Accept Payment
                                        </button>
                                        <?php } ?>
                                    </form>
                                </div>
                            </div>    
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>