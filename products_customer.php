<?php
include("logincode.php");
$page_title = "Products";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");
$cartCount = 0;

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email']; // Get the customer email from the session
    if (isset($_SESSION['userId'])) {
        $userId = $_SESSION['userId']; // Get the user ID from the session
    } else {
        echo "Error: userId is not set in the session.";
        exit;
    }

    // Update cart count based on session data
    if (isset($_SESSION['cart'][$userId])) {
        $cartCount = count($_SESSION['cart'][$userId]);
    }

    if (isset($_POST['add_to_cart'])) {
        $prodNo = $_GET['prodNo'];
        $inputQuantity = $_POST['quantity']; // Get the quantity from the input
        // Fetch product details from the database
        $query = "SELECT prodImg, quantity FROM products WHERE prodNo = '$prodNo'";
        $result = mysqli_query($con, $query);
        $row = mysqli_fetch_array($result);

        if ($row) {
            $availableQuantity = $row['quantity']; // Available quantity in the database

            // Check if the requested quantity exceeds available quantity
            if ($inputQuantity > $availableQuantity) {
                echo '<script>alert("Requested quantity exceeds available stock. Available stock: ' . $availableQuantity . '")</script>';
                echo '<script>window.location="products_customer.php"</script>';
                exit; // Exit to prevent further processing
            }

            
            // Check if cart exists for this user
            if (isset($_SESSION['cart'][$userId])) {
                $item_array_id = array_column($_SESSION['cart'][$userId], 'prodNo');
                if (!in_array($prodNo, $item_array_id)) {
                    $count = count($_SESSION['cart'][$userId]);
                    $item_array = array(
                        'prodNo' => $prodNo,
                        'prodName' => $_POST['prodName'],
                        'prodPrice' => $_POST['prodPrice'],
                        'quantity' => $inputQuantity,
                        'prodImg' => $row['prodImg'],
                    );
                    $_SESSION['cart'][$userId][$count] = $item_array;
                    $cartCount++; // Increment cart count
                    echo '<script>alert("Product is added to cart")</script>';
                    echo '<script>window.location="products_customer.php"</script>';
                } else {
                    echo '<script>alert("Product is already added to cart")</script>';
                    echo '<script>window.location="products_customer.php"</script>';
                }
            } else {
                $item_array = array(
                    'prodNo' => $prodNo,
                    'prodName' => $_POST['prodName'],
                    'prodPrice' => $_POST['prodPrice'],
                    'quantity' => $inputQuantity,
                    'prodImg' => $row['prodImg'],
                );
                $_SESSION['cart'][$userId][0] = $item_array;
                $cartCount++; // Increment cart count
            }
        }
    }

    // Handle delete from cart
    if (isset($_GET['action']) && $_GET['action'] == "delete") {
        foreach ($_SESSION["cart"][$userId] as $key => $value) {
            if ($value["prodNo"] == $_GET["prodNo"]) {
                unset($_SESSION["cart"][$userId][$key]);
                $cartCount = count($_SESSION["cart"][$userId]); // Recalculate cart count
                echo '<script>alert("Product has been removed")</script>';
                echo '<script>window.location="cart_customer.php"</script>';
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="container-fluid">
        <div class="col">
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <a href="cart_customer.php">
                                <button type="button" class="btn btn-primary d-flex position-relative" name="cart" id="cart-button" data-cart-count="<?= $cartCount ?>">
                                    <i class="bi bi-cart-fill"> My Cart</i> 
                                    <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: <?= $cartCount > 0 ? 'inline' : 'none' ?>;">
                                        <?= $cartCount ?>
                                    </span>
                                </button>
                            </a>

                            <div class="row">
                                <?php
                                // Fetch products
                                $query = "SELECT * FROM products";
                                $result = mysqli_query($con, $query);

                                while ($row = mysqli_fetch_array($result)) {
                                    $imageData = base64_encode($row['prodImg']); 
                                ?>
                                <div class="col-lg-4 col-md-6 col-sm-12 p-2">
                                    <form method="POST" action="products_customer.php?prodNo=<?= $row['prodNo'] ?>">
                                        <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid" style="height: 150px; object-fit: cover;">
                                        <div class="d-flex flex-column align-items-center mt-3">
                                            <h5 class="text-dark"><?php echo $row['prodName']; ?></h5>
                                            <h5 class="text-dark">P<?php echo $row['prodPrice']; ?></h5>
                                            <p class="text-dark">Quantity: <?php echo $row['quantity']; ?></p>

                                            <!-- Check if the product is out of stock -->
                                            <?php if ($row['quantity'] > 0): ?>
                                                <!-- Show the input for quantity and Add to Cart button if in stock -->
                                                <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $row['quantity']; ?>">
                                                <input type="hidden" name="prodName" value="<?php echo $row['prodName']; ?>">
                                                <input type="hidden" name="prodPrice" value="<?php echo $row['prodPrice']; ?>">
                                                <input type="submit" name="add_to_cart" style="margin-top: 5px;" class="btn btn-success bg-gradient" value="Add to Cart">
                                            <?php else: ?>
                                                <!-- Show 'Out of Stock' button if the product is out of stock -->
                                                <button type="button" class="btn btn-danger bg-gradient" style="margin-top: 5px;" disabled>Out of Stock</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>

                                <?php } ?>
                            </div>
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to update the cart count display
        function updateCartCount(count) {
            const cartCountElement = document.getElementById('cart-count');
            const cartButton = document.getElementById('cart-button');

            // Update the count display
            cartCountElement.innerText = count;

            // Show or hide the badge based on the count
            cartCountElement.style.display = count > 0 ? 'inline' : 'none';

            // Update the data attribute on the button
            cartButton.setAttribute('data-cart-count', count);
        }

        // Call this function after adding an item to the cart
        <?php if (isset($_POST['add_to_cart'])): ?>
            updateCartCount(<?= $cartCount ?>);
        <?php endif; ?>

        // Call this function after removing an item from the cart
        // updateCartCount(newCount);
    </script>
</body>
</html>
