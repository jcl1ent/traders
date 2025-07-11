<?php
include("logincode.php");
$page_title = "Cart";
include("sidebar.php");
include("includes/header.php");
include("dbcon.php");


$totalPrice = 0;

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email']; // Get the customer email from the session
    if (isset($_SESSION['userId'])) {
        $userId = $_SESSION['userId']; // Get the user ID from the session
    } else {
        // Handle the case where userId is not set in the session
        echo "Error: userId is not set in the session.";
        exit;
    }

    $custId_query = "SELECT custId FROM customers WHERE userId = ?";
    $stmt_custId = $con->prepare($custId_query);

    if (!$stmt_custId) {
        die("CustId query prepare failed: " . $con->error);
    }

    $stmt_custId->bind_param("i", $userId);
    $stmt_custId->execute();
    $stmt_custId->bind_result($custId);
    $stmt_custId->fetch();
    $stmt_custId->close();

    $fullName_query = "SELECT fullName FROM users WHERE userId = ?";
    $stmt_fullName = $con->prepare($fullName_query);

    if (!$stmt_fullName) {
        die("CustId query prepare failed: " . $con->error);
    }

    $stmt_fullName->bind_param("i", $userId);
    $stmt_fullName->execute();
    $stmt_fullName->bind_result($fullName);
    $stmt_fullName->fetch();
    $stmt_fullName->close();

    $sql = "SELECT adminId FROM admin LIMIT 1";  
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($adminId);
    $stmt->fetch();
    $stmt->close();


    // Calculate total price from the cart
    if (isset($_SESSION['cart'][$userId]) && is_array($_SESSION['cart'][$userId])) {
        foreach ($_SESSION['cart'][$userId] as $product) {
            $prodPrice = isset($product['prodPrice']) ? $product['prodPrice'] : 0;
            $quantity = isset($product['quantity']) ? $product['quantity'] : 0;
            $totalProductPrice = $prodPrice * $quantity;
            $totalPrice += $totalProductPrice;
            $paymentType = isset($product['paymentType']) ? $product['paymentType'] :'';
        }

        // Check if the checkout button is clicked
        if (isset($_POST['check_out'])) {
            $status = "Pending Order";
            
            if (isset($_POST['paymentType'])) {
                $paymentType = $_POST['paymentType']; // Get the selected payment method
                
                if ($paymentType === 'Full' || $paymentType === 'COD') {
                    $payable = $totalPrice; // Full payment
                } else {
                    $payable = $totalPrice / 2; // 50% for partial payment
                }
        
                // Start a transaction to ensure data integrity
                $con->begin_transaction();
        
                try {
                    // Insert into the orders table
                    $sql = "INSERT INTO orders (custId, fullName, totalPrice, paymentType, payable, status) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("isdsds", $custId, $fullName, $totalPrice, $paymentType, $payable, $status);
                    $stmt->execute();
                    $orderNo = $con->insert_id; // Get the last inserted order ID
                    $stmt->close();

                    $log_action_query2 = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
                    $action = $fullName . ' submitted an order request.';
                    $status = 'unread';
                    $log_action_stmt2 = $con->prepare($log_action_query2);
                    $log_action_stmt2->bind_param("iss", $adminId, $action, $status);
                    $log_action_stmt2->execute();
                    $log_action_stmt2->close();
        
                    // Insert each product into the order_items table and update product quantities
                    foreach ($_SESSION['cart'][$userId] as $product) {
                        $prodNo = isset($product['prodNo']) ? $product['prodNo'] : '';
                        $prodName = isset($product['prodName']) ? $product['prodName'] : '';
                        $prodPrice = isset($product['prodPrice']) ? $product['prodPrice'] : 0;
                        $quantity = isset($product['quantity']) ? $product['quantity'] : 0;
                        $prodImg = isset($product['prodImg']) ? $product['prodImg'] : '';
        
                        $totalProductPrice = $prodPrice * $quantity;
        
                        $sql = "INSERT INTO order_items (orderNo, prodNo, prodName, prodImg, quantity, prodPrice, totalProductPrice) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param("iisiddi", $orderNo, $prodNo, $prodName, $prodImg, $quantity, $prodPrice, $totalProductPrice);
        
                        if (!$stmt->execute()) {
                            throw new Exception("Error inserting into order_items: " . $stmt->error);
                        }
                        $stmt->close();
        
                        // Update the quantity of the product in the products table
                        $sql_update_quantity = "UPDATE products SET quantity = quantity - ? WHERE prodNo = ?";
                        $stmt_update = $con->prepare($sql_update_quantity);
                        $stmt_update->bind_param("ii", $quantity, $prodNo);
        
                        if (!$stmt_update->execute()) {
                            throw new Exception("Error updating product quantity: " . $stmt_update->error);
                        }
        
                        $stmt_update->close();
                        
                    }
        
                    // If everything is successful, commit the transaction
                    $con->commit();
        
                    // Display success message and clear the cart after successful order
                    echo "<script>alert('Order has been successfully placed.')</script>";
                    echo '<script>window.location="cart_customer.php"</script>';
                    unset($_SESSION['cart'][$userId]); // Clear the cart
        
                } catch (Exception $e) {
                    // Rollback the transaction if something went wrong
                    $con->rollback();
                    echo "<script>alert('Error placing order: " . $e->getMessage() . "')</script>";
                    echo '<script>window.location="cart_customer.php"</script>';
                }
        
            } else {
                echo "<script>alert('Please select a Payment Type.')</script>"; // If MOP is not selected, show an error message
                echo '<script>window.location="cart_customer.php"</script>';
            }

            $sql_product_quantity = "SELECT quantity FROM products WHERE prodNo = ?";
            $stmt_product_quantity = $con->prepare($sql_product_quantity);
            $stmt_product_quantity->bind_param("i", $prodNo);
            $stmt_product_quantity->execute();
            $result_product_quantity = $stmt_product_quantity->get_result();
            while ($row = $result_product_quantity->fetch_assoc()) {
                $productQuantity = $row['quantity'];
                if ($productQuantity == 0) {
                    $sql_update_product_status = "UPDATE products SET productStatus = 'Out Of Stock' WHERE prodNo = ?";
                    $stmt_update = $con->prepare($sql_update_product_status);
                    $stmt_update->bind_param("i", $prodNo);
                    $stmt_update->execute();
                    $stmt_update->close();
                }

            }
            $stmt_product_quantity->close();
            

        }
        

    }
}

