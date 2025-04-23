<?php
$page_title = "Staff Update Order";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['updateOrder'])) {
    // Retrieve posted values
    $orderNo = $_POST['orderNo'] ?? $currentRow['orderNo']; 
    $assignedStaff = $_POST['assignedStaff'] ?? $currentRow['assignedStaff'];
    $totalPrice = $_POST['totalPrice'] ?? $currentRow['totalPrice'];
    $payable = $_POST['payable'] ?? $currentRow['payable'];
    $paymentType = $_POST['paymentType'] ?? $currentRow['paymentType'];
    $status = $_POST['status'] ?? $currentRow['status'];
    $address = $_POST['address'] ?? $currentRow['address'];

    // Update the orders table
    $query = "UPDATE orders SET assignedStaff=?, totalPrice=?, payable=?, paymentType=?, status=? WHERE orderNo=?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("sdsssi", $assignedStaff, $totalPrice, $payable, $paymentType, $status, $orderNo);
    if($stmt->execute()){
        echo "<script>alert('Order updated successfully!');</script>";
        echo "<script>window.location.href='staff_orders.php';</script>";
    } // Execute the query
    $stmt->close();

    $balance = ($paymentType == 'Partial') ? $payable : 0;

    $updateStaffServQuery = "UPDATE payment
                             SET totalAmount = ?, payable = ?, paymentType = ?, balance = ?
                             WHERE orderNo = ?";
    $stmtStaffServ = $con->prepare($updateStaffServQuery);
    $stmtStaffServ->bind_param("iisii", $totalPrice, $payable, $paymentType, $balance, $orderNo);
    $stmtStaffServ->execute();

    // Handle the quantity and price updates
    if (isset($_POST['quantities']) && isset($_POST['prices'])) {
        $quantities = $_POST['quantities']; // Ensure the variable is assigned
        $prices = $_POST['prices']; // Ensure the variable is assigned

        // Loop through each product item and update the corresponding quantity and price
        foreach ($quantities as $prodNo => $quantity) {
            if (isset($prices[$prodNo])) {
                $productPrice = $prices[$prodNo];
                
                // Get the current quantity from order_items
                $currentQuantityQuery = "SELECT quantity FROM order_items WHERE orderNo = ? AND prodNo = ?";
                $stmtCurrentQty = $con->prepare($currentQuantityQuery);
                $stmtCurrentQty->bind_param("ii", $orderNo, $prodNo);
                $stmtCurrentQty->execute();
                $result = $stmtCurrentQty->get_result();
                $currentQtyRow = $result->fetch_assoc();

                // Ensure the value is assigned to a variable before using it
                $currentQuantity = isset($currentQtyRow['quantity']) ? $currentQtyRow['quantity'] : 0;
                $stmtCurrentQty->close();

                // Calculate the difference in quantities
                $quantityDifference = $quantity - $currentQuantity;

                // Update the order_items table
                $update_items_query = "UPDATE order_items SET quantity = ?, prodPrice = ?, totalProductPrice = ? WHERE orderNo = ? AND prodNo = ?";
                $totalProductPrice = $productPrice * $quantity;
                $item_stmt = $con->prepare($update_items_query);
                $item_stmt->bind_param("diidi", $quantity, $productPrice, $totalProductPrice, $orderNo, $prodNo);
                $item_stmt->execute();
                $item_stmt->close();
                
                  // Update the products table based on the quantity change
                  if ($quantityDifference > 0) {
                    // Decrease the product quantity in the products table (product quantity was increased in the order)
                    $updateProductQuery = "UPDATE products SET quantity = quantity - ? WHERE prodNo = ?";
                    $stmtProduct = $con->prepare($updateProductQuery);
                    $stmtProduct->bind_param("ii", $quantityDifference, $prodNo);
                    $stmtProduct->execute();
                    $stmtProduct->close();
                    
                } elseif ($quantityDifference < 0) {
                    // Increase the product quantity in the products table (product quantity was decreased in the order)
                    $updateProductQuery = "UPDATE products SET quantity = quantity + ? WHERE prodNo = ?";
                    $stmtProduct = $con->prepare($updateProductQuery);
                    
                    // Store the absolute value of quantityDifference in a variable
                    $quantityDiffAbs = abs($quantityDifference);
                    
                    $stmtProduct->bind_param("ii", $quantityDiffAbs, $prodNo);
                    $stmtProduct->execute();
                    $stmtProduct->close();
                }
                
            }
        }
    }
    $fetch_customer_query = "SELECT 
                            CONCAT(customers.firstname, ' ', customers.middlename, ' ', customers.lastname) AS fullName, 
                            customers.custId, 
                            orders.status
                         FROM orders
                         INNER JOIN customers ON orders.custId = customers.custId
                         WHERE orders.orderNo = ?";
    $fetch_customer_stmt = $con->prepare($fetch_customer_query);
    $fetch_customer_stmt->bind_param("i", $orderNo); 
    $fetch_customer_stmt->execute();
    $fetch_customer_stmt->bind_result($fullName, $custId, $status);
    $fetch_customer_stmt->fetch();
    $fetch_customer_stmt->close();

    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'The status of your Order No.' .$orderNo. ' is now ' . $status; 
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();

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
            <div class="col-md-6 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                        <div class="card-body mb-3">
                            <?php 
                            if (isset($_GET['orderNo'])) {
                                $orderNo = $_GET['orderNo'];

                                // Fetch the order details
                                $query = "
                                            SELECT 
                                                orders.orderNo, 
                                                orders.fullName, 
                                                customers.address,
                                                CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                                GROUP_CONCAT(order_items.prodPrice SEPARATOR ', ') AS prodPrices,
                                                GROUP_CONCAT(order_items.totalProductPrice SEPARATOR ', ') AS totalProductPrices,
                                                GROUP_CONCAT(order_items.prodName SEPARATOR ', ') AS productNames, 
                                                order_items.quantity,
                                                orders.paymentType, 
                                                orders.payable, 
                                                orders.totalPrice, 
                                                orders.orderDate,
                                                orders.status,
                                                orders.assignedStaff,
                                                order_items.prodPrice,
                                                order_items.prodNo,
                                                orders.assignedStaff,
                                                order_items.totalProductPrice
                                            FROM orders
                                            INNER JOIN order_items ON orders.orderNo = order_items.orderNo  
                                            INNER JOIN customers ON orders.custId = customers.custId
                                            INNER JOIN staffs ON orders.assignedStaff = staffs.staffId 
                                            WHERE orders.orderNo = ?
                                            GROUP BY orders.orderNo
                                            ORDER BY orders.orderNo DESC
                                        ";
                                $stmt = $con->prepare($query);
                                $stmt->bind_param("i", $orderNo);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $currentRow = $row;
                            ?>
                                <form action="staff_updateOrder.php?orderNo=<?php echo $orderNo; ?>" method="POST">
                                    <input type="hidden" name="orderNo" value="<?php echo $row['orderNo']; ?>">

                                    <h3 class="text-center pb-2">UPDATE ORDER</h3>
                                    <div class="mb-3 row">
                                        <label for="staticfullName" class="col col-form-label">Order Number:</label>
                                        <div class="col">
                                            <input class="form-control" type="text" name="fullName" value="<?php echo $row['orderNo']; ?>" readonly disabled>
                                        </div>
                                    </div> 
                                    <div class="mb-3 row">
                                        <label for="staticfullName" class="col col-form-label">Customer Name:</label>
                                        <div class="col">
                                            <input class="form-control" type="text" name="fullName" value="<?php echo $row['fullName']; ?>" readonly disabled>
                                        </div>
                                    </div> 
                                    <div class="mb-3 row">
                                        <label for="staticAddress" class="col col-form-label">Address:</label>
                                        <div class="col">
                                            <input class="form-control" type="text" name="address" value="<?php echo $row['address']; ?>">
                                        </div>
                                    </div> 
                                    <div class="mb-3 row">
                                        <label for="assignedStaff" class="col col-form-label">Assigned Staff:</label>
                                        <div class="col">
                                            <input class="form-control" type="text" name="assignedStaff" value="<?php echo $row['staffName']; ?>" readonly disabled>
                                        </div>
                                    </div>
                                    <input type="hidden" name="assignedStaff" value="<?php echo $row['assignedStaff']; ?>"><br>
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
                                        echo "<div class='col-2 col-md-2'><input type='number' name='quantities[{$item['prodNo']}]' value='{$item['quantity']}' class='form-control quantity-input' data-prodno='{$item['prodNo']}'></div>";
                                        echo "<div class='col-2 col-md-2'><input type='number' name='prices[{$item['prodNo']}]' value='{$item['prodPrice']}' class='form-control price-input' step='0.01' min='0' data-prodno='{$item['prodNo']}'></div>";
                                        echo "<div class='col-2 col-md-2'><input type='text' value='" . number_format($totalProductPrice, 2) . "' class='form-control total-price' id='totalPrice_{$item['prodNo']}' readonly disabled></div>";
                                        echo "</div>";
                                    }
                                    $itemsStmt->close();
                                    ?>


                                   <br> 
                                    <div class="mb-3 row">
                                        <label class="col col-form-label">Total Price:</label>
                                        <div class="col">
                                            <input class="form-control" type="text" name="totalPrice" id="totalPrice" value="<?php echo $row['totalPrice']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="col col-form-label">Payable:</label>
                                        <div class="col">
                                            <input class="form-control" type="text" name="payable" id="payable" value="<?php echo htmlspecialchars($row['payable']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="col col-form-label">Payment Type:</label>
                                        <div class="col">
                                            <select class="form-control" name="paymentType" id="paymentType" onchange="updatePayableAndTotalAmount()">
                                                <option value="COD" <?php echo $row['paymentType'] == 'COD' ? 'selected' : ''; ?>>COD</option>
                                                <option value="Full" <?php echo $row['paymentType'] == 'Full' ? 'selected' : ''; ?>>Full</option>
                                                <option value="Partial" <?php echo $row['paymentType'] == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3 row">
                                        <?php
                                            $query = "SELECT paymentStatus FROM payment WHERE orderNo = ?";
                                            $stmt = $con->prepare($query);
                                            $stmt->bind_param("i", $orderNo);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            if($result->num_rows > 0){
                                                $payStat = $result->fetch_assoc();
                                           
                                            if($payStat['paymentStatus'] == 'To be checked' || $payStat['paymentStatus'] == 'To be paid'){
                                        ?>
                                        <label for="staticstatus" class="col col-form-label">Status:</label>
                                        <div class="col">
                                            <select class="form-select" name="status" disabled> 
                                                <option value="<?php echo $row['status']; ?>"><?php echo $row['status']; ?></option>
                                                <option value="Pending Order">Pending Order</option>
                                                <option value="Order Confirmed">Order Confirmed</option>
                                                <option value="Order Declined">Order Declined</option>
                                                <option value="Order Placed">Order Placed</option>
                                                <option value="Preparing to Ship">Preparing to Ship</option>
                                                <option value="Order Being Shipped">Order Being Shipped</option>
                                                <option value="Out for Delivery">Out for Delivery</option>
                                                <option value="Order Delivered">Order Delivered</option>
                                                <option value="Delivery Unsuccessful">Delivery Unsuccessful</option>
                                            </select>
                                        </div>
                                        <?php }else{
                                            ?>
                                            <label for="staticstatus" class="col col-form-label">Status:</label>
                                            <div class="col">
                                            <select class="form-select" name="status"> 
                                                <option value="<?php echo $row['status']; ?>"><?php echo $row['status']; ?></option>
                                                <option value="Pending Order">Pending Order</option>
                                                <option value="Order Confirmed">Order Confirmed</option>
                                                <option value="Order Declined">Order Declined</option>
                                                <option value="Order Placed">Order Placed</option>
                                                <option value="Preparing to Ship">Preparing to Ship</option>
                                                <option value="Order Being Shipped">Order Being Shipped</option>
                                                <option value="Out for Delivery">Out for Delivery</option>
                                                <option value="Order Delivered">Order Delivered</option>
                                                <option value="Delivery Unsuccessful">Delivery Unsuccessful</option>
                                            </select>
                                        </div>
                                        <?php
                                        }
                                    } ?>
                                    </div>
                                    <div class="d-flex justify-content-center m-3" style="gap: 10px;">
                                        <a href="staff_orders.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-90deg-left"></i> Back 
                                        </a>
                                        <button type="submit" name="updateOrder" class="btn btn-primary">
                                            <i class="bi bi-arrow-clockwise"></i> Update Order
                                        </button>
                                    </div>
                                </form>
                            <?php 
                            } 
                            $stmt->close();
                        }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('input', function(event) {
        // Check if the event target is a quantity or price input
        if (event.target.classList.contains('quantity-input') || event.target.classList.contains('price-input')) {
            const prodNo = event.target.getAttribute('data-prodno');
            const quantityInput = document.querySelector(`input[name='quantities[${prodNo}]']`);
            const priceInput = document.querySelector(`input[name='prices[${prodNo}]']`);
            const totalPriceField = document.getElementById(`totalPrice_${prodNo}`);

            // Calculate the new total price
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const newTotal = (quantity * price).toFixed(2);

            // Update the total price field
            totalPriceField.value = newTotal;
        }
    });
