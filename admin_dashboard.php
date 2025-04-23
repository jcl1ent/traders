<?php
$page_title = "Admin Dashboard";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

    //Get the count of the orders
    $cOrder_query = "SELECT COUNT(*) AS total_orders FROM orders";
    $stmt_cOrder = $con->prepare($cOrder_query);
    $stmt_cOrder->execute();
    $result_cOrders = $stmt_cOrder->get_result();

    // //Get the count of the tickets
    $cTick_query = "SELECT COUNT(*) AS total_tick FROM ticket";
    $stmt_cTick = $con->prepare($cTick_query);
    $stmt_cTick->execute();
    $result_cTick = $stmt_cTick->get_result();

    //Get the count of the service requests
    $cServ_query = "SELECT COUNT(*) AS total_serv FROM reqserv";
    $stmt_cServ = $con->prepare($cServ_query);
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

    //Accepted Service request
    $cAcc_query = "SELECT COUNT(*) AS total_accept FROM acceptserv2";
    $stmt_cAcc = $con->prepare($cAcc_query);
    $stmt_cAcc->execute();
    $result_cAcc = $stmt_cAcc->get_result();

// Fetch sales data for the bar graph (weekly sales)
$graphData = [];
$graphQuery = "
    SELECT year, month, weekNo, transaction, SUM(reportSale) AS weekly_sales
    FROM branch_report
    GROUP BY year, month, weekNo, transaction
    ORDER BY year DESC, month, weekNo
";

if ($stmt = $con->prepare($graphQuery)) {
    $stmt->execute();
    $stmt->bind_result($year, $month, $weekNo, $transactionType, $weeklySales);

    while ($stmt->fetch()) {
        $graphData[] = [
            'year' => $year,
            'month' => $month,
            'weekNo' => $weekNo,
            'transaction' => $transactionType,
            'weekly_sales' => $weeklySales
        ];
    }
    $stmt->close();
}

// Fetch sales data for the bar graph (weekly sales)
$graphData = [];
$graphQuery = "
    SELECT year, month, weekNo, transaction, SUM(reportSale) AS weekly_sales
    FROM branch_report
    GROUP BY year, month, weekNo, transaction
    ORDER BY year DESC, month, weekNo
";

if ($stmt = $con->prepare($graphQuery)) {
    $stmt->execute();
    $stmt->bind_result($year, $month, $weekNo, $transactionType, $weeklySales);

    while ($stmt->fetch()) {
        $graphData[] = [
            'year' => $year,
            'month' => $month,
            'weekNo' => $weekNo,
            'transaction' => $transactionType,
            'weekly_sales' => $weeklySales
        ];
    }
    $stmt->close();
}

// Fetch monthly sales data from company_report table
$monthlyGraphData = [];
$monthlyGraphQuery = "
    SELECT year, month, transaction, MonthlySales AS monthly_sales
    FROM company_report
    GROUP BY year, month, transaction
    ORDER BY year DESC, month
";

if ($stmt = $con->prepare($monthlyGraphQuery)) {
    $stmt->execute();
    $stmt->bind_result($year, $month, $transactionType, $monthlySales);

    while ($stmt->fetch()) {
        $monthlyGraphData[] = [
            'year' => $year,
            'month' => $month,
            'transaction' => $transactionType,
            'monthly_sales' => $monthlySales
        ];
    }
    $stmt->close();
}

// Fetch yearly sales data
$yearlyGraphData = []; // Initialize as empty array to avoid warnings
$yearlyGraphQuery = "
    SELECT year, transaction, SUM(MonthlySales) AS yearly_sales
    FROM company_report
    GROUP BY year, transaction
    ORDER BY year DESC
";

if ($stmt = $con->prepare($yearlyGraphQuery)) {
    $stmt->execute();
    $stmt->bind_result($year, $transactionType, $yearlySales);

    while ($stmt->fetch()) {
        $yearlyGraphData[] = [
            'year' => $year,
            'transaction' => $transactionType,
            'yearly_sales' => $yearlySales
        ];
    }
    $stmt->close();
}

