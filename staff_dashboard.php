<?php
$page_title = "Staff Dashboard";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

$userId = $_SESSION['userId'] ?? null;
$staffId = null;

// Fetch the staffId for the logged-in user
if ($userId) {
    $query = "SELECT staffId FROM staffs WHERE userId = ?";
    if ($stmt = $con->prepare($query)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($staffId);
        $stmt->fetch();
        $stmt->close();
    } else {
        $error = "Error fetching staffId from the database.";
    }
}

    //Get the count of the orders
    $cOrder_query = "SELECT COUNT(*) AS total_orders FROM orders WHERE assignedStaff = ?";
    $stmt_cOrder = $con->prepare($cOrder_query);
    $stmt_cOrder->bind_param("i", $staffId);
    $stmt_cOrder->execute();
    $result_cOrders = $stmt_cOrder->get_result();

    // //Get the count of the tickets
    $cTick_query = "SELECT COUNT(*) AS total_tick FROM ticket";
    $stmt_cTick = $con->prepare($cTick_query);
    $stmt_cTick->execute();
    $result_cTick = $stmt_cTick->get_result();

    //Get the count of the service requests
    $cServ_query = "SELECT COUNT(*) AS total_serv FROM acceptserv2 WHERE staffId = ?";
    $stmt_cServ = $con->prepare($cServ_query);
    $stmt_cServ->bind_param("i", $staffId);
    $stmt_cServ->execute();
    $result_cServ = $stmt_cServ->get_result();


    //Get the count of all the users
    $cUsers_query ="SELECT COUNT(*) AS total_users FROM users WHERE role = 'customer'";
    $stmt_cUsers = $con->prepare($cUsers_query);
    $stmt_cUsers->execute();
    $result_cUsers = $stmt_cUsers->get_result();

    //Count of the denied service request

    $cDec_query = "SELECT COUNT(*) AS total_decline FROM declined_reqserv";
    $stmt_cDec = $con->prepare($cDec_query);
    $stmt_cDec->execute();
    $result_cDec = $stmt_cDec->get_result();

    //Count of the accepted service request
    $cAcc_query = "SELECT COUNT(*) AS total_accept FROM acceptserv2 WHERE staffId = ?";
    $stmt_cAcc = $con->prepare($cAcc_query);
    $stmt_cAcc->bind_param("i", $staffId);
    $stmt_cAcc->execute();
    $result_cAcc = $stmt_cAcc->get_result();

    $graphData = [];
    $graphQuery = "
        SELECT year, month, weekNo, 
               SUM(CASE WHEN transaction = 'Order' THEN reportSale ELSE 0 END) AS order_sales,
               SUM(CASE WHEN transaction = 'Service' THEN reportSale ELSE 0 END) AS service_sales,
               SUM(reportSale) AS weekly_sales
        FROM branch_report
        WHERE staffId = ?
        GROUP BY year, month, weekNo
        ORDER BY year DESC, month, weekNo
    ";
    
    if ($stmt = $con->prepare($graphQuery)) {
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
        $stmt->bind_result($year, $month, $weekNo, $orderSales, $serviceSales, $weeklySales);
    
        while ($stmt->fetch()) {
            $graphData[] = [
                'weekNo' => $weekNo,
                'month' => $month,
                'year' => $year,
                'order_sales' => $orderSales,
                'service_sales' => $serviceSales,
                'weekly_sales' => $weeklySales
            ];
        }
        $stmt->close();
    }
    
    $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    $weeklySalesData = [];
    $monthlySalesData = [];
    $yearlySalesData = [];
    $weeklyLabels = [];
    $monthlyLabels = [];
    $orderSalesData = [];
    $serviceSalesData = [];
    $yearlyLabels = [];
    
    // Prepare weekly, monthly, and yearly data
    foreach ($graphData as $data) {
        // Weekly Data
        $weekLabel = "Week " . $data['weekNo'] . " (" . $months[$data['month'] - 1] . " " . $data['year'] . ")";
        $weeklyLabels[] = $weekLabel;
        $weeklySalesData[] = $data['weekly_sales'] ?? 0;
        $orderSalesData[] = $data['order_sales'] ?? 0;
        $serviceSalesData[] = $data['service_sales'] ?? 0;
    
        // Monthly Data
        $monthLabel = $months[$data['month'] - 1] . " " . $data['year'];
        $monthlyLabels[] = $monthLabel;
        if (!isset($monthlySalesData[$monthLabel])) {
            $monthlySalesData[$monthLabel] = ['order_sales' => 0, 'service_sales' => 0];
        }
        $monthlySalesData[$monthLabel]['order_sales'] += $data['order_sales'] ?? 0;
        $monthlySalesData[$monthLabel]['service_sales'] += $data['service_sales'] ?? 0;
    
        // Yearly Data
        $yearLabel = $data['year'];
        if (!in_array($yearLabel, $yearlyLabels)) {
            $yearlyLabels[] = $yearLabel;
        }
        if (!isset($yearlySalesData[$yearLabel])) {
            $yearlySalesData[$yearLabel] = ['order_sales' => 0, 'service_sales' => 0];
        }
        $yearlySalesData[$yearLabel]['order_sales'] += $data['order_sales'] ?? 0;
        $yearlySalesData[$yearLabel]['service_sales'] += $data['service_sales'] ?? 0;
    }
    
    $monthlySalesDataFlattened = array_map(function ($data) {
        return $data['order_sales']; // For Order Sales
    }, $monthlySalesData);
    
    $monthlyServiceSalesDataFlattened = array_map(function ($data) {
        return $data['service_sales']; // For Service Sales
    }, $monthlySalesData);
    
    $monthlyLabelsFlattened = array_keys($monthlySalesData);
    
    $yearlyOrderSalesData = array_map(function ($data) {
        return $data['order_sales'];
    }, $yearlySalesData);
    
    $yearlyServiceSalesData = array_map(function ($data) {
        return $data['service_sales'];
    }, $yearlySalesData);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
