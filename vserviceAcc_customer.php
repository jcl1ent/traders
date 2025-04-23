<?php
include("logincode.php");
$page_title = "View Service";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");

$loggedUserId = $_SESSION['userId'];

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
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
</head>
<body>
    <ul class="nav nav-tabs justify-content-end mt-3" id="navTabs">
        <li class="nav-item">
            <a class="nav-link fs-5" href="vservice_customer.php">Requested Services</a>
        </li>
        <li class="nav-item active">
            <a class="nav-link fs-5" href="vserviceAcc_customer.php">Accepted Services</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fs-5" href="vserviceDec_customer.php">Declined Services</a>
        </li>
    </ul>

    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="dataTable" class="table table-hover table-bordered">
                                    <thead>
                                        <tr class="text-center">                                               
                                            <th scope="col">Service Number</th>
                                            <th scope="col">Staff Name</th>
                                            <th scope="col">Manpower</th>
                                            <th scope="col">Service Type</th>  
                                            <th scope="col">Payable</th>
                                            <th scope="col">Total Amount</th>
                                            <th scope="col">Schedule</th>
                                            <th scope="col">Urgent</th>
                                            <th scope="col">Status</th>  
                                            <th scope="col">Estimated Date</th>                                          
                                            <th scope="col">Tracking Reference</th>
                                            <th scope="col">Actions</th>                               
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $query = "
                                            SELECT  
                                                users.fullName AS customerName,
                                                CONCAT(staffs.firstname, ' ', staffs.middlename, ' ', staffs.lastname) AS staffName,
                                                acceptserv2.pendservice,
                                                acceptserv2.acceptedDate,
                                                manpower.fullName AS manpowerName,
                                                acceptserv2.schedule,
                                                acceptserv2.servTrackNo,
                                                reqserv.*
                                            FROM acceptserv2
                                            INNER JOIN users ON acceptserv2.userId = users.userId
                                            INNER JOIN reqserv ON acceptserv2.reqserv = reqserv.reqserv
                                            LEFT JOIN manpower ON acceptserv2.mpId = manpower.mpId
                                            INNER JOIN staffs ON acceptserv2.staffId = staffs.staffId
                                            WHERE acceptserv2.userId = '$loggedUserId' AND acceptserv2.status IN ('Assigned', 'Unassigned') AND reqserv.servArchive = '0'
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
                                        <td><?php echo $row['pendservice'] ?? 'None'; ?></td>
                                        <td><?php echo $row['staffName'] ?? 'None'; ?></td>
                                        <td><?php echo $row['manpowerName']; ?></td>
                                        <td><?php echo $row['servType'] ?? 'None'; ?></td>
                                        <td><?php echo $row['payable'] ?? 'None'; ?></td>
                                        <td><?php echo $row['totalAmount'] ?? 'None'; ?></td>
                                        <td><?php echo $row['schedule'] ?? 'None'; ?></td>
                                        <td><?php echo $row['urgent'] ?? 'None'; ?></td>
                                        <td><?php echo $row['servStatus'] ?? 'None'; ?></td>
                                        <td><?php echo $scheduleOption; ?></td> 
                                        
                                        <?php if ($row['servStatus'] != 'Service Completed') { ?>
                                            <td> </td>
                                            <?php }else {?>
                                            <td>
                                            <a href="<?php echo htmlspecialchars($row['servTrackNo']); ?>">
                                                <?php echo htmlspecialchars($row['servTrackNo']); ?>
                                            </a>
                                        </td>
                                               
                                                <?php }?>
                                        <td class="d-flex justify-content-center align-items-center">
                                        <button type="button" class="btn btn-primary me-2 d-flex align-items-center" onclick="location.href='vserviceAccInfo_customer.php?pendservice=<?php echo $row['pendservice']; ?>'">
                                        <i class="bi bi-arrow-right-circle me-2"></i>
                                        <span>Details</span>
                                        </button>
                                            <?php 
                                            echo "<!-- Debugging: servStatus: " . $row['servStatus'] . " -->";
                                            if ($row['servStatus'] === 'Service Completed') { ?>
                                                <div class="text-center">
                                                    <form action="service_feedback.php" method="GET" class="d-inline">
                                                        <input type="hidden" name="userId" value="<?php echo $loggedUserId; ?>">
                                                        <input type="hidden" name="trscnType" value="<?php echo htmlspecialchars($row['servType']); ?>">
                                                        <input type="hidden" name="pendservice" value="<?php echo htmlspecialchars($row['pendservice']); ?>">
                                                        <button type="submit" class="btn btn-warning d-flex align-items-center">
                                            <i class="bi bi-chat-dots me-2"></i>
                                            <span>Feedback</span>
                                        </button>
                                                    </form>
                                                </div>
                                            <?php } ?>
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
</body>
</html>
<script>
// JavaScript to handle immediate style change and redirection
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
        //link.style.transition = 'background-color 0.2s, color 0.2s'; // Optional: smooth transition
    }

    // Function to reset styles when the tab is no longer active
    function resetNavStyle(link) {
        link.style.backgroundColor = ''; // Reset background color
        link.style.color = ''; // Reset text color
    }
});
</script>