$months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$weeklyLabels = [];
$orderSalesData = [];
$serviceSalesData = [];
$monthlyLabels = [];
$monthlyOrderSalesData = [];
$monthlyServiceSalesData = [];
$yearlyLabels = [];
$yearlyOrderSalesData = [];
$yearlyServiceSalesData = [];

// Prepare weekly data
foreach ($graphData as $data) {
    $weekLabel = "Week " . $data['weekNo'] . " (" . $months[$data['month'] - 1] . " " . $data['year'] . ")";
    $weeklyLabels[] = $weekLabel;

    if ($data['transaction'] == 'Order') {
        $orderSalesData[] = $data['weekly_sales'] ?? 0;
        $serviceSalesData[] = 0;
    } elseif ($data['transaction'] == 'Service') {
        $serviceSalesData[] = $data['weekly_sales'] ?? 0;
        $orderSalesData[] = 0;
    }
}

// Prepare monthly data
foreach ($monthlyGraphData as $data) {
    $monthLabel = $months[$data['month'] - 1] . " " . $data['year'];
    $monthlyLabels[] = $monthLabel;

    if ($data['transaction'] == 'Order') {
        $monthlyOrderSalesData[] = $data['monthly_sales'] ?? 0;
        $monthlyServiceSalesData[] = 0;
    } elseif ($data['transaction'] == 'Service') {
        $monthlyServiceSalesData[] = $data['monthly_sales'] ?? 0;
        $monthlyOrderSalesData[] = 0;
    }
}

// Prepare yearly data
foreach ($yearlyGraphData as $data) {
    $yearlyLabels[] = $data['year'];

    if ($data['transaction'] == 'Order') {
        $yearlyOrderSalesData[] = $data['yearly_sales'] ?? 0;
        $yearlyServiceSalesData[] = 0;
    } elseif ($data['transaction'] == 'Service') {
        $yearlyServiceSalesData[] = $data['yearly_sales'] ?? 0;
        $yearlyOrderSalesData[] = 0;
    }
}
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
      <!-- Chart.js Bar Graph -->
<div class="row mt-4">
    <div class="col-md-4">
        <h4>Weekly Sales Chart</h4>
        <canvas id="weeklySalesChart"></canvas>
    </div>
    <div class="col-md-4">
        <h4>Monthly Sales Chart</h4>
        <canvas id="monthlySalesChart"></canvas>
    </div>
    <div class="col-md-4">
        <h4>Yearly Sales Chart</h4>
        <canvas id="yearlySalesChart"></canvas>
    </div>
