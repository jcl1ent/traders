<?php
$page_title = "Admin User Accounts";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");


if (isset($_POST['deleteUserId'])) {
    $deleteUserId = $_POST['deleteUserId'];

    // Check if the userId is numeric to prevent SQL injection
    if (is_numeric($deleteUserId)) {
        // Prepare and execute the delete statement
        $delete_query = "DELETE FROM users WHERE userId = ?";
        $stmt = $con->prepare($delete_query);
        $stmt->bind_param("i", $deleteUserId);

        if ($stmt->execute()) {
            // Redirect back to the same page with a success message
            echo "<script>alert('User deleted successfully.')</script>";
            echo '<script>window.location="admin_userAccounts.php"</script>';
        } else {
            echo "<script>alert('Failed to delete user.')</script>";
            echo '<script>window.location="admin_userAccounts.php"</script>';
        }

        $stmt->close();
    } else {
        echo "<script>alert('Invalid User ID.')</script>";
        echo '<script>window.location="admin_userAccounts.php"</script>';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<ul class="nav nav-tabs justify-content-end mt-3" id="navTabs">
    <li class="nav-item ">
        <a class="nav-link fs-5" href="admin_userAccounts.php">Customers</a>
    </li>
    <li class="nav-item active">
        <a class="nav-link fs-5" href="admin_staffAccounts.php">Staffs</a>
    </li>
</ul>
<div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">
                            <div class="addUser d-flex justify-content-end m-3">
                                <a href="admin_addUser.php">
                                    <button type ="button" class="btn btn-success bg-gradient">
                                        <i class="bi bi-person-plus-fill"> Add User</i>
                                    </button>
                                </a>
                                <div class="assign_branch ms-3">
                                <a href="admin_assignBranch.php">
                                    <button type ="button" class="btn btn-primary bg-gradient">
                                        Assign Branch
                                    </button>
                                </a>
                                </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead><h1 class="text-center">Staff Accounts</h1>
                                            <tr class="text-center">
                                                                                        
                                                <th scope="col">User ID</th>
                                                <th scope="col">Name</th>
                                                <th scope="col">Email</th>
                                                <th scope="col">Status</th>   
                                                <th scope="col">Created Date</th>
                                                <th scope="col">Action</th>                                      
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                 $vCust_query = "SELECT * FROM users WHERE role = 'staff'";
                                                 $stmt_vCust = $con->prepare($vCust_query);
                                                 $stmt_vCust->execute();
                                                 $result_vCust = $stmt_vCust->get_result();
                                                 if ($result_vCust->num_rows > 0) {
                                                    while ($row = $result_vCust->fetch_assoc()) {                                                                                                                      
                                                                ?>
                                                                <tr class="text-center">
                                                                    
                                                                    <td data-label="User ID"><?php echo $row['userId']; ?></td>                                                                    
                                                                    <td data-label="Name"><?php echo $row['fullName']; ?></td>
                                                                    <td data-label="Email"><?php echo $row['email']; ?></td>
                                                                    <?php 
                                                                    $color = strtolower(trim($row['user_status'])) === 'online' ? 'green' : 'red';
                                                                    ?>
                                                                    <td data-label="Status"><?php echo "<p style='color: $color;'>".ucfirst($row['user_status'])."</p>"; ?></td>
                                                                    <td data-label="Created Date"><?php echo $row['created_at']; ?></td>                                                
                                                                    <td data-label="Actions">
                                                                    <a href="staff_info.php?userId=<?php echo $row['userId']; ?>">
                                                                        <button type="button" class="btn btn-primary bg-gradient">
                                                                            Details
                                                                        </button>
                                                                    </a>
                                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?php echo $row['userId']; ?>">
                                                                    <i class="bi bi-trash3"></i> Remove
                                                                    </button>
                                                                    
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
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this user?
      </div>
      <div class="modal-footer">
        <form method="POST" action="admin_userAccounts.php" id="deleteForm">
          <input type="hidden" name="deleteUserId" id="userId">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" class="btn btn-danger">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>

</body>
</html>
<script typee="text/javascript">
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var userId = button.getAttribute('data-user-id'); // Extract userId
    var inputuserId = deleteModal.querySelector('#userId'); // Get the hidden input inside the form
    inputuserId.value = userId; // Set the userId value in the hidden input
});

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
        }

        // Function to reset styles when the tab is no longer active
        function resetNavStyle(link) {
            link.style.backgroundColor = ''; // Reset background color
            link.style.color = ''; // Reset text color
        }
    });
</script>
