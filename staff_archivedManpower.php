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
                            <a href="staff_manpower.php">
                                <button type="button" class="btn  btn-secondary bg-gradient">
                                <i class="bi bi-arrow-left"></i> Back
                                </button>
                            </a>
                                                                                       
                        </div>
                            <div class="card-body">
                                <div class="row shadow">
                                    
                                    <?php
                                    // Fetch manpower entries
                                    $query = "SELECT * FROM manpower 
                                    WHERE mpArchive  = 1 ";

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
