<?php
$page_title = "Staff Manpower";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

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
                            <a href="staff_archivedManpower.php">
                                <button type="button" class="btn  btn-secondary bg-gradient">
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
                                             
                                            <a href="staff_updateManpower.php?mpId=<?php echo $row['mpId']; ?>">
                                                <button type="button" class="btn btn-success">
                                                <i class="bi bi-arrow-clockwise"></i>Update
                                                </button>
                                            </a>
                                            </div>
                                            <!-- Added border and padding to each card -->
                                            <form method="POST" action="staff_manpower.php">
                                                <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid rounded" style="height: 150px; object-fit: cover;">
                                                <div class="d-flex flex-column align-items-center text-center mt-2">
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
                                        </div> <!-- End of bordered card -->
                                    </div>
                                <?php } ?>
                                
                            </div>
                        </div>
                    </div>   
                </div>
            </div>
        </div>
    </div>
</body>
</html>
