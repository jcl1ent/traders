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

if(isset($_POST['addTrackNo'])){
    $orderNo = $_POST['orderNo'];
    $orderTrackNo = $_POST['orderTrackNo'];


    // Fetch the order and customer ID from the database
    $sql = "SELECT orderNo, custId FROM orders WHERE orderNo = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $orderNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $custId = $row['custId'];  // Get the customer ID from the result
    } else {
        echo "<script>alert('Order not found.')</script>";
        echo '<script>window.location="admin_pendingOrders.php"</script>';
        exit();
    }

    // Prepare and execute the decline action (log action and update order status)
    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'Tracking Reference has been added to your Order No. ' . $orderNo; 
    $status = 'unread';  // Assuming you want to set the status to unread
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();

    $updateQuery = "UPDATE orders SET orderTrackNo = ? WHERE orderNo = ?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("si", $orderTrackNo, $orderNo);
    if ($stmt->execute()) {
        echo "<script>alert('Tracking Reference Added.');</script>";
        echo "<script>window.location.href='admin_acceptedOrders.php ?>';</script>";
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
                            if($_GET['orderNo']){
                                $orderNo = $_GET['orderNo'];
                            
                            $orderQuery = "SELECT orderNo FROM orders WHERE orderNo = ?";
                            $stmt = $con->prepare($orderQuery);
                            $stmt ->bind_param("i", $orderNo);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if($result->num_rows > 0){
                                while($row = $result->fetch_assoc()){

                            ?>
                            <form action="addTrackNo.php?orderNo=<?php echo $row['orderNo']; ?>" method="POST">
                                <div class="form-group mb-3">
                                    <label for="orderNo">Order Number: </label>
                                    <input type="text" name="orderNo" class="form-control"  value="<?php echo $row['orderNo'] ?>"readonly>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="orderTrackNo">Tracking Reference: </label><br>
                                    <textarea class="form-control" name="orderTrackNo" aria-label="With textarea" required></textarea>
                                </div>
                                <div class="form-group d-flex justify-content-between">
                                    <button type="submit" name="addTrackNo" class="btn btn-primary">Submit</button>
                                    
                                    <?php 
                                    if($_SESSION['userId']){
                                        $query = "SELECT role FROM USERS where userId = ?";
                                        $stmt = $con->prepare($query);
                                        $stmt->bind_param("i", $_SESSION['userId']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $row = $result->fetch_assoc();
                                    
                                    
                                        if($row['role'] == 'admin'){ ?>
                                            <a href="admin_acceptedOrders.php" class="btn btn-secondary">
                                                <i class="bi bi-arrow-90deg-left"></i> Back
                                            </a>
                                        <?php }else if($row['role'] == 'staff'){ ?>
                                            <a href="staff_acceptedOrders.php" class="btn btn-secondary">
                                                <i class="bi bi-arrow-90deg-left"></i> Back
                                            </a>
                                      <?php  }
                                    }
                                    ?>
                                </div>
                                </div>
                            </form>   
                            <?php 
                            }
                        }
                        $stmt->close();
                    }else{
                        echo 'Invalid user ID';
                        echo "<script>window.location.href='addTrackNo.php?orderNo=<?php $orderNo; ?>';</script>";
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