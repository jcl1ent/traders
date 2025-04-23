<?php
include("logincode.php");
$page_title = "View Order";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");

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
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead><h3 class="text-center">Order Information</h3>
                                            <tr class="text-center">                                      	
                                                <th scope="col">Product Image</th>
                                                <th scope="col">Product Name</th>
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Product Price</th>
                                                <th scope="col">Total Product Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (isset($_SESSION['email']) && isset($_SESSION['custId'])) {
                                                $custId = $_SESSION['custId'];
                                                if (isset($_GET['orderNo'])) {
                                                    $orderNo = intval($_GET['orderNo']); // Get the specific order number

                                                    // Fetch the items for this specific order
                                                    $vorderitems_query = "
                                                        SELECT oi.*, p.prodImg 
                                                        FROM order_items oi
                                                        JOIN products p ON oi.prodNo = p.prodNo
                                                        WHERE oi.orderNo = ?";
                                                    $stmt_vorderitems = $con->prepare($vorderitems_query);
                                                    $stmt_vorderitems->bind_param("i", $orderNo);
                                                    $stmt_vorderitems->execute();
                                                    $result_items = $stmt_vorderitems->get_result();

                                                    if ($result_items->num_rows > 0) {
                                                        while ($row = $result_items->fetch_assoc()) {
                                                            $imageData = base64_encode($row['prodImg']);
                                                            ?>
                                                            <tr class="text-center">
                                                                <td data-label="Product Image">
                                                                    <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid" style="height: 100px; width: 100px; object-fit: cover;">
                                                                </td>
                                                                <td data-label="Product Name"><?php echo htmlspecialchars($row['prodName']); ?></td>
                                                                <td data-label="Quantity"><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                                <td data-label="Product Price"><?php echo htmlspecialchars($row['prodPrice']); ?></td>
                                                                <td data-label="Total Product Price"><?php echo htmlspecialchars($row['totalProductPrice']); ?></td>
                                                            </tr>
                                                            <?php 
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='5'>No items found for this order.</td></tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='5'>Error: Order number is not specified.</td></tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5'>Error: Session variables are not set.</td></tr>";
                                            }
                                            ?>
                                            
                                        </tbody>
                                        
                                    </table>
                                    <div class="back mb-3">
                                            <a href="vorder_customer.php">
                                                <button type="button" class="btn btn-secondary">
                                                <i class="bi bi-arrow-90deg-left"></i> Back
                                                </button>
                                            </a>
                                            </div>
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
