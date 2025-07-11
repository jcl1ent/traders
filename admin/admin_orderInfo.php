<?php
$page_title = "Admin Order Information";
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
                                    if (isset($_GET['orderNo'])) {
                                        $orderNo = $_GET['orderNo'];

                                        $query = "
                                        SELECT 
                                            orders.orderNo, 
                                            orders.fullName, 
                                            customers.address,
                                            CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                            GROUP_CONCAT(CONCAT(order_items.prodName, ' ', order_items.prodPrice) SEPARATOR ', ') AS prodPrices,
                                            GROUP_CONCAT(CONCAT(order_items.prodName, ' ', order_items.totalProductPrice) SEPARATOR ', ') AS totalProductPrices,  
                                            GROUP_CONCAT(order_items.prodName SEPARATOR ', ') AS productNames, 
                                            SUM(order_items.quantity) AS totalQuantity,
                                            orders.paymentType, 
                                            orders.payable, 
                                            orders.totalPrice, 
                                            orders.orderDate,
                                            orders.status,
                                            orders.assignedStaff,
                                            order_items.prodPrice,
                                            order_items.totalProductPrice                                                 
                                        FROM orders
                                        INNER JOIN order_items ON orders.orderNo = order_items.orderNo
                                        INNER JOIN customers ON orders.custId = customers.custId
                                        LEFT JOIN staffs ON orders.assignedStaff = staffs.staffId 
                                        WHERE orders.orderNo = ?
                                        GROUP BY orders.orderNo;
                                    ";
                                    
                                        $stmt = $con->prepare($query);
                                        $stmt->bind_param("i", $orderNo); // Bind pdngReqsNo as string
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        if ($result->num_rows > 0) {
                                            $row = $result->fetch_assoc();

                                    ?>    
                                        <form>                                        
                                            <h3 class="text-center pb-2">ORDER INFORMATION</h3>
                                            <div class="mb-3 row">
                                                <label for="staticCustId" class="col col-form-label">Order Number:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['orderNo'] ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCustId" class="col col-form-label">Customer Name:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['fullName'] ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCustId" class="col col-form-label">Address:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['address'] ?>"  disabled readonly>                                                      
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Assigned Staff:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['staffName'] ? $row['staffName'] : 'No staff assigned'; ?>" disabled readonly>                                                  
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3 row">
    <div class="col-3 col-md-2">Product Number</div>
    <div class="col-6 col-md-4">Product Name</div>
    <div class="col-2 col-md-2">Quantity</div>
    <div class="col-2 col-md-2">Price</div>
    <div class="col-2 col-md-2">Total Price</div>
</div>

<?php
$orderItemsQuery = "SELECT prodNo, prodName, quantity, prodPrice FROM order_items WHERE orderNo = ?";
$itemsStmt = $con->prepare($orderItemsQuery);
$itemsStmt->bind_param("i", $orderNo);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

while ($item = $itemsResult->fetch_assoc()) {
    $totalProductPrice = $item['quantity'] * $item['prodPrice'];
    echo "<div class='mb-3 row'>";
    echo "<div class='col-3 col-md-2'><label class='form-label'>{$item['prodNo']}</label></div>";
    echo "<div class='col-6 col-md-4'><label class='form-label'>{$item['prodName']}</label></div>";
    echo "<div class='col-2 col-md-2'><input type='number' name='quantities[{$item['prodNo']}]' value='{$item['quantity']}' class='form-control quantity-input' data-prodno='{$item['prodNo']}' readonly disabled></div>";
    echo "<div class='col-2 col-md-2'><input type='number' name='prices[{$item['prodNo']}]' value='{$item['prodPrice']}' class='form-control price-input' step='0.01' min='0' data-prodno='{$item['prodNo']}' readonly disabled></div>";
    echo "<div class='col-2 col-md-2'><input type='text' value='" . number_format($totalProductPrice, 2) . "' class='form-control total-price' id='totalPrice_{$item['prodNo']}' readonly disabled></div>";
    echo "</div>";
}
$itemsStmt->close();
?>

                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Total Price:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['totalPrice']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Payable:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['payable']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Payment Type:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['paymentType']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Order Date:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['orderDate']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            <div class="mb-3 row">
                                                <label for="staticCreateDate" class="col col-form-label">Status:</label>
                                                <div class="col">
                                                <input class="form-control" type="text" value="<?php echo $row['status']; ?>"  disabled readonly>                                                    
                                                </div>
                                            </div>
                                            
                                            <a href="admin_orders.php">
                                                <button type="button" class="btn btn-secondary">
                                                    <i class="bi bi-arrow-90deg-left"></i> Back 
                                                </button>
                                            </a>
                                        </form>
                                        <?php
                                            } else {
                                                echo "Order Information not found.";
                                            }
                                            $stmt->close();
                                        } else {
                                            echo "Order Number provided.";
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
