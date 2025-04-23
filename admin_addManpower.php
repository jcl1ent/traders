<?php
$page_title = "Admin Add Manpower";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if (isset($_POST['addManpower_btn'])) {
    $fullName = $_POST['fullName'];
    $age = $_POST['age'];
    $address = $_POST['address'];
    $contactNo = $_POST['contactNo'];
    $mpStatus = 'Available';
    $mpArchive = 0;

    if (isset($_FILES['mpImg']) && $_FILES['mpImg']['error'] === 0) {
        $mpImg = $_FILES['mpImg']['tmp_name'];
        $mpImgData = file_get_contents($mpImg); // Get the binary image data
        
        $sql = "INSERT INTO manpower (fullName, age, address, contactNo, mpImg, mpStatus, mpArchive) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssssssi", $fullName, $age, $address, $contactNo, $mpImgData, $mpStatus, $mpArchive); // Change 'b' to 's' as bind_param needs 's' for string

        if ($stmt->execute()) {
            echo "<script>alert('Manpower data and image uploaded successfully!');</script>";
            echo "<script>window.location.href='admin_manpower.php';</script>";
        } else {
            echo "<script>alert('Error: Could not upload the data.');</script>";
            echo "<script>window.location.href='admin_manpower.php';</script>";
        }
    } else {
        echo "<script>alert('Error: No image selected or there was an error uploading.');</script>";
        echo "<script>window.location.href='admin_addManpower.php';</script>";
        
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
                        <h5>Add Manpower</h5>
                    </div>
                    <div class="card-body">
                        <form name ="addManpower" action="" method="POST" enctype="multipart/form-data">
                            <div class="form-group mb-3">
                                <label for="">Full Name</label>
                                <input type="text" name="fullName" class="form-control" required>
                            </div>            
                            <div class="form-group mb-3">
                                <label for="">Age</label>
                                <input type="text" name="age" class="form-control" required>
                            </div>               
                            <div class="form-group mb-3">
                                <label for="">Address</label>
                                <input type="text" name="address" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Contact Number</label>
                                <input type="text" name="contactNo" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="">Image</label>
                                <input type="file" name="mpImg" class="form-control" required>                               
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="addManpower_btn" class="btn btn-primary">Add Manpower</button>
                            </div>
                        </form>    
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>