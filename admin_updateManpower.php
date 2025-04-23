<?php
$page_title = "Admin Edit Manpower";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");


// Check if form was submitted
if (isset($_POST['updateManpower'])) {
    $mpId = $_POST['mpId'];
    $fullName = $_POST['fullName'];
    $age = $_POST['age'];
    $address = $_POST['address'];
    $contactNo = $_POST['contactNo'];
    $mpStatus = $_POST['mpStatus'];
    $mpArchive = $_POST['mpArchive'];

    // Fetch current values if mpStatus or mpArchive are not provided
    $query = "SELECT * FROM manpower WHERE mpId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $mpId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Use standalone variables
        $mpStatus = !empty($_POST['mpStatus']) ? $_POST['mpStatus'] : $row['mpStatus'];
        $mpArchive = isset($_POST['mpArchive']) ? $_POST['mpArchive'] : $row['mpArchive'];
        $fullName = isset($_POST['fullName']) ? $_POST['fullName'] : $row['fullName'];
        $age = isset($_POST['age']) ? $_POST['age'] : $row['age'];
        $address = isset($_POST['address']) ? $_POST['address'] : $row['address'];
        $contactNo =isset($_POST['contactNo']) ? $_POST['contactNo'] : $row['contactNo'];
        $mpArchive = isset($_POST['mpArchive']) ? $_POST['mpArchive'] : $row['mpArchive'];
    } else {
        echo "<script>alert('Invalid manpower ID.');</script>";
        $stmt->close();
        return;
    }
    
    // Update only the provided fields
    $query = "UPDATE manpower SET fullName=?, age=?, address=?, contactNo=?, mpStatus=?, mpArchive=? WHERE mpId=?";
    $stmt = $con->prepare($query);
    $stmt->bind_param(
        "ssssssi",
        $fullName,
        $age,
        $address,
        $contactNo,
        $mpStatus,
        $mpArchive,
        $mpId
    );

        if ($stmt->execute()) {
            echo "<script>alert('Manpower information updated successfully');</script>";
            echo "<script>window.location.href='admin_manpower.php';</script>";
        } else {
            echo "<script>alert('Error updating manpower information');</script>";
        }
        $stmt->close();
    }


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="text-center">EDIT MANPOWER INFORMATION</h3>
                    </div>
                    <div class="card-body mb-3">
                        <?php 
                        if (isset($_GET['mpId'])) {
                            $mpId = $_GET['mpId'];
                            
                            // Fetch the manpower data based on the provided mpId
                            $query = "SELECT * FROM manpower WHERE mpId = ?";
                            $stmt = $con->prepare($query);
                            $stmt->bind_param("i", $mpId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                        ?>    
                            <form action="admin_updateManpower.php?mpId=<?php echo $mpId; ?>" method="POST">                                       
                                <div class="mb-3 row">
                                    <label for="mpId" class="col col-form-label">Manpower ID:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="mpId" value="<?php echo $row['mpId']; ?>" readonly>                                                    
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="fullName" class="col col-form-label">Manpower Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="fullName" value="<?php echo $row['fullName']; ?>">                                                   
                                    </div>
                                </div>
                                
                                <div class="mb-3 row">
                                    <label for="age" class="col col-form-label">Age:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="age" value="<?php echo $row['age']; ?>">                                                   
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="address" class="col col-form-label">Address:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="address" value="<?php echo $row['address']; ?>">                                                   
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="contactNo" class="col col-form-label">Contact Number:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="contactNo" value="<?php echo $row['contactNo']; ?>">                                                   
                                    </div>
                                </div>                            
                                <div class="mb-3 row">
                                    <label for="mpStatus" class="col col-form-label">Status:</label>
                                        <div class="col">
                                            <select class="form-select" name="mpStatus" aria-required> <!-- Correct name attribute -->
                                                <option value=""><?php echo $row['mpStatus']; ?></option>
                                                <option value="Available">Available</option>
                                                <option value="Not Available">Not Available</option>                                               
                                            </select>
                                        </div>
                                </div>
                                <div class="mb-3 row">
                                        <label for="mpArchive" class="col col-form-label" >Archived:</label>
                                        <div class="col">
                                        <select class="form-select" name="mpArchive" required>
                                            <option value="1" <?php echo $row['mpArchive'] == 1 ? 'selected' : ''; ?>>Archive</option>
                                            <option value="0" <?php echo $row['mpArchive'] == 0 ? 'selected' : ''; ?>>Unarchive</option>
                                        </select>
                                        </div>
                                </div>                                    
                                <div class="d-flex justify-content-center m-3" style="gap: 10px;">
                                    <?php 
                                    $archive_query = "SELECT mpArchive  FROM manpower WHERE mpId = '$mpId'";
                                    $archive_result = mysqli_query($con, $archive_query);
                                    $archive_row = mysqli_fetch_assoc($archive_result);
                                    if($archive_row['mpArchive'] == 1){ ?>
                                <a href="admin_archivedManpower.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-90deg-left"></i> Back 
                                </a>
                                <?php  } else { ?>
                                <a href="admin_manpower.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-90deg-left"></i> Back 
                                </a>
                                <?php } ?>
                                <button type="submit" name="updateManpower" class="btn btn-primary">
                                <i class="bi bi-arrow-clockwise"></i> Update
                                </button>
                                </div>
                            </form>
                        <?php
                            } else {
                                echo "<p>Manpower information not found.</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p>No manpower ID provided.</p>";
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
