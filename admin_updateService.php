<?php
$page_title = "Admin Edit Service";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");


// Check if form was submitted
// Check if form was submitted
if (isset($_POST['updateService'])) {
    $servCode = $_POST['servCode'];
    $servName = $_POST['servName'];
    $rateService = $_POST['rateService'];
    $servArchive = $_POST['servArchive'];

    $rateQuery = "SELECT servName, rateService, servArchive FROM services WHERE servCode = ?";
    $stmt = $con->prepare($rateQuery);
    $stmt->bind_param("i", $servCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        $servCode = !empty($_POST['servCode']) ? $_POST['servCode'] : $row['servCode'];
        $servName = !empty($_POST['servName']) ? $_POST['servName'] : $row['servName'];
        $rateService = !empty($_POST['rateService']) ? $_POST['rateService'] : $row['rateService'];
        $servArchive = !empty($_POST['servArchive']) ? $_POST['servArchive'] : $row['servArchive'];
    } else {
        echo "<script>alert('Invalid service ID.');</script>";
        $stmt->close();
        return;
    }
    
    // Handle the image upload
    if (isset($_FILES['servImg']) && $_FILES['servImg']['error'] === 0) {
        $servImg = $_FILES['servImg']['tmp_name'];
        $servImgData = file_get_contents($servImg);
    } else {
        // If no new image is uploaded, retain the old image
        $query = "SELECT servImg FROM services WHERE servCode = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $servCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $servImgData = $row['servImg']; // Retain the existing image data
    }
    
    // Update the services information in the database
    $query = "UPDATE services SET servName=?, servImg=?, rateService=?, servArchive=? WHERE servCode=?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ssdsi", $servName, $servImgData, $rateService, $servArchive, $servCode);
    
    if ($stmt->execute()) {
        echo "<script>alert('Service information updated successfully');</script>";
        echo "<script>window.location.href='admin_services.php';</script>";
    } else {
        echo "<script>alert('Error updating service information');</script>";
    }
    
    $stmt->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="text-center">EDIT SERVICE INFORMATION</h3>
                    </div>
                    <div class="card-body mb-3">
                        <?php 
                        if (isset($_GET['servCode'])) {
                            $servCode = $_GET['servCode'];
                            
                            // Fetch the manpower data based on the provided mpId
                            $query = "SELECT * FROM services WHERE servCode = ?";
                            $stmt = $con->prepare($query);
                            $stmt->bind_param("i", $servCode);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                $imageData = base64_encode($row['servImg']);
                                
                        ?>    
                            
                            <form action="admin_updateService.php" method="POST" enctype="multipart/form-data">   
                                <input type="hidden" name="servCode" value="<?php echo $row['servCode']; ?>">
                                <div class="mb-3 row">
                                    <label for="servCode" class="col col-form-label">Service ID:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="prodNo" value="<?php echo $row['servCode']; ?>" disabled readonly>                                                   
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="servName" class="col col-form-label">Service Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="servName" value="<?php echo $row['servName']; ?>">                                                   
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="rateService" class="col col-form-label">Service Rate:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="rateService" value="<?php echo $row['rateService']; ?>">                                                   
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                        <label for="servArchive" class="col col-form-label" >Status:</label>
                                        <div class="col">
                                            <select class="form-select" name="servArchive" aria-required> <!-- Correct name attribute -->
                                                <option value=""><?php echo $row['servArchive']; ?></option>
                                                <option value="Available">Available</option>
                                                <option value="Unavailable">Unavailable</option>                                               
                                            </select>
                                        </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="servImg" class="col col-form-label">Service Image:</label>
                                    <div class="col">
                                        <input class="form-control" type="file" name="servImg">
                                        <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid" style="height: 150px; object-fit: cover;">                                                   
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center m-3" style="gap: 10px;">
                                    <a href="admin_services.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-90deg-left"></i> Back 
                                    </a>
                                    <button type="submit" name="updateService" class="btn btn-primary">
                                        <i class="bi bi-arrow-clockwise"></i> Update
                                    </button>
                                </div>
                            </form>
                        <?php
                            } else {
                                echo "<p>Service information not found.</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p>No Service ID provided.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>  
</body>
</html>