document.addEventListener('DOMContentLoaded', function () {
    // Add event listeners to all quantity and price inputs
    const quantityInputs = document.querySelectorAll('input[name^="quantities"]');
    const priceInputs = document.querySelectorAll('input[name^="prices"]');
    const totalPriceInput = document.getElementById('totalPrice');
    const payableInput = document.getElementById('payable');
    const paymentTypeSelect = document.getElementById('paymentType');

    // Function to update total price and payable amount
    function updateTotalAndPayable() {
        let totalPrice = 0;
        
        // Loop through each quantity and price input to calculate the total price
        quantityInputs.forEach((quantityInput, index) => {
            const priceInput = priceInputs[index];
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const totalProductPrice = quantity * price;
            totalPrice += totalProductPrice;

            // Update the total product price for this item (if needed)
            const prodNo = quantityInput.name.match(/\d+/)[0];
            document.querySelector(`input[name="quantities[${prodNo}]"]`).value = quantity;
            document.querySelector(`input[name="prices[${prodNo}]"]`).value = price;
        });

        // Update the total price field
        totalPriceInput.value = totalPrice.toFixed(2);

        // Update payable based on payment type
        updatePayableAndTotalAmount(totalPrice);
    }

    // Function to update payable based on payment type
    function updatePayableAndTotalAmount(totalPrice) {
        let payableAmount = 0;

        const paymentType = paymentTypeSelect.value;

        if (paymentType === 'Full') {
            payableAmount = totalPrice;
        } else if (paymentType === 'Partial') {
            payableAmount = totalPrice * 0.5; // Assuming partial is 50%
        } else if (paymentType === 'COD') {
            payableAmount = totalPrice;
        }

        payableInput.value = payableAmount.toFixed(2);
    }

    // Attach change event listeners to quantity and price inputs
    quantityInputs.forEach(input => input.addEventListener('input', updateTotalAndPayable));
    priceInputs.forEach(input => input.addEventListener('input', updateTotalAndPayable));

    // Attach change event listener to payment type select
    paymentTypeSelect.addEventListener('change', function () {
        updatePayableAndTotalAmount(parseFloat(totalPriceInput.value) || 0);
    });
});
</script>
</body>
</html>