</head>
<body>
<div class="row mt-4">
        <!-- Weekly Sales Chart -->
        <div class="col-md-4">
            <h4>Weekly Sales Chart</h4>
            <canvas id="weeklySalesChart"></canvas>
        </div>

        <!-- Monthly Sales Chart -->
        <div class="col-md-4">
            <h4>Monthly Sales Chart</h4>
            <canvas id="monthlySalesChart"></canvas>
        </div>

        <!-- Yearly Sales Chart -->
        <div class="col-md-4">
            <h4>Yearly Sales Chart</h4>
            <canvas id="yearlySalesChart"></canvas>
        </div>
    </div>
<script>
// Weekly Sales Chart
new Chart(document.getElementById("weeklySalesChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode($weeklyLabels) ?>,
        datasets: [
            {
                label: "Order Sales",
                backgroundColor: "rgba(40, 167, 69, 0.7)",
                data: <?= json_encode($orderSalesData) ?>
            },
            {
                label: "Service Sales",
                backgroundColor: "rgba(220, 53, 69, 0.7)",
                data: <?= json_encode($serviceSalesData) ?>
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                color: 'white',
                font: {
                    weight: 'bold'
                },
                anchor: 'end',
                align: 'start'
            }
        }
    }
});

// Monthly Sales Chart
new Chart(document.getElementById("monthlySalesChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode($monthlyLabelsFlattened) ?>,
        datasets: [
            {
                label: "Order Sales",
                backgroundColor: "rgba(0, 123, 255, 0.7)",
                data: <?= json_encode($monthlySalesDataFlattened) ?>
            },
            {
                label: "Service Sales",
                backgroundColor: "rgba(40, 167, 69, 0.7)",
                data: <?= json_encode($monthlyServiceSalesDataFlattened) ?>
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                color: 'white',
                font: {
                    weight: 'bold'
                },
                anchor: 'end',
                align: 'start'
            }
        }
    }
});

