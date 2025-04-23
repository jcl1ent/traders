<?php
$page_title = "Admin Manpower";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_POST['deleteManpower'])) {
    $mpId = $_POST['mpId'];

    // Prepare and execute the delete statement
    $delete_query = "UPDATE manpower SET mpArchive = 1 WHERE mpId = ?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $mpId);

    if ($stmt->execute()) {
        echo "<script>alert('Manpower removed successfully.')</script>";
        echo '<script>window.location="admin_manpower.php"</script>';
    } else {
        echo "<script>alert('Failed to remove manpower.')</script>";
        echo '<script>window.location="admin_manpower.php"</script>';
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
                        <div class="add_manpower d-flex justify-content-end mt-2 me-2" style="gap: 10px;">
                            <a href="admin_addManpower.php">
                                <button type="button" class="btn btn-primary bg-gradient">
                                    <i class="bi bi-person-plus-fill"></i> Add Manpower
                                </button>
                            </a>
                            <a href="admin_archivedManpower.php">
                                <button type="button" class="btn btn-secondary bg-gradient">
                                    <i class="bi bi-trash3-fill"></i> Archived Manpower
                                </button>
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Expertise Filter -->
                            <form method="GET" class="d-flex justify-content-start mt-2" style="max-width: 350px;">
                                <select name="expertise" class="form-select" onchange="this.form.submit()">
                                    <option value="" <?= !isset($_GET['expertise']) || $_GET['expertise'] == '' ? 'selected' : ''; ?>>All Expertise</option>
                                    <option value="Welding and Fusion Welding" <?= isset($_GET['expertise']) && $_GET['expertise'] == 'Welding and Fusion Welding' ? 'selected' : ''; ?>>Welding and Fusion Welding</option>
                                    <option value="Turbocharger Components" <?= isset($_GET['expertise']) && $_GET['expertise'] == 'Turbocharger Components' ? 'selected' : ''; ?>>Turbocharger Components</option>
                                    <option value="Mechanical Parts" <?= isset($_GET['expertise']) && $_GET['expertise'] == 'Mechanical Parts' ? 'selected' : ''; ?>>Mechanical Parts</option>
                                    <option value="General Engine Parts" <?= isset($_GET['expertise']) && $_GET['expertise'] == 'General Engine Parts' ? 'selected' : ''; ?>>General Engine Parts</option>
                                    <option value="Casting and Surface Alloying" <?= isset($_GET['expertise']) && $_GET['expertise'] == 'Casting and Surface Alloying' ? 'selected' : ''; ?>>Casting and Surface Alloying</option>
                                    <option value="Dynamic Balancing and In-Place Services" <?= isset($_GET['expertise']) && $_GET['expertise'] == 'Dynamic Balancing and In-Place Services' ? 'selected' : ''; ?>>Dynamic Balancing and In-Place Services</option>
                                </select>
                            </form>
                            <div class="row shadow">
                                <?php
                                // Build query with optional expertise filter
                                $query = "SELECT m.*, e.* 
                                          FROM manpower AS m
                                          JOIN manpower_expertise AS e ON m.mpId = e.mpId
                                          WHERE m.mpArchive = 0
                                          ";

                                if (isset($_GET['expertise']) && $_GET['expertise'] != '') {
                                    $expertise = $_GET['expertise'];
                                    $query .= " AND (e.expertise1 = '$expertise' OR e.expertise2 = '$expertise' OR e.expertise3 = '$expertise' OR e.expertise4 = '$expertise')";
                                }

                                $result = mysqli_query($con, $query);

                                while ($row = mysqli_fetch_array($result)) {
                                    $imageData = base64_encode($row['mpImg']);
                                ?>
                                    <div class="col-lg-4 col-md-6 col-sm-12 p-2">
                                        <div class="border border-dark rounded p-3">
                                            <div class="delete_button d-flex justify-content-end mb-3" style="gap: 10px;">
                                                <button type="button" class="btn btn-danger bg-gradient" data-bs-toggle="modal" data-bs-target="#deleteModal" data-mp-no="<?php echo $row['mpId']; ?>">
                                                    <i class="bi bi-person-x-fill"></i> Remove
                                                </button>
                                                <a href="admin_updateManpower.php?mpId=<?php echo $row['mpId']; ?>">
                                                    <button type="button" class="btn btn-success">
                                                        Update
                                                    </button>
                                                </a>
                                            </div>
                                            <form method="POST" action="admin_manpower.php">
                                                <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid rounded" style="height: 150px; object-fit: cover;">
                                                <div class="d-flex flex-column align-items-center text-center">
                                                    <p>Manpower ID: <span class="fw-bold"><?= $row['mpId'] ?></span></p>
                                                    <p>Name: <span class="fw-bold"><?php echo $row['fullName']; ?></span></p>
                                                    <p>Age: <span class="fw-bold"><?php echo $row['age']; ?></span></p>
                                                    <p>Address: <span class="fw-bold"><?php echo $row['address']; ?></span></p>
                                                    <p>Contact No: <span class="fw-bold"><?php echo $row['contactNo']; ?></span></p>
                                                    <p>Status: <span class="fw-bold"><?php echo $row['mpStatus']; ?></span></p>

                                                    <h5 class="pt-3">Expertise:</h5>
                                                    <p><strong><?php echo $row['expertise1']; ?></strong></p>
                                                    <p><strong><?php echo $row['expertise2']; ?></strong></p>
                                                    <p><strong><?php echo $row['expertise3']; ?></strong></p>
                                                    <p><strong><?php echo $row['expertise4']; ?></strong></p>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php } ?>
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
                    <form method="POST" action="admin_manpower.php" id="deleteForm">
                        <input type="hidden" name="mpId" id="mpId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                        <button type="submit" name="deleteManpower" class="btn btn-danger">Yes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<script type="text/javascript">
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var mpId = button.getAttribute('data-mp-no');
        var inputmpId = deleteModal.querySelector('#mpId');
        inputmpId.value = mpId;
    });
</script>
