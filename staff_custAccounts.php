<?php
$page_title = "Staff User Accounts";
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
<div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">                              
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead><h1 class="text-center">Customer Accounts</h1>
                                            <tr class="text-center">
                                                <th scope="col">Customer ID</th>
                                                <th scope="col">Name</th>
                                                <th scope="col">Email</th>
                                                <th scope="col">Status</th>   
                                                <th scope="col">Registered Date</th>
                                                <th scope="col">Action</th>                                      
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                // Step 1: Fetch users with role 'customer'
                                                $vCust_query = "SELECT * FROM users WHERE role = 'customer'";
                                                $stmt_vCust = $con->prepare($vCust_query);
                                                $stmt_vCust->execute();
                                                $result_vCust = $stmt_vCust->get_result();

                                                // Check if users exist
                                                if ($result_vCust->num_rows > 0) {
                                                    // Loop through each user
                                                    while ($row = $result_vCust->fetch_assoc()) {
                                                        // Get the custId from the customer table using the userId from users table
                                                        $userId = $row['userId'];
                                                        $sql_customer = "SELECT custId FROM customers WHERE userId = ?";
                                                        $stmt_customer = $con->prepare($sql_customer);
                                                        $stmt_customer->bind_param("i", $userId);
                                                        $stmt_customer->execute();
                                                        $customer_result = $stmt_customer->get_result();

                                                        // Check if a customer is found
                                                        if ($customer_result->num_rows > 0) {
                                                            $customer_row = $customer_result->fetch_assoc();
                                                            $custId = $customer_row['custId'];
                                                        } else {
                                                            $custId = 'N/A'; // Handle case where no customer found
                                                        }
                                                        ?>
                                                        <tr class="text-center">
                                                            <td data-label="Customer ID"><?php echo $custId; ?></td>
                                                            <td data-label="Name"><?php echo ucwords($row['fullName']); ?></td>
                                                            <td data-label="Email"><?php echo $row['email']; ?></td>
                                                            <?php 
                                                            $color = strtolower(trim($row['user_status'])) === 'online' ? 'green' : 'red';
                                                            ?>
                                                            <td data-label="Status"><?php echo "<p style='color: $color;'>".ucfirst($row['user_status'])."</p>"; ?></td>
                                                            <td data-label="Created Date"><?php echo $row['created_at']; ?></td>                                                
                                                            <td data-label="Actions">
                                                                <a href="staff_custInfo.php?userId=<?php echo $row['userId']; ?>">
                                                                    <button type="button" class="btn btn-primary bg-gradient">
                                                                        Details
                                                                    </button>
                                                                </a>
                                                            </td>                                                           
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    echo "<tr class='text-center'><td colspan='13'>No Service Request found for this customer.</td></tr>";
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

<script type="text/javascript">
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var userId = button.getAttribute('data-user-id'); // Extract userId
        var inputuserId = deleteModal.querySelector('#userId'); // Get the hidden input inside the form
        inputuserId.value = userId; // Set the userId value in the hidden input
    });

    document.addEventListener("DOMContentLoaded", function() {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            const link = item.querySelector('.nav-link');
            item.addEventListener('click', function() {
                navItems.forEach(nav => resetNavStyle(nav.querySelector('.nav-link')));
                applyClickStyle(link);
            });
            link.addEventListener('mouseover', function() {
                link.style.backgroundColor = '#007bff';
                link.style.color = 'white';
            });
            link.addEventListener('mouseout', function() {
                if (!item.classList.contains('active')) {
                    link.style.backgroundColor = '';
                    link.style.color = '';
                }
            });
        });

        function applyClickStyle(link) {
            link.style.backgroundColor = '#28a745';
            link.style.color = 'white';
        }

        function resetNavStyle(link) {
            link.style.backgroundColor = '';
            link.style.color = '';
        }
    });
</script>
