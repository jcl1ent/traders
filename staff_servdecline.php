<?php
$page_title = "Staff Service Requests";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

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
    <li class="nav-item ">
        <a class="nav-link fs-5" href="staff_acceptedService.php">Accepted</a>
    </li>
    <li class="nav-item active">
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
                                        <thead><h1 class="text-center mb-3">DECLINED SERVICE REQUEST</h1>
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">Decline Request ID</th>
                                                <th scope="col">Service Request ID</th>
                                                <th scope="col">User ID</th>
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Reason</th>
                                                <th scope="col">Decline Date</th>
                                                                                     
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                             
                                            $query = "
                                                SELECT DISTINCT users.fullName, users.userId, declined_reqserv.*, reqserv.servStatus
                                                FROM users
                                                INNER JOIN declined_reqserv ON users.userId = declined_reqserv.userId
                                                INNER JOIN reqserv ON users.userId = reqserv.userId
                                                WHERE reqserv.servArchive = '0'
                                                ORDER BY declined_reqserv.declinedReqsNo DESC
                                            ";
                                            $stmt = $con->prepare($query);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    ?>
                                                    <tr class="text-center">
                                                        <td data-label="Decline Request ID"><?php echo $row['declinedReqsNo']; ?></td>
                                                        <td data-label="Service Request ID"><?php echo $row['reqserv']; ?></td>
                                                        <td data-label="User ID"><?php echo $row['userId']; ?></td>
                                                        <td data-label="Customer Name"><?php echo $row['fullName']; ?></td>
                                                        <td data-label="Reason"><?php echo $row['reason']; ?></td>
                                                        <td data-label="Decline Date"><?php echo $row['declineDate']; ?></td>                                                       
                                                    </tr>
                                                    <?php 
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