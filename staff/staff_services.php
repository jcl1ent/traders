<?php
$page_title = "Staff Services";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['deleteService'])) {
    $servCode = $_POST['servCode'];

    // Prepare and execute the delete statement
    $delete_query = "UPDATE services SET servArchive = 1 WHERE servCode = ?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $servCode);

    if ($stmt->execute()) {
        // Optionally set a session message for success or failure
        echo "<script>alert('Service removed successfully.')</script>";
        echo '<script>window.location="staff_services.php"</script>';
    } else {
        echo "<script>alert('Failed to removed service.')</script>";
        echo '<script>window.location="staff_services.php"</script>';
    }

    $stmt->close();

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
                            <!-- Search Form -->
                            <form method="GET" action="staff_services.php" class="mb-4">
                                <div class="input-group input-group-sm">
                                    <input 
                                        type="text" 
                                        name="search" 
                                        class="form-control" 
                                        placeholder="Search for services..." 
                                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                                        style="max-width: 350px;"
                                    >
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </form>

                            <!-- Category Filter -->
                            <form method="GET" class="d-flex justify-content-start mt-2" style="max-width: 350px;">
                                <select name="category" class="form-select" onchange="this.form.submit()">
                                    <option value="" <?= !isset($_GET['category']) || $_GET['category'] == '' ? 'selected' : ''; ?>>All Services</option>
                                    <option value="Welding and Fusion Welding" <?= isset($_GET['category']) && $_GET['category'] == 'Welding and Fusion Welding' ? 'selected' : ''; ?>>Welding and Fusion Welding</option>
                                    <option value="Turbocharger Components" <?= isset($_GET['category']) && $_GET['category'] == 'Turbocharger Components' ? 'selected' : ''; ?>>Turbocharger Components</option>
                                    <option value="General Engine Parts" <?= isset($_GET['category']) && $_GET['category'] == 'General Engine Parts' ? 'selected' : ''; ?>>General Engine Parts</option>
                                    <option value="Casting and Surface Alloying" <?= isset($_GET['category']) && $_GET['category'] == 'Casting and Surface Alloying' ? 'selected' : ''; ?>>Casting and Surface Alloying</option>
                                    <option value="Dynamic Balancing and In-Place Services" <?= isset($_GET['category']) && $_GET['category'] == 'Dynamic Balancing and In-Place Services' ? 'selected' : ''; ?>>Dynamic Balancing and In-Place Services</option>
                                    <option value="Mechanical Parts" <?= isset($_GET['category']) && $_GET['category'] == 'Mechanical Parts' ? 'selected' : ''; ?>>Mechanical Parts</option>
                                </select>
                            </form>

                            <div class="card-body">
                                <div class="row shadow">
                                    <?php
                                    // Get the search and category filters
                                    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
                                    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

                                    // Base query
                                    $query = "SELECT * FROM services WHERE servArchive = 'Available' ";

                                    // Apply category filter if set
                                    if ($categoryFilter && $categoryFilter != '') {
                                        $query .= "AND servCategory = ? ";
                                    }

                                    // Apply search term filter if set
                                    if ($searchTerm && $searchTerm != '') {
                                        $query .= "AND (servName LIKE ? OR servCategory LIKE ? OR rateService LIKE ?) ";
                                    }

                                    // Order by servCode in descending order
                                    $query .= "ORDER BY servCode DESC";

                                    $stmt = $con->prepare($query);

                                    // Bind parameters dynamically
                                    if ($categoryFilter && $searchTerm) {
                                        $searchLike = "%$searchTerm%";
                                        $stmt->bind_param("ssss", $categoryFilter, $searchLike, $searchLike, $searchLike);
                                    } elseif ($categoryFilter) {
                                        $stmt->bind_param("s", $categoryFilter);
                                    } elseif ($searchTerm) {
                                        $searchLike = "%$searchTerm%";
                                        $stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
                                    }

                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if ($result->num_rows > 0) {
                                        while ($service = $result->fetch_assoc()) {
                                            $imageData = base64_encode($service['servImg']);
                                            ?>
                                    <div class="col-lg-4 col-md-6 col-sm-12 p-2">
                                        <div class="border border-dark rounded p-3">
                                            <div class="delete_button d-flex justify-content-end mb-3" style="gap: 10px;"> 
                                            <a href="staff_updateService.php?servCode=<?php echo $row['servCode']; ?>">
                                                <button type="button" class="btn btn-success">
                                                <i class="bi bi-arrow-clockwise"></i>Update
                                                </button>
                                            </a>
                                            </div>
                                            <!-- Added border and padding to each card -->
                                            <form method="POST" action="staff_services.php">
                                            <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid" style="height: 150px; object-fit: cover;">  
                                                    <div class="d-flex flex-column align-items-center text-center mt-2">
                                                        <p>Service ID: <span class="fw-bold"><?= $service['servCode'] ?></span></p>
                                                        <p>Category: <span class="fw-bold"><?= htmlspecialchars($service['servCategory']); ?></span></p>
                                                        <p>Service: <span class="fw-bold"><?= htmlspecialchars($service['servName']); ?></span></p>
                                                        <p>Service Rate: <span class="fw-bold"><?= htmlspecialchars($service['rateService']); ?></span></p>   
                                                    </div>
                                            </form>
                                        </div> <!-- End of bordered card -->
                                    </div>
                                    <?php
                                        }
                                    } else {
                                        echo "<p class='text-center'>No services found matching your criteria.</p>";
                                    }
                                    ?>
                                
                            </div>
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </div>

</body>
</html>