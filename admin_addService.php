<?php
$page_title = "Admin Add Service";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if (isset($_POST['addService_btn'])) {
    $servName = $_POST['servName'];
    $rateService = $_POST['rateService'];
    $servArchive = 'Available';

    if (isset($_FILES['servImg']) && $_FILES['servImg']['error'] === 0) {
        $servImg = $_FILES['servImg']['tmp_name'];
        $servImgData = file_get_contents($servImg); // Get the binary image data
        
        $sql = "INSERT INTO services(servName, rateService, servArchive, servImg) VALUES (?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sdss", $servName, $rateService, $servArchive, $servImgData); // Change 'b' to 's' as bind_param needs 's' for string

        if ($stmt->execute()) {
            echo "<script>alert('Service uploaded successfully!');</script>";
            echo "<script>window.location.href='admin_services.php';</script>";
        } else {
            echo "<script>alert('Error: Could not upload service.');</script>";
            echo "<script>window.location.href='admin_addService.php';</script>";
        }
    } else {
        echo "<script>alert('There was an error uploading.');</script>";
        echo "<script>window.location.href='admin_addService.php';</script>";
        
    }

    if (isset($stmt)) {
        $stmt->close(); // Close the statement if it was created
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h5>Add Service</h5>
                    </div>
                    <div class="card-body">
                        <form name ="addManpower" action="" method="POST" enctype="multipart/form-data">
                            <div class="form-group mb-3">
                                <label for="">Service Name: </label>
                                <input type="text" name="servName" class="form-control" required>
                            </div>            
                            <div class="form-group mb-3">
                                <label for="">Service Rate: </label>
                                <input type="text" name="rateService" class="form-control" required>
                            </div>               
                            <div class="form-group mb-3">
                                <label for="">Service Image: </label>
                                <input type="file" name="servImg" class="form-control" required>                               
                            </div>
                            
                            <div class="form-group d-flex justify-content-between align-items-center">
                            <button type="submit" name="addService_btn" class="btn btn-primary">
                                Add Service
                            </button>
                            <a href="admin_services.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-90deg-left"></i> Back
                            </a>
                        </div>
                        </form>    
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>