</div>
<script>
    // Weekly Sales Chart
    const weeklySalesChartCtx = document.getElementById('weeklySalesChart').getContext('2d');
    new Chart(weeklySalesChartCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($weeklyLabels) ?>,
            datasets: [
                {
                    label: 'Order Sales',
                    data: <?= json_encode($orderSalesData) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',  // Brighter color for Order Sales
                    borderColor: 'rgba(255, 99, 132, 1)',  // Darker border color for contrast
                    borderWidth: 2
                },
                {
                    label: 'Service Sales',
                    data: <?= json_encode($serviceSalesData) ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.8)',  // Brighter color for Service Sales
                    borderColor: 'rgba(153, 102, 255, 1)',  // Darker border color for contrast
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,  // Ensures the Y-axis starts at zero
                    ticks: {
                        font: {
                            size: 14,  // Larger font size for better readability
                            weight: 'bold'
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 14,  // Larger font size for better readability
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                datalabels: {
                    color: 'white',
                    anchor: 'end',
                    align: 'top',
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                }
            }
        }
    });

    // Monthly Sales Chart
    const monthlySalesChartCtx = document.getElementById('monthlySalesChart').getContext('2d');
    new Chart(monthlySalesChartCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [
                {
                    label: 'Order Sales',
                    data: <?= json_encode($monthlyOrderSalesData) ?>,
                    backgroundColor: 'rgba(255, 159, 64, 0.8)',  // Brighter color for Order Sales
                    borderColor: 'rgba(255, 159, 64, 1)',  // Darker border color for contrast
                    borderWidth: 2
                },
                {
                    label: 'Service Sales',
                    data: <?= json_encode($monthlyServiceSalesData) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',  // Brighter color for Service Sales
                    borderColor: 'rgba(75, 192, 192, 1)',  // Darker border color for contrast
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,  // Ensures the Y-axis starts at zero
                    ticks: {
                        font: {
                            size: 14,  // Larger font size for better readability
                            weight: 'bold'
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 14,  // Larger font size for better readability
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                datalabels: {
                    color: 'white',
                    anchor: 'end',
                    align: 'top',
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                }
            }
        }
    });

    // Yearly Sales Chart
    const yearlySalesChartCtx = document.getElementById('yearlySalesChart').getContext('2d');
    new Chart(yearlySalesChartCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($yearlyLabels) ?>,
            datasets: [
                {
                    label: 'Order Sales',
                    data: <?= json_encode($yearlyOrderSalesData) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',  // Brighter color for Order Sales
                    borderColor: 'rgba(255, 99, 132, 1)',  // Darker border color for contrast
                    borderWidth: 2
                },
                {
                    label: 'Service Sales',
                    data: <?= json_encode($yearlyServiceSalesData) ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.8)',  // Brighter color for Service Sales
                    borderColor: 'rgba(153, 102, 255, 1)',  // Darker border color for contrast
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,  // Ensures the Y-axis starts at zero
                    ticks: {
                        font: {
                            size: 14,  // Larger font size for better readability
                            weight: 'bold'
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 14,  // Larger font size for better readability
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                datalabels: {
                    color: 'white',
                    anchor: 'end',
                    align: 'top',
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
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
                                <a class="medium text-white" href="admin_service.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-success bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Accepted Service Request</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                if($result_cAcc->num_rows > 0){
                                    $row = $result_cAcc->fetch_assoc();
                                    echo $row["total_accept"];
                                }else{
                                    echo "0 results.";
                                }
                                ?>
                                </div>
                                <a class="medium text-white" href="admin_acceptedService.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-danger bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Denied Service Request</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                if($result_cDec->num_rows > 0){
                                    $row = $result_cDec->fetch_assoc();
                                    echo $row["total_decline"];
                                }else{
                                    echo "0 results.";
                                }
                                ?>
                                </div>
                                <a class="medium text-white" href="admin_declineService.php"><i class="fas fa-angle-right">Full Details</i></a>
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
                                <a class="medium text-white" href="admin_tickets.php"><i class="fas fa-angle-right">Full Details</i></a>
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
                                <a class="medium text-white" href="admin_userAccounts.php"><i class="fas fa-angle-right">Full Details</i></a>
                            </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-3 ms-3 pt-4">
                    <div class="card shadow bg-secondary bg-gradient mb-4">
                        <div class="card-header">
                            <h5 class="text-center text-white">Total Orders</h5>
                        </div>
                            <div class="card-body text-size">
                                <div class="card-footer d-flex align-items-center justify-content-center fs-2 text-white">
                                <?php
                                    if($result_cOrders->num_rows > 0){
                                        $row = $result_cOrders->fetch_assoc();
                                        echo $row["total_orders"];
                                    }else{
                                        echo "0 results.";
                                    }
                                
                                ?>
                                </div>
                                <a class="medium text-white" href="admin_orders.php"><i class="fas fa-angle-right">Full Details</i></a>
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
                <a class="d-block text-center mt-2 text-white bg-dark p-1 rounded" href="admin_products.php" style="text-decoration: none; font-size: 1.1rem;">
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
                <a class="d-block text-center mt-2 text-white bg-dark p-1 rounded" href="admin_products.php" style="text-decoration: none; font-size: 1.1rem;">
                    <i class="fas fa-angle-right"></i> Full Details
                </a>
            </div>
        </div>
    </div>
</div>


</body>
</html>

