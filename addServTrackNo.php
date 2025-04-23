<?php
include("logincode.php");
$page_title = "Add Tracking Number";
if($_SESSION['userId']){
    $query = "SELECT role FROM USERS where userId = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();


    if($row['role'] == 'admin'){
        include("sidebar_admin.php");
    }else if($row['role'] == 'staff'){
        include("sidebar_staff.php");
    }
}
include("includes/header.php"); 
include("dbcon.php");

if(isset($_POST['addServTrackNo'])){
    $pendservice = $_POST['pendservice'];
    $servTrackNo = $_POST['servTrackNo'];


    // Fetch the service and customer ID from the database
    $sql = "SELECT pendservice, userId FROM acceptserv2 WHERE pendservice = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $pendservice);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userId = $row['userId'];  // Get the customer ID from the result
    } else {
        echo "<script>alert('Service not found.')</script>";
        if($_SESSION['role'] == 'admin'){
            echo '<script>window.location="admin_acceptedService.php"</script>';
        }else{
            echo '<script>window.location="staff_acceptedService.php"</script>';
        }

        exit();
    }

    // Prepare and execute the decline action (log action and update service status)
    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'Tracking Reference has been added to your Service No. ' . $pendservice; 
    $status = 'unread';  // Assuming you want to set the status to unread
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();

    $updateQuery = "UPDATE acceptserv2 SET servTrackNo = ? WHERE pendservice = ?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("si", $servTrackNo, $pendservice);
    if ($stmt->execute()) {
        echo "<script>alert('Tracking Reference Added.');</script>";
        echo "<script>window.location.href='admin_acceptedService.php ?>';</script>";
    } else {
        echo "<script>alert('Error: Error');</script>";
    }
    $stmt->close();
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
                            <h5>Add Tracking Number</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            if($_GET['pendservice']){
                                $pendservice = $_GET['pendservice'];
                            
                            $pendServiceQuery = "SELECT pendservice FROM acceptserv2 WHERE pendservice = ?";
                            $stmt = $con->prepare($pendServiceQuery);
                            $stmt ->bind_param("i", $pendservice);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if($result->num_rows > 0){
                                while($row = $result->fetch_assoc()){

                            ?>
                            <form action="addServTrackNo.php?pendservice=<?php echo $row['pendservice']; ?>" method="POST">
                                <div class="form-group mb-3">
                                    <label for="pendservice">Service Number: </label>
                                    <input type="text" name="pendservice" class="form-control"  value="<?php echo $row['pendservice'] ?>"readonly>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="servTrackNo">Tracking Reference: </label><br>
                                    <textarea class="form-control" name="servTrackNo" aria-label="With textarea" required></textarea>
                                </div>
                                <div class="form-group d-flex justify-content-between">
                                    <button type="submit" name="addServTrackNo" class="btn btn-primary">Submit</button>
                                    <?php 
                                    if($_SESSION['userId']){
                                        $query = "SELECT role FROM USERS where userId = ?";
                                        $stmt = $con->prepare($query);
                                        $stmt->bind_param("i", $_SESSION['userId']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $row = $result->fetch_assoc();
                                    
                                    
                                        if($row['role'] == 'admin'){ ?>
                                            <a href="admin_acceptedService.php" class="btn btn-secondary">
                                                 <i class="bi bi-arrow-90deg-left"></i> Back
                                            </a>
                                        <?php }else if($row['role'] == 'staff'){ ?>
                                            <a href="staff_acceptedService.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-90deg-left"></i> Back
                                    </a>
                                      <?php  }
                                    }
                                    ?>
                                </div>
                            </form>   
                            <?php 
                            }
                        }
                        $stmt->close();
                    }else{
                        echo 'Invalid user ID';
                        echo "<script>window.location.href='addServTrackNo.php?pendservice=<?php $pendservice; ?>';</script>";
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