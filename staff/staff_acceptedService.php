<?php
$page_title = "Staff Service Requests";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

$loggedInUserId = $_SESSION['userId'];
$staffIdQuery = "SELECT staffId FROM staffs WHERE userId = '$loggedInUserId'";
$staffIdResult = mysqli_query($con, $staffIdQuery);

// Check if the query returned a result
if ($staffIdResult && mysqli_num_rows($staffIdResult) > 0) {
    $staffRow = mysqli_fetch_assoc($staffIdResult);
    $staffId = $staffRow['staffId']; // Assign the staffId to the variable
} else {
    echo "<script>alert('Error: Staff ID not found.')</script>";
    exit();
}

if (isset($_GET['reqserv'])) {
    $reqserv = $_GET['reqserv'];
    $query = "SELECT  
                                users.fullName AS customerName, 
                                CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                manpower.fullName AS manpowerName,
                                reqserv.*, 
                                acceptserv2.schedule, 
                                acceptserv2.acceptedDate,
                                staffs.staffId,
                                manpower.mpId,
                                customers.address,
                                customers.contact_number
                            FROM reqserv
                            INNER JOIN users ON reqserv.userId = users.userId
                            INNER JOIN customers ON users.userId = customers.userId
                            INNER JOIN acceptserv2 ON reqserv.reqserv = acceptserv2.reqserv
                            INNER JOIN staffs ON acceptserv2.staffId = staffs.staffId
                            INNER JOIN manpower ON acceptserv2.mpId = manpower.mpId
                            WHERE reqserv.reqserv = ? 
                            ORDER BY reqserv.reqserv DESC 
                            LIMIT 1";

    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $reqserv);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<ul class="nav nav-tabs justify-content-end mt-2" id="navTabs">
<li class="nav-item ">
        <a class="nav-link fs-5" href="staff_pendingserv.php">Pendings</a>
    </li>
    <li class="nav-item active">
        <a class="nav-link fs-5" href="staff_acceptedService.php">Accepted</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="staff_servdecline.php">Declined</a>
    </li>
    <li class="nav-item ">
        <a class="nav-link fs-5" href="staff_serviceRequest.php">Service Requests</a>
    </li>
