<?php
session_start();
include("dbcon.php");

if (isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId']; // Retrieve userId from session

    if (isset($_GET['prodNo'])) {
        foreach ($_SESSION['cart'][$userId] as $key => $product) {
            if ($product['prodNo'] == $_GET['prodNo']) {
                unset($_SESSION['cart'][$userId][$key]); // Remove the product from the user's cart
                break;
            }
        }

        // Reindex the cart array to prevent empty indexes
        $_SESSION['cart'][$userId] = array_values($_SESSION['cart'][$userId]);

        // Redirect after modifying the cart
        header('Location: cart_customer.php');
        exit(); // Ensure the script stops after the header redirect
    } else {
        echo "Error: Product number is not set.";
    }
} else {
    echo "Error: userId is not set in the session.";
    exit();
}
?>