?>
<div class="button p-3">
    <a href="products_customer.php">
        <button class="btn btn-primary bg-gradient">
            <i class="bi bi-arrow-90deg-left"> Continue Shopping</i>
        </button>
    </a>
    </div>
     
<?php
// Display cart contents if available
 if (isset($_SESSION['cart'][$userId]) && is_array($_SESSION['cart'][$userId]) && count($_SESSION['cart'][$userId]) > 0) {
    $totalPrice = 0;   
    foreach ($_SESSION['cart'][$userId] as $product) {
        $prodName = isset($product['prodName']) ? $product['prodName'] : '';
        $prodPrice = isset($product['prodPrice']) ? $product['prodPrice'] : 0;
        $quantity = isset($product['quantity']) ? $product['quantity'] : 0;
        $prodImg = isset($product['prodImg']) ? $product['prodImg'] : '';

        $totalProductPrice = $prodPrice * $quantity;
        $totalPrice += $totalProductPrice;
        $payable = 0;
        ?>
        <div class="card mb-3 m-3">
            <div class="card-body">
                <div class="row g-0">
                    <div class="col-md-4">
                        <?php if ($prodImg): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($prodImg) ?>" class="d-block mx-auto img-fluid" style="height: 150px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <h5 class="card-text">Name: <?= htmlspecialchars($prodName) ?></h5>
                            <p class="card-text">Price: P<?= htmlspecialchars($prodPrice) ?></p>
                            <p class="card-text">Quantity: <?= htmlspecialchars($quantity) ?></p>
                            <p class="card-text">Total Product Price: <?= htmlspecialchars($totalProductPrice) ?></p>
                            <a href="remove_from_cart.php?prodNo=<?= $product['prodNo']?>" class="btn btn-danger">Remove</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
<div class="card m-3" style="width: 35%; float: right;">
    <div class="card-body mx-auto p-2 m-3">
        <div class="total-price">
            <?php
            echo "<h5 class='me-3 text-center'>Total Price: P" . number_format($totalPrice, 2) . "</h5>";
            echo "<h3 class='me-3 text-center'>Payable: <span id='payableDisplay'>P" . number_format(0, 2) . "</span></h3>"; // Display updated payable
            ?>
            <form action="" method="POST">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="paymentType" id="paymentTypeCOD" value="COD">
                    <label class="form-check-label" for="paymentTypeCOD">COD(Full Payment)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="paymentType" id="paymentTypePartial" value="Partial">
                    <label class="form-check-label" for="paymentTypePartial">Partial Payment (50%)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="paymentType" id="paymentTypeFull" value="Full">
                    <label class="form-check-label" for="paymentTypeFull">Full Payment</label>
                </div>
                <div class="button d-flex justify-content-center">
                    <button class="btn btn-info" name="check_out" value="Check Out">
                        <i class="bi bi-bag-check-fill"></i> Check Out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Function to calculate the payable and total amounts
    function calculateAmounts() {
        const totalPrice = parseFloat("<?= $totalPrice ?>") || 0; // Get total price from PHP
        const paymentType = document.querySelector('input[name="paymentType"]:checked')?.value || ''; // Get selected payment type
        const payableDisplay = document.getElementById('payableDisplay'); // Display payable value
        
        let payable = 0;

        // Calculate payable based on selected payment type
        if (paymentType === 'Full' || paymentType === 'COD') {
            payable = totalPrice; // Full payment
        } else {
            payable = totalPrice / 2; // 50% for partial payment
        }

        // Update the payable display
        if (payableDisplay) {
            payableDisplay.textContent = 'P ' + payable.toFixed(2);
        }
    }

    // Add event listeners to update payable when payment type changes
    document.querySelectorAll('input[name="paymentType"]').forEach((input) => {
        input.addEventListener('change', calculateAmounts);
    });

    // Initial calculation on page load (if a payment type is already selected)
    calculateAmounts();
</script>

<?php
} else {
    echo "<h3 class = 'text-center'>Your cart is empty!</h3>";
    }
?>
