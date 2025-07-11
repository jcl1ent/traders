<?php
$page_title = "Dashboard";
include("logincode.php");
include("sidebar.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_SESSION['email']) && isset($_SESSION['custId'])  && isset($_SESSION['userId'])) {

    $custId = $_SESSION['custId'];
    $email = $_SESSION['email'];
    $userId = $_SESSION['userId'];
    // Get the count of the orders
    $cOrder_query = "SELECT COUNT(*) AS total_orders FROM orders WHERE custId = ?";
    $stmt_cOrder = $con->prepare($cOrder_query);
    $stmt_cOrder->bind_param("i", $custId);
    $stmt_cOrder->execute();
    $result_cOrders = $stmt_cOrder->get_result();
    $stmt_cOrder->close();

    // Get the count of the deliverd orders
    $cdOrder_query = "SELECT COUNT(*) AS delivered_orders FROM orders WHERE status ='Order Delivered' AND custId = ?";
    $stmt_cdOrder = $con->prepare($cdOrder_query);
    $stmt_cdOrder->bind_param("i", $custId);
    $stmt_cdOrder->execute();
    $result_cdOrders = $stmt_cdOrder->get_result();
    $stmt_cdOrder->close();

    //Get the count of the tickets
    $cTick_query = "SELECT COUNT(*) AS total_tick FROM ticket WHERE userId = ?";
    $stmt_cTick = $con->prepare($cTick_query);
    $stmt_cTick->bind_param("i", $userId);
    $stmt_cTick->execute();
    $result_cTick = $stmt_cTick->get_result();
    $stmt_cTick->close();

    $cServ_query = "SELECT COUNT(*) AS total_serv FROM reqserv WHERE userId = ? AND servStatus = 'Pending Request'";
    $stmt_cServ = $con->prepare($cServ_query);
    $stmt_cServ->bind_param("i", $userId);
    $stmt_cServ->execute();
    $result_cServ = $stmt_cServ->get_result();
    $stmt_cServ->close();

    $cDecServ_query = "SELECT COUNT(*) AS total_decline FROM declined_reqserv WHERE userId = ?";
    $stmt_cDecServ = $con->prepare($cDecServ_query);
    $stmt_cDecServ->bind_param("i", $userId);
    $stmt_cDecServ->execute();
    $result_cDecServ = $stmt_cDecServ->get_result();

    $cAccServ_query = "SELECT COUNT(*) AS total_accept FROM acceptserv2 WHERE userId = ?";
    $stmt_cAccServ = $con->prepare($cAccServ_query);
    $stmt_cAccServ->bind_param("i", $userId);
    $stmt_cAccServ->execute();
    $result_cAccServ = $stmt_cAccServ->get_result();

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
            <div class="row d-flex justify-content-around ">
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-primary bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Service Requested</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                    if($result_cServ->num_rows > 0){
                                        $row = $result_cServ->fetch_assoc();
                                        echo $row["total_serv"];

                                    }
                                    else{
                                        echo "0 results.";
                                    }
                                ?>
                                </div>
                                <a class="medium text-white" href="vservice_customer.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-success bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Accepted Service Request</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2">
                                    <?php
                                        if($result_cAccServ->num_rows > 0){
                                            $row = $result_cAccServ->fetch_assoc();
                                            echo $row["total_accept"];

                                        }
                                        else{
                                            echo "0 results.";
                                        }
                                    ?>
                                </div>
                                <a class="medium text-white" href="vserviceAcc_customer.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-danger bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Denied Service Request</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2">
                                <?php
                                if($result_cDecServ->num_rows > 0){
                                    $row = $result_cDecServ->fetch_assoc();
                                    echo $row["total_decline"];

                                }
                                else{
                                    echo "0 results.";
                                }
                                ?>
                                </div>
                                <a class="medium text-white" href="vserviceDec_customer.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-warning bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Tickets Submitted</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                    if($result_cTick->num_rows > 0){
                                        $row = $result_cTick->fetch_assoc();
                                        echo $row["total_tick"];

                                    }
                                    else{
                                        echo "0 results.";
                                    }
                                ?>
                                </div>
                                <a class="medium text-white" href="vticket_customer.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-dark bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Total Orders Made</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                    if($result_cOrders->num_rows > 0){
                                        $row = $result_cOrders->fetch_assoc();
                                        echo $row["total_orders"];
                                    }else{
                                        echo "0 results";
                                    }
                                ?>
                                </div>
                                <a class="medium text-white" href="vorder_customer.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-secondary bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center">Delivered Orders</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2">
                                <?php
                                    if($result_cdOrders->num_rows > 0){
                                        $row = $result_cdOrders->fetch_assoc();
                                        echo $row["delivered_orders"];
                                    }else{
                                        echo "0 results";
                                    }
                                ?>
                                </div>
                                <a class="medium text-white" href="vorder_customer.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
    <div class="container">
    <div class="row justify-content-center">
        <!-- Most Requested Service -->
        <div class="col-md-3 mb-3">
            <div class="card-body text-size p-2 bg-success shadow-sm rounded">
                <h5 class="text-center mb-2 text-white">Most Requested Service</h5>
                <div class="card-footer d-flex flex-column align-items-center text-black">
                <?php
                    // Fetch the most requested service
                    $Tserv_query = "SELECT reqserv_service.servName, COUNT(*) AS serv_trend, services.servImg, services.servName, services.rateService, reqserv.servStatus
                                    FROM reqserv_service
                                    INNER JOIN services ON reqserv_service.servCode = services.servCode
                                    INNER JOIN reqserv ON reqserv_service.reqserv = reqserv.reqserv
                                    WHERE reqserv.servStatus = 'Service Completed'
                                    GROUP BY reqserv_service.servName
                                    ORDER BY serv_trend DESC LIMIT 1";
                    $stmt_Tserv = $con->prepare($Tserv_query);
                    $stmt_Tserv->execute();
                    $result_Tserv = $stmt_Tserv->get_result();
                    $row_Tserv = $result_Tserv->fetch_assoc();
                    $stmt_Tserv->close();

                    $most_repetitive_servType = $row_Tserv['servType'] ?? 'None';
                    $total_serv_count = $row_Tserv['serv_trend'] ?? 'None';
                    $serv_rate = $row_Tserv['rateService'] ?? 'None';
                    $serv_image_data = $row_Tserv['servImg'] ?? null;
                    $serv_name = $row_Tserv['servName'] ?? 'None';

                    // Check if servImg is null and handle accordingly
                    if ($serv_image_data === null) {
                        $serv_image_base64 = 'None'; // Set to 'None' if image is null
                    } else {
                        $serv_image_base64 = base64_encode($serv_image_data);
                    }

                    echo "<div class='text-center mb-2'>";
                    // Display image or 'None' if no image
                    if ($serv_image_base64 === 'None') {
                        echo "<p>None</p>"; // Display "None" if image is null
                    } else {
                        echo "<img src='data:image/jpeg;base64," . $serv_image_base64 . "' alt='" . $most_repetitive_servType . "' class='img-fluid rounded' style='width: 60px; height: 60px; object-fit: cover;'>";
                    }
                    echo "</div>";

                    echo "<div class='text-center'>";
                    echo "<p class='mb-1 fw-bold'>Service: <span class='fw-normal'>" . $serv_name . "</span></p>";
                    echo "<p class='mb-1 fw-bold'>Rate: <span class='fw-normal'>" . $serv_rate . "</span></p>";
                    echo "<p class='mb-1 fw-normal'>Total: <span class='fw-bold'>" . $total_serv_count . "</span></p>";
                    echo "</div>";
                ?>
                </div>
                <a class="d-block text-center mt-2 text-white bg-dark p-1 rounded" href="customerServiceList.php" style="text-decoration: none; font-size: 1.1rem;">
                    <i class="fas fa-angle-right"></i> Full Details
                </a>
            </div>
        </div>

        <!-- Best Selling Product -->
        <div class="col-md-3 mb-3">
            <div class="card-body text-size p-2 bg-success shadow-sm rounded">
                <h5 class="text-center mb-2 text-white">Best Selling Product</h5>
                <div class="card-footer d-flex flex-column align-items-center text-black">
                <?php
                    // Fetch the best-selling product
                    $Tprod_query = "SELECT order_items.prodName, 
                                    SUM(order_items.quantity) AS total_quantity,
                                    products.prodImg,
                                    products.prodprice
                                    FROM order_items
                                    INNER JOIN orders ON order_items.orderNo = orders.orderNo
                                    INNER JOIN products ON order_items.prodNo = products.prodNo
                                    WHERE orders.status = 'Order Delivered'
                                    GROUP BY order_items.prodName
                                    ORDER BY total_quantity DESC LIMIT 1";
                    $stmt_Tprod = $con->prepare($Tprod_query);
                    $stmt_Tprod->execute();
                    $result_Tprod = $stmt_Tprod->get_result();
                    $row_Tprod = $result_Tprod->fetch_assoc();
                    $stmt_Tprod->close();

                    $prod_name = $row_Tprod['prodName'] ?? 'None';
                    $total_quantity = $row_Tprod['total_quantity'] ?? 'None';
                    $prod_image_data = $row_Tprod['prodImg'] ?? null;
                    $prod_price = $row_Tprod['prodprice'] ?? 'None';

                    // Check if image data is null and set the appropriate value
                    if ($prod_image_data === null) {
                        $prod_image_base64 = 'None';
                    } else {
                        $prod_image_base64 = base64_encode($prod_image_data);
                    }

                    echo "<div class='text-center mb-2'>";
                    // Display image or 'None' if no image
                    if ($prod_image_base64 === 'None') {
                        echo "<p>None</p>";
                    } else {
                        echo "<img src='data:image/jpeg;base64," . $prod_image_base64 . "' alt='" . $prod_name . "' class='img-fluid rounded' style='width: 60px; height: 60px; object-fit: cover;'>";
                    }
                    echo "</div>";
                    echo "<div class='text-center'>";
                    echo "<p class='mb-1 fw-bold'>Product: <span class='fw-normal'>" . $prod_name . "</span></p>";
                    echo "<p class='mb-1 fw-bold'>Price: <span class='fw-normal'>" . $prod_price . "</span></p>";
                    echo "<p class='mb-1 fw-normal'>Sold: <span class='fw-bold'>" . $total_quantity . "</span></p>";
                    echo "</div>";
                ?>
                </div>
                <a class="d-block text-center mt-2 text-white bg-dark p-1 rounded" href="products_customer.php" style="text-decoration: none; font-size: 1.1rem;">
                    <i class="fas fa-angle-right"></i> Full Details
                </a>
            </div>
        </div>


</body>
</html>

