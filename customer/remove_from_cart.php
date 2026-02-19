<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../dbcon.php");
include_once __DIR__ . "/../config.php";

if (isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];

    if (isset($_GET['prodNo'])) {
        if (isset($_SESSION['cart'][$userId]) && is_array($_SESSION['cart'][$userId])) {
            foreach ($_SESSION['cart'][$userId] as $key => $product) {
                if ($product['prodNo'] == $_GET['prodNo']) {
                    unset($_SESSION['cart'][$userId][$key]);
                    break;
                }
            }
            // Reindex the cart array to prevent empty indexes
            $_SESSION['cart'][$userId] = array_values($_SESSION['cart'][$userId]);
        }

        // Redirect back to the cart page
        header('Location: ' . url('customer/cart_customer.php'));
        exit();
    } else {
        echo "Error: Product number is not set.";
    }
} else {
    $_SESSION['status'] = "Session expired. Please log in again.";
    header('Location: ' . url('index.php'));
    exit();
}
