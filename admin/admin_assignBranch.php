<?php
$page_title = "Admin Assign Branch";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if(isset($_POST['assignBranch'])){
    $staffId = $_POST['staffId'];
    $branch = $_POST['branch'];

    $staffsQuery = "SELECT staffId, branch FROM staffs WHERE staffId = '$staffId'";
    $stmt = $con->prepare($staffsQuery);
    $stmt->execute();
    $staffs = $stmt->get_result();
    $staffRoww = $staffs->fetch_assoc();

    $updateStaffQuery = "UPDATE staffs SET branch = ? WHERE staffId = ?";
    $stmt = $con->prepare($updateStaffQuery);
    $stmt->bind_param("si", $branch, $staffId);

    if($stmt->execute()){
        echo "<script>alert('Staff Added to the branch successfully!');</script>";    
    }else{
        echo "<script>alert('Error: Something Went Wrong');</script>";
    }
    $stmt->close();


}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-table-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .form {
            flex: 2.5;

        }

        .branches-staffs {
            flex: 2;
        }

        table {
            width: 80%;
            border-collapse: collapse;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            max-width: 50px;
        }

        table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="py-5 mt-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">
                            <div class="card-body">
                                <a href="admin_staffAccounts.php">
                                    <button class="btn btn-secondary bg-gradient">
                                        <i class="bi bi-arrow-90deg-left"> Back</i>
                                    </button>
                                </a>
                                <h1 class="text-center">Assign Branch</h1>
                                <div class="form-table-wrapper">
                                    <div class="form">
                                        <form name="assignBranchForm" action="" method="POST">
                                            <div class="col-md-3 p-3">
                                                <label for="staffId" class="form-label">Staff Name: </label>
                                                <select class="form-select" name="staffId" required>
                                                    <option value="" selected disabled>Select Staff</option>
                                                    <?php                          
                                                    $sql = "SELECT staffId, CONCAT(firstname, ' ' , lastname) AS staffName 
                                                    FROM staffs";
                                                    $stmt = $con->prepare($sql);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo '<option value="' . htmlspecialchars($row['staffId']) . '">' . htmlspecialchars(ucwords($row['staffName'])) . '</option>';
                                                        }
                                                    } else {
                                                        echo '<option value="" disabled>No Staff Available</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 p-3">
                                                <label for="branch" class="form-label">Branch Name: </label>
                                                <select class="form-select" name="branch" required>
                                                    <option value="" selected disabled>Select Branch</option>
                                                    <option value="Cebu">Cebu</option>
                                                    <option value="Manila">Manila</option>
                                                    <option value="Palawan">Palawan</option>
                                                </select>
                                            </div>
                                            <div class="form-group ms-3">
                                                <button type="submit" name="assignBranch" class="btn btn-primary">Assign</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="branches-staffs">
                                        <h3 class="text-center">Branches With Staffs</h3>
                                        <table id="news" class="table vertical-align">
                                            <thead>
                                                <tr>
                                                    <th>CEBU</th>
                                                    <th>MANILA</th>
                                                    <th>PALAWAN</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        $staffQuery="SELECT staffId, CONCAT(firstname, ' ' , lastname) AS staffName, branch 
                                                                    FROM staffs
                                                                    WHERE branch = 'Cebu'";
                                                        $staffStmt = $con->prepare($staffQuery);
                                                        $staffStmt->execute();
                                                        $staffResult = $staffStmt->get_result();
                                                        if ($staffResult->num_rows > 0) {
                                                            while ($staffRow = $staffResult->fetch_assoc()) {
                                                                echo ucwords($staffRow['staffName']) . "<br>";
                                                            }
                                                        } else {
                                                            echo "No staff in this branch";
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $staffQuery="SELECT staffId, CONCAT(firstname, ' ' , lastname) AS staffName, branch 
                                                                    FROM staffs
                                                                    WHERE branch = 'Manila'";
                                                        $staffStmt = $con->prepare($staffQuery);
                                                        $staffStmt->execute();
                                                        $staffResult = $staffStmt->get_result();
                                                        if ($staffResult->num_rows > 0) {
                                                            while ($staffRow = $staffResult->fetch_assoc()) {
                                                                echo ucwords($staffRow['staffName']) . "<br>";
                                                            }
                                                        } else {
                                                            echo "No staff in this branch";
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $staffQuery="SELECT staffId, CONCAT(firstname, ' ' , lastname) AS staffName, branch 
                                                                    FROM staffs
                                                                    WHERE branch = 'Palawan'";
                                                        $staffStmt = $con->prepare($staffQuery);
                                                        $staffStmt->execute();
                                                        $staffResult = $staffStmt->get_result();
                                                        if ($staffResult->num_rows > 0) {
                                                            while ($staffRow = $staffResult->fetch_assoc()) {
                                                                echo ucwords($staffRow['staffName']) . "<br>";
                                                            }
                                                        } else {
                                                            echo "No staff in this branch";
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
