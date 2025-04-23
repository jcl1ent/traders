<?php
$page_title = "Staff Products";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

    // Fetch categories for the dropdown
    $categories = ["All Products", "Engine Components", "Turbocharger Components", "Valve Components"];

    // Get the selected category and search term from the request
    $selectedCategory = isset($_GET['category']) ? $_GET['category'] : "All Products";
    $searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : "";

    // Prepare the query
    $query = "SELECT * FROM products";
    $conditions = [];

    if (!empty($searchTerm)) {
        $conditions[] = "(prodName LIKE '%$searchTerm%' OR prodCategory LIKE '%$searchTerm%' OR prodNo LIKE '%$searchTerm%')";
    }

    if ($selectedCategory !== "All Products") {
        $conditions[] = "prodCategory = '" . mysqli_real_escape_string($con, $selectedCategory) . "'";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY prodNo DESC";

    // Execute the query
    $result = mysqli_query($con, $query);
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

                            <form method="GET" action="staff_products.php" class="mb-4">
                                <div class="input-group input-group-sm">
                                    <input 
                                        type="text" 
                                        name="search" 
                                        class="form-control" 
                                        placeholder="Search for products..." 
                                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                                        style="max-width: 350px;"
                                    >
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </form>

                            <!-- Dropdown Filter -->
                            <form method="GET" class="d-flex justify-content-start mt-2" style="max-width: 350px;">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
                                <select name="category" class="form-select" onchange="this.form.submit()">
                                    <option value="All Products" <?= $selectedCategory == 'All Products' ? 'selected' : '' ?>>All Products</option>
                                    <option value="Engine Components" <?= $selectedCategory == 'Engine Components' ? 'selected' : '' ?>>Engine Components</option>
                                    <option value="Turbocharger Components" <?= $selectedCategory == 'Turbocharger Components' ? 'selected' : '' ?>>Turbocharger Components</option>
                                    <option value="Valve Components" <?= $selectedCategory == 'Valve Components' ? 'selected' : '' ?>>Valve Components</option>
                                </select>
                            </for>

                        <!-- Product List -->
                        <div class="row shadow">
                            <?php
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_array($result)) {
                                    $imageData = base64_encode($row['prodImg']);
                            ?>
                                    <div class="col-lg-4 col-md-6 col-sm-12 p-2">
                                        <div class="border border-dark rounded p-3">
                                            <div class="delete_button d-flex justify-content-end mb-3" style="gap: 10px;"> 
                                            <a href="staff_updateProducts.php?prodNo=<?php echo $row['prodNo']; ?>">
                                                <button type="button" class="btn btn-success">
                                                <i class="bi bi-arrow-clockwise"></i> Update
                                                </button>
                                            </a>
                                            </div>
                                            <!-- Added border and padding to each card -->
                                            <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid rounded" style="height: 150px; object-fit: cover;">
                                                <div class="d-flex flex-column align-items-center text-center mt-2">
                                                    <p>Product ID: <span class="fw-bold"><?= $row['prodNo'] ?></span></p>
                                                    <p>Category: <span class="fw-bold"><?php echo $row['prodCategory']; ?></span></p>
                                                    <p>Product Name: <span class="fw-bold"><?php echo $row['prodName']; ?></span></p>
                                                    <p>Quantity: <span class="fw-bold"><?php echo $row['quantity']; ?></span></p>
                                                    <p>Price: <span class="fw-bold"><?php echo $row['prodPrice']; ?></span></p>
                                                    <p>Status: <span class="fw-bold"><?php echo $row['productStatus']; ?></span></p>
                                                </div>
                                        </div> <!-- End of bordered card -->
                                    </div>
                                    <?php
                                    }
                                } else {
                                    echo "<p class='text-center'>No products found for the selected category or search term.</p>";
                                }
                                ?>
                                
                            </div>
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this manpower?
      </div>
      <div class="modal-footer">
        <form method="POST" action="admin_products.php" id="deleteForm">
          <input type="hidden" name="prodNo" id="prodNo">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="deleteProduct" class="btn btn-danger">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<script type="text/javascript">
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var prodNo = button.getAttribute('data-prod-no'); // Extract pdngReqsNo
        var inputprodNo = deleteModal.querySelector('#prodNo'); // Get the hidden input inside the form
        inputprodNo.value = prodNo; // Set the pdngReqsNo value in the hidden input
    });
</script>