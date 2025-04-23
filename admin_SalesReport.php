<?php
$page_title = "Admin Sales Report";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

$currentMonth = date('n');
$currentYear = date('Y');

$transaction = $_POST['transaction'] ?? '';
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? $currentYear;
$week = $_POST['week'] ?? '';
$error = null;
$successMessage = '';

// Handle form submission for generating the report
if (isset($_POST['generate_report'])) {
    if ($transaction && $month && $year) {
        $reportNoQuery = "
            SELECT ReportNo
            FROM branch_report
            WHERE year = ? AND month = ? AND transaction = ?
            LIMIT 1
        ";

        if ($stmtReportNo = $con->prepare($reportNoQuery)) {
            $stmtReportNo->bind_param("iis", $year, $month, $transaction);
            $stmtReportNo->execute();
            $stmtReportNo->bind_result($ReportNo);
            $stmtReportNo->fetch();
            $stmtReportNo->close();
        } else {
            $error = "Error preparing ReportNo query: " . $con->error;
        }

        if ($ReportNo === null) {
            $error = "No report found for the given parameters.";
        } else {
            $monthlySalesQuery = "
                SELECT SUM(reportSale) AS monthly_sales
                FROM branch_report
                WHERE year = ? AND month = ? AND transaction = ?
                GROUP BY year, month, transaction
            ";

            if ($stmtMonthlySales = $con->prepare($monthlySalesQuery)) {
                $stmtMonthlySales->bind_param("iis", $year, $month, $transaction);
                $stmtMonthlySales->execute();
                $stmtMonthlySales->bind_result($monthlySales);
                $stmtMonthlySales->fetch();
                $stmtMonthlySales->close();
            } else {
                $error = "Error preparing monthly sales query: " . $con->error;
            }

            if ($monthlySales === null) {
                $error = "No sales found for the given parameters.";
            } else {
                $insertQuery = "
                    INSERT INTO company_report (ReportNo, transaction, year, month, MonthlySales, createDate)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ";

                if ($stmtInsert = $con->prepare($insertQuery)) {
                    $stmtInsert->bind_param("ssisi", $ReportNo, $transaction, $year, $month, $monthlySales);
                    if ($stmtInsert->execute()) {
                        ;
                    } else {
                        $error = "Error generating report: " . $stmtInsert->error;
                    }
                    $stmtInsert->close();
                } else {
                    $error = "Error preparing the insert query: " . $con->error;
                }
            }
        }
    } else {
        $error = "All fields are required!";
    }
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

$branchGraphData = [];
$branchWeeklySalesQuery = "
    SELECT br.year, br.month, br.weekNo, br.transaction, br.staffId, s.branch, SUM(br.reportSale) AS weekly_sales
    FROM branch_report br
    JOIN staffs s ON br.staffId = s.staffId
    GROUP BY br.year, br.month, br.weekNo, br.transaction, s.branch
    ORDER BY br.year DESC, br.month, br.weekNo
";

if ($stmt = $con->prepare($branchWeeklySalesQuery)) {
    $stmt->execute();
    $stmt->bind_result($year, $month, $weekNo, $transactionType, $staffId, $branch, $weeklySales);

    while ($stmt->fetch()) {
        $branchGraphData[] = [
            'year' => $year,
            'month' => $month,
            'weekNo' => $weekNo,
            'transaction' => $transactionType,
            'branch' => $branch,
            'weekly_sales' => $weeklySales
        ];
    }
    $stmt->close();

    
}

// Prepare branch data for the graph
$branchLabels = [];
$branchOrderSalesData = [];
$branchServiceSalesData = [];

foreach ($branchGraphData as $data) {
    $weekLabel = "Week " . $data['weekNo'] . " (" . $months[$data['month'] - 1] . " " . $data['year'] . ")";
    $branchLabels[] = $data['branch'] . " - " . $weekLabel;

    if ($data['transaction'] == 'Order') {
        $branchOrderSalesData[] = $data['weekly_sales'] ?? 0;
        $branchServiceSalesData[] = 0;
    } elseif ($data['transaction'] == 'Service') {
        $branchServiceSalesData[] = $data['weekly_sales'] ?? 0;
        $branchOrderSalesData[] = 0;
    }
}
$branchMonthlySalesData = [];
$branchMonthlySalesQuery = "
    SELECT b.year, b.month, b.transaction, SUM(b.reportSale) AS monthly_sales, s.branch
    FROM branch_report b
    JOIN staffs s ON s.staffId = b.staffId
    WHERE b.year = ? AND b.month = ?
    GROUP BY b.year, b.month, b.transaction, s.branch
    ORDER BY s.branch
";

if ($stmt = $con->prepare($branchMonthlySalesQuery)) {
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $stmt->bind_result($year, $month, $transactionType, $monthlySales, $branchName);

    while ($stmt->fetch()) {
        $branchMonthlySalesData[$branchName][$transactionType][] = $monthlySales;
    }
    $stmt->close();
}
$branchLabels = [];
$branchOrderSalesData = [];
$branchServiceSalesData = [];

foreach ($branchMonthlySalesData as $branchName => $salesData) {
    $branchLabels[] = $branchName; // Label for each branch
    // Sum the monthly sales for Order and Service
    $branchOrderSalesData[] = isset($salesData['Order']) ? array_sum($salesData['Order']) : 0;
    $branchServiceSalesData[] = isset($salesData['Service']) ? array_sum($salesData['Service']) : 0;
}

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
                <div class="form-group">
                    <label for="transaction">Transaction Type:</label>
                    <select name="transaction" id="transaction" class="form-control" required>
                        <option value="">--Select Transaction--</option>
                        <option value="Service" <?= ($transaction == 'Service') ? 'selected' : '' ?>>Service</option>
                        <option value="Order" <?= ($transaction == 'Order') ? 'selected' : '' ?>>Order</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year">Year:</label>
                    <select name="year" id="year" class="form-control" required>
                        <option value="">--Year--</option>
                        <?php
                        for ($i = 2020; $i <= $currentYear; $i++) {
                            echo "<option value='$i' " . (($i == $year) ? 'selected' : '') . ">$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="month">Month:</label>
                    <select name="month" id="month" class="form-control" required>
                        <option value="">--Select Month--</option>
                        <?php
                        foreach ($months as $index => $monthName) {
                            echo "<option value='" . ($index + 1) . "' " . (($month == $index + 1) ? 'selected' : '') . ">$monthName</option>";
                        }
                        ?>
                    </select>
                </div>
                        <br>
                <div class="form-group">
                    <button type="submit" name="generate_report" class="btn btn-primary btn-block">Generate Report</button>
                </div>
            </form>
            <?php
            if ($error) {
                echo "<div class='alert alert-danger mt-3'>$error</div>";
            }
            if ($successMessage) {
                echo "<div class='alert alert-success mt-3'>$successMessage</div>";
            }
            ?>
        </div>
    </div>

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
    <div class="row mt-4 d-flex justify-content-center">
    <div class="col-md-4">
        <h4>Branch Weekly Sales Chart</h4>
        <canvas id="branchWeeklySalesChart"></canvas>
    </div>
    <div class="col-md-4">
        <h4>Branch Monthly Sales Chart</h4>
        <canvas id="branchMonthlySalesChart"></canvas>
    </div>
</div>
</div>
<script>
    // Branch Weekly Sales Chart
    const branchWeeklySalesChartCtx = document.getElementById('branchWeeklySalesChart').getContext('2d');
    new Chart(branchWeeklySalesChartCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($branchLabels) ?>,
            datasets: [
                {
                    label: 'Order Sales',
                    data: <?= json_encode($branchOrderSalesData) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',  // Brighter color for Order Sales
                    borderColor: 'rgba(255, 99, 132, 1)',  // Darker border color for contrast
                    borderWidth: 2
                },
                {
                    label: 'Service Sales',
                    data: <?= json_encode($branchServiceSalesData) ?>,
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
    const branchMonthlySalesChartCtx = document.getElementById('branchMonthlySalesChart').getContext('2d');
new Chart(branchMonthlySalesChartCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($branchLabels) ?>,
        datasets: [
            {
                label: 'Order Sales',
                data: <?= json_encode($branchOrderSalesData) ?>,
                backgroundColor: 'rgba(255, 159, 64, 0.8)', // Order Sales color
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 2
            },
            {
                label: 'Service Sales',
                data: <?= json_encode($branchServiceSalesData) ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.8)', // Service Sales color
                borderColor: 'rgba(75, 192, 192, 1)',
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
                        size: 14,
                        weight: 'bold'
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 14,
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

</body>
</html>