</ul>
<div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="dataTable" class="table table-hover table-bordered">
                                        <thead><h1 class="text-center mb-3">ACCEPTED SERVICE REQUESTS</h1>
                                            <tr class="text-center">                                  
                                                <th scope="col">Service Request ID</th>
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Staff Name</th>
                                                <th scope="col">Manpower</th>
                                                <th scope="col">Service Type</th>
                                                <th scope="col">Description</th>   
                                                <th scope="col">Payable</th>
                                                <th scope="col">Total Amount</th>
                                                <th scope="col">Schedule</th> 
                                                <th scope="col">Urgent</th> 
                                                <th scope="col">Estimated Date</th> 
                                                <th scope="col">Tracking Reference</th>   
                                                <th scope="col">Service Status</th> 
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                            $query = "
                                            SELECT  
                                                users.fullName AS customerName, 
                                                CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                                manpower.fullName AS manpowerName,
                                                reqserv.*,
                                                acceptserv2.pendservice,
                                                acceptserv2.schedule,
                                                acceptserv2.servTrackNo
                                            FROM reqserv
                                            INNER JOIN users ON reqserv.userId = users.userId
                                            INNER JOIN acceptserv2 ON reqserv.reqserv = acceptserv2.reqserv
                                            INNER JOIN staffs ON acceptserv2.staffId = staffs.staffId
                                            LEFT JOIN manpower ON acceptserv2.mpId = manpower.mpId
                                            WHERE acceptserv2.staffId = '$staffId' AND (acceptserv2.schedule IS NOT NULL AND acceptserv2.mpId IS NOT NULL) AND reqserv.servArchive = '0'
                                            ORDER BY acceptserv2.pendservice DESC
                                            ";
                                            $result = mysqli_query($con, $query);   
                                            if ($result) {
                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        // Initialize schedule option for each row
                                                        $scheduleOption = '';
    
                                                        // Calculate the scheduleOption by adding 10 days to the schedule date
                                                        if (!empty($row['schedule'])) {
                                                            $scheduleDate = new DateTime($row['schedule']);
                                                            $scheduleDate->modify('+10 days');
                                                            $scheduleOption = $scheduleDate->format('Y-m-d H:i:s');
                                                        }
                                                ?>
                                                <tr class="text-center">
                                                    <td data-label="Service Request ID"><?php echo $row['pendservice']; ?></td>
                                                    <td data-label="Customer Name"><?php echo $row['customerName']; ?></td>
                                                    <td data-label="Staff Name"><?php echo $row['staffName']; ?></td>
                                                    <td data-label="Manpower"><?php echo $row['manpowerName']; ?></td> 
                                                    <td data-label="Service Type"><?php echo $row['servType']; ?></td>
                                                    <td data-label="Description"><?php echo $row['description']; ?></td> 
                                                    <td data-label="Payable"><?php echo $row['payable']; ?></td> 
                                                    <td data-label="Total Amount"><?php echo $row['totalAmount']; ?></td> 
                                                    <td data-label="Schedule"><?php echo $row['schedule']; ?></td> 
                                                    <td data-label="Urgent"><?php echo $row['urgent']; ?></td> 
                                                    <td data-label="scheduleOption"><?php echo $scheduleOption; ?></td> 
                                                    <?php if($row['servStatus'] == 'Service Completed'): ?>
                                                    <?php if($row['servTrackNo'] == NULL){ ?>
                                                        <td data-label="Tracking Number" style="width: 15%;"><a href="addServTrackNo.php?pendservice=<?php echo $row['pendservice']; ?>">Add Tracking Reference</a></td>
                                                        <?php } else { ?>
                                                            <td data-label="Tracking Number" style="width: 15%;">
                                                                <a href="<?php echo htmlspecialchars($row['servTrackNo']); ?>">
                                                                    <?php echo htmlspecialchars($row['servTrackNo']); ?>
                                                                </a>
                                                            </td>
                                                        <?php } ?>
                                                        <?php else: ?>
                                                            <td data-label="Tracking Number"> </td>
                                                            <?php endif ?>
                                                        <td data-label="Actions" style="width: 10%;">
                                                    <button type="button" class="btn btn-primary d-flex align-items-center" onclick="location.href='staff_acceptedservInfo.php?pendservice=<?php echo $row['pendservice']; ?>'">
                                                    <i class="bi bi-arrow-right-circle me-2"></i>
                                                    <span>Details</span>
                                                    </button>
                                                    </td>                                                
                                                </tr>
                                                <?php 
                                            }
                                        } 
                                    }
                                        ?>
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
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get all the nav items
    const navItems = document.querySelectorAll('.nav-item');

    // Loop through each nav item and add a click event listener
    navItems.forEach(item => {
        const link = item.querySelector('.nav-link');

        // Set up the click event for immediate style change and redirection
        item.addEventListener('click', function(e) {
            // Apply the color changes immediately
            navItems.forEach(nav => {
                // Reset all other nav items
                resetNavStyle(nav.querySelector('.nav-link'));
            });

            // Apply active styles to the clicked link
            applyClickStyle(link);
        });

        // Add a hover effect using JavaScript
        link.addEventListener('mouseover', function() {
            link.style.backgroundColor = '#007bff';
            link.style.color = 'white';
        });

        link.addEventListener('mouseout', function() {
            if (!item.classList.contains('active')) {
                link.style.backgroundColor = ''; // Reset to default
                link.style.color = ''; // Reset to default
            }
        });
    });

    // Function to apply the click styles (background and text color change)
    function applyClickStyle(link) {
        link.style.backgroundColor = '#28a745'; // Green background
        link.style.color = 'white'; // White text
    }

    // Function to reset styles when the tab is no longer active
    function resetNavStyle(link) {
        link.style.backgroundColor = ''; // Reset background color
        link.style.color = ''; // Reset text color
    }
});
</script>