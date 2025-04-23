<?php
$page_title = "Staff Sales Report";
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

$currentMonth = date('n');
$currentYear = date('Y');

$transaction = $_POST['transaction'] ?? '';
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? $currentYear;
$week = $_POST['week'] ?? '';
$reportSale = 0;
$error = null;

// Handle the form submission for generating the report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    if ($transaction && $month && $year && $week) {
        $startDate = date('Y-m-d', strtotime("$year-$month-01"));
        $startDateWeek = date('Y-m-d', strtotime($startDate . " + " . (($week - 1) * 7) . " days"));
        $endDateWeek = date('Y-m-d', strtotime($startDateWeek . " + 6 days"));

        if ($transaction == 'Order') {
            // Payment query for Order transactions with non-null orderNo
            $paymentQuery = "
                SELECT SUM(CASE 
                WHEN paymentStatus IN ('COD Paid', 'Paid') THEN payable
                WHEN paymentStatus = 'Partially Paid' THEN (payable + payable) / 2
                ELSE 0 
              END) AS total_sales
    FROM payment
    WHERE orderNo IS NOT NULL
    AND staffId = ? 
    AND paymentDate BETWEEN ? AND ? 
";
        } elseif ($transaction == 'Service') {
            // Payment query for Service transactions with non-null pendservice
            $paymentQuery = "
                SELECT SUM(CASE 
                WHEN paymentStatus IN ('COD Paid', 'Paid') THEN payable
                WHEN paymentStatus = 'Partially Paid' THEN (payable + payable) / 2
                ELSE 0 
              END) AS total_sales
    FROM payment
    WHERE pendservice IS NOT NULL
    AND staffId = ? 
    AND paymentDate BETWEEN ? AND ? 
";
        }
        if ($stmt = $con->prepare($paymentQuery)) {
            $stmt->bind_param("iss", $staffId, $startDateWeek, $endDateWeek);
            $stmt->execute();
            $stmt->bind_result($totalSales);
            $stmt->fetch();
            $stmt->close();

            if ($totalSales === NULL) {
                $totalSales = 0;
            }

            $insertQuery = "
                INSERT INTO branch_report (staffId, weekNo, month, year, reportSale, reportDate, transaction)
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ";

            if ($stmtInsert = $con->prepare($insertQuery)) {
                $stmtInsert->bind_param("iiisss", $staffId, $week, $month, $year, $totalSales, $transaction);
                if ($stmtInsert->execute()) {
                    $successMessage = "Report generated successfully.";
                } else {
                    $error = "Error inserting the report into the database.";
                }
                $stmtInsert->close();
            } else {
                $error = "Error preparing the insert query.";
            }
        } else {
            $error = "Error fetching sales data from the database.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch sales data for the bar graph
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
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
</head>
<body>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <form action="" method="post" id="salesReportForm">
                <!-- Transaction Type Dropdown -->
                <div class="form-group">
                    <label for="transaction">Transaction Type:</label>
                    <select name="transaction" id="transaction" class="form-control" required>
                        <option value="">--Select Transaction--</option>
                        <option value="Service" <?= ($transaction == 'Service') ? 'selected' : '' ?>>Service</option>
                        <option value="Order" <?= ($transaction == 'Order') ? 'selected' : '' ?>>Order</option>
                    </select>
                </div>

                <!-- Year Selection -->
                <div class="form-group">
                    <label for="year">Year:</label>
                    <input type="number" name="year" id="year" class="form-control" value="<?= $year ?>" required>
                </div>

                <!-- Month Selection -->
                <div class="form-group">
                    <label for="month">Month:</label>
                    <select name="month" id="month" class="form-control" required>
                        <?php foreach ($months as $index => $monthName) : ?>
                            <option value="<?= $index + 1 ?>" <?= ($month == $index + 1) ? 'selected' : '' ?>>
                                <?= $monthName ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Week Selection -->
                <div class="form-group">
                    <label for="week">Week:</label>
                    <input type="number" name="week" id="week" class="form-control" value="<?= $week ?>" required>
                </div>

                <button type="submit" name="generate_report" class="btn btn-primary mt-3">Generate Report</button>
            </form>
        </div>
    </div>

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

</body>
</html>