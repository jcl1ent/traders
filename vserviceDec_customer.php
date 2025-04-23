<?php
include("logincode.php");
$page_title = "View Service";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<ul class="nav nav-tabs justify-content-end mt-3" id="navTabs">
    <li class="nav-item">
        <a class="nav-link fs-5" href="vservice_customer.php">Requested Services</a>
    </li>
    <li class="nav-item">
        <a class="nav-link fs-5" href="vserviceAcc_customer.php">Accepted Services</a>
    </li>
    <li class="nav-item active">
        <a class="nav-link fs-5" href="vserviceDec_customer.php">Declined Services</a>
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
                                        <thead>
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">Decline Request Number</th>
                                                <th scope="col">Request Number</th>
                                                <th scope="col">Reason</th>
                                                <th scope="col">Decline Date</th>   
                                                                                      
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
if (isset($_SESSION['email']) && isset($_SESSION['userId']) && isset($_SESSION['custId'])) {
    $userId = $_SESSION['userId'];
    $email = $_SESSION['email'];
    $custId = $_SESSION['custId'];

    // Get the information from the declined service requests with descending order
    $vreqServ_query = "SELECT DISTINCT declined_reqserv.*, reqserv.servArchive, users.*
                        FROM declined_reqserv 
                        INNER JOIN users ON declined_reqserv.userId = users.userId
                        INNER JOIN reqserv ON users.userId = reqserv.userId
                        WHERE declined_reqserv.userId = ? AND reqserv.servArchive = '0'
                        ORDER BY declinedReqsNo DESC";
    $stmt_vServ = $con->prepare($vreqServ_query);
    $stmt_vServ->bind_param("i", $userId);
    $stmt_vServ->execute();
    $result_vServ = $stmt_vServ->get_result();

    if ($result_vServ->num_rows > 0) {
        while ($row = $result_vServ->fetch_assoc()) {                                                                                                                      
            ?>
            <tr class="text-center">
                <td data-label="Request Number"><?php echo $row['declinedReqsNo']; ?></td>                                                                    
                <td data-label="Service Code"><?php echo $row['reqserv']; ?></td>
                <td data-label="Service Type"><?php echo $row['reason']; ?></td>                                                                                                                                           
                <td data-label="Date Created"><?php echo $row['declineDate']; ?></td>
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
