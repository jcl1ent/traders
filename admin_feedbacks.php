<?php
$page_title = "Admin Feedbacks";
include("logincode.php");
include("sidebar_admin.php");
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
                                                                                        
                                                <th scope="col">Feedback Number</th>
                                                <th scope="col">Transaction Type</th>
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Rating</th>
                                                <th scope="col">Feedback</th>
                                                <th scope="col">Feedback Date</th>
                                                                                     
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                             
                                            $query = "
                                                SELECT users.fullName, users.userId, feedback.*
                                                FROM users
                                                INNER JOIN feedback ON users.userId = feedback.userId
                                                ORDER BY feedback.fbNo DESC
                                            ";
                                            $stmt = $con->prepare($query);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    ?>
                                                    <tr class="text-center">
                                                        <td data-label="Feedback Number"><?php echo $row['fbNo']; ?></td>
                                                        <td data-label="Transaction Type"><?php echo $row['trscnType']; ?></td>
                                                        <td data-label="Customer Name"><?php echo $row['fullName']; ?></td>
                                                        <td data-label="Rating"><?php echo $row['satisfaction']; ?></td>
                                                        <td data-label="Feedback"><?php echo $row['description']; ?></td>
                                                        <td data-label="Decline Date"><?php echo $row['createDate']; ?></td>                                                       
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