// Yearly Sales Chart
new Chart(document.getElementById("yearlySalesChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode($yearlyLabels) ?>,
        datasets: [
            {
                label: "Order Sales",
                backgroundColor: "rgba(0, 123, 255, 0.7)",
                data: <?= json_encode($yearlyOrderSalesData) ?>
            },
            {
                label: "Service Sales",
                backgroundColor: "rgba(40, 167, 69, 0.7)",
                data: <?= json_encode($yearlyServiceSalesData) ?>
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            datalabels: {
                color: 'white',
                font: {
                    weight: 'bold'
                },
                anchor: 'end',
                align: 'start'
            }
        }
    }
});
</script>
    <div class="py-5">
        <div class="container">
            <div class="row d-flex justify-content-around ">
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-primary bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Total Requests Services</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                if($result_cServ->num_rows > 0){
                                    $row = $result_cServ->fetch_assoc();
                                    echo $row["total_serv"];
                                }else{
                                    echo "0 results.";
                                }

                                ?>
                                </div>
                                <a class="medium text-white" href="staff_serviceRequest.php"><i class="fas fa-angle-right">Full Details</i></a>
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
                                if($result_cAcc->num_rows > 0){
                                    $row = $result_cAcc->fetch_assoc();
                                    echo $row["total_accept"];
                                }else{
                                    echo "0 results.";
                                }
                                ?>
                                </div>
                                <a class="medium text-white" href="staff_acceptedService.php"><i class="fas fa-angle-right">Full Details</i></a>
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
                                if($result_cDec->num_rows > 0){
                                    $row = $result_cDec->fetch_assoc();
                                    echo $row["total_decline"];
                                }else{
                                    echo "0 results.";
                                }
                                ?>
                                </div>
                                <a class="medium text-white" href="staff_servdecline.php"><i class="fas fa-angle-right">Full Details</i></a>
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
                                    }else{
                                        echo "0 results.";
                                    }

                                ?>
                                </div>
                                <a class="medium text-white" href="staff_tickets.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-dark bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Total Customers</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                    if($result_cUsers->num_rows > 0){
                                        $row = $result_cUsers->fetch_assoc();
                                        echo $row["total_users"];

                                    }
                                    else{
                                        echo "0 results.";
                                    }
                                ?>
                                </div>
                                <a class="medium text-white" href="staff_custAccounts.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-secondary bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center">Total Orders</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2">
                                <?php
                                    if($result_cOrders->num_rows > 0){
                                        $row = $result_cOrders->fetch_assoc();
                                        echo $row["total_orders"];
                                    }else{
                                        echo "0 results.";
                                    }
                                
                                ?>
                                </div>
                                <a class="medium text-white" href="staff_orders.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row justify-content-between">
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

        <!-- Least Requested Service -->
        <div class="col-md-3 mb-3">
            <div class="card-body text-size p-2 bg-danger shadow-sm rounded">
                <h5 class="text-center mb-2 text-white">Least Requested Service</h5>
                <div class="card-footer d-flex flex-column align-items-center text-white">
                    <?php
                        // Fetch the least requested service
                        $Tserv_query = "SELECT 
                                        rs.servName AS rsName,
                                        IFNULL(COUNT(rs.servName), 0) AS serv_trend, 
                                        s.servImg, 
                                        s.servName, 
                                        s.rateService
                                    FROM 
                                        services s
                                    LEFT JOIN
                                        reqserv_service rs ON s.servCode = rs.servCode
                                    LEFT JOIN 
                                        reqserv r ON rs.reqserv = r.reqserv AND r.servStatus = 'Service Completed'
                                    GROUP BY 
                                        s.servName
                                    ORDER BY 
                                        serv_trend ASC LIMIT 1";

                        $stmt_Tserv = $con->prepare($Tserv_query);
                        $stmt_Tserv->execute();
                        $result_Tserv = $stmt_Tserv->get_result();

                        // Fetch the results
                        while ($row_Tserv = $result_Tserv->fetch_assoc()) {
                            $servCount = $row_Tserv['serv_trend'];  
                            $servImg = $row_Tserv['servImg'];
                            $servName = $row_Tserv['servName'];
                            $servRate = $row_Tserv['rateService'];

                            // If the service has not been requested, servCount will be 0
                            if ($servImg) {
                                $servImgBase64 = base64_encode($servImg);
                                echo "<div class='text-center mb-2'>";
                                echo "<img src='data:image/jpeg;base64," . $servImgBase64 . "' alt='" . $servName . "' class='img-fluid rounded' style='width: 60px; height: 60px; object-fit: cover;'>";
                                echo "</div>";
                            }
                        }
                    ?>
                </div>
                <div class="text-center">
                    <p class="mb-1 fw-bold">Service: <span class="fw-normal"><?php echo $servName; ?></span></p>
                    <p class="mb-1 fw-bold">Rate: <span class="fw-normal"><?php echo $servRate; ?></span></p>
                    <p class="mb-1 fw-normal">Total: <span class="fw-bold"><?php echo $servCount; ?></span></p>
                </div>
                <a class="d-block text-center mt-2 text-white bg-dark p-1 rounded" href="admin_services.php" style="text-decoration: none; font-size: 1.1rem;">
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
                <a class="d-block text-center mt-2 text-white bg-dark p-1 rounded" href="staff_products.php" style="text-decoration: none; font-size: 1.1rem;">
                    <i class="fas fa-angle-right"></i> Full Details
                </a>
            </div>
        </div>

        <!-- Least Purchased Product -->
        <div class="col-md-3 mb-3">
            <div class="card-body text-size p-2 bg-danger shadow-sm rounded">
                <h5 class="text-center mb-2 text-white">Least Purchased Product</h5>
                <div class="card-footer d-flex flex-column align-items-center text-black">
                    <?php
                        // Fetch the least purchased product
                        $Tprod_query = "SELECT 
                                            products.prodName, 
                                            IFNULL(SUM(order_items.quantity), 0) AS total_quantity, 
                                            products.prodImg, 
                                            products.prodprice
                                        FROM products
                                        LEFT JOIN order_items ON products.prodNo = order_items.prodNo
                                        LEFT JOIN orders ON order_items.orderNo = orders.orderNo
                                            AND orders.status = 'Order Delivered' 
                                        GROUP BY products.prodNo
                                        ORDER BY total_quantity ASC
                                        LIMIT 1";

                        $stmt_Tprod = $con->prepare($Tprod_query);
                        $stmt_Tprod->execute();
                        $result_Tprod = $stmt_Tprod->get_result();
                        $row_Tprod = $result_Tprod->fetch_assoc();
                        $stmt_Tprod->close();

                        $prod_name = $row_Tprod['prodName'];
                        $total_quantity = $row_Tprod['total_quantity'];
                        $prod_image_data = $row_Tprod['prodImg'];
                        $prod_price = $row_Tprod['prodprice'];

                        $prod_image_base64 = base64_encode($prod_image_data);

                        echo "<div class='text-center mb-2'>";
                        echo "<img src='data:image/jpeg;base64," . $prod_image_base64 . "' alt='" . $prod_name . "' class='img-fluid rounded' style='width: 60px; height: 60px; object-fit: cover;'>";
                        echo "</div>";
                        echo "<div class='text-center'>";
                        echo "<p class='mb-1 fw-bold'>Product: <span class='fw-normal'>" . $prod_name . "</span></p>";
                        echo "<p class='mb-1 fw-bold'>Price: <span class='fw-normal'>" . $prod_price . "</span></p>";
                        echo "<p class='mb-1 fw-normal'>Sold: <span class='fw-bold'>" . $total_quantity . "</span></p>";
                        echo "</div>";
                    ?>
                </div>
                <a class="d-block text-center mt-2 text-white bg-dark p-1 rounded" href="staff_products.php" style="text-decoration: none; font-size: 1.1rem;">
                    <i class="fas fa-angle-right"></i> Full Details
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

