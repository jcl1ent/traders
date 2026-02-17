<?php
 // Start the session
 if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . "/../dbcon.php");
include(__DIR__ . "/../logincode.php");

if (isset($_SESSION['userId'])) {
  $userId = $_SESSION['userId'];

  // Fetch the user's name from the database
  $query = "SELECT fullName FROM users WHERE userId = ?";
  $stmt = $con->prepare($query);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullName = ucwords($row['fullName']); // Get the username from the query result
    $nameParts = explode(' ', $fullName);
    $name = $nameParts[0] . ' ' . $nameParts[count($nameParts) - 1];
 
  } else {
    // If user not found, set a default value or handle it
    $fullName = "Guest";
  }

  $stmt->close();

  $customerQuery = "SELECT custId FROM customers WHERE userId = ?";
  $stmt = $con->prepare($customerQuery);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $customerResult = $stmt->get_result();

  if ($customerResult->num_rows > 0) {
      $customerRow = $customerResult->fetch_assoc();
      $custId = $customerRow['custId']; 
  } else {
      echo "No admin found for this user.";
  }

  $stmt->close();
} else {
  echo "No user ID in session";
  exit();
}
  // Fetch notifications from action_user_logs table
  $notifications_query = "SELECT COUNT(*) AS unread_count FROM user_action_logs WHERE custId = ? AND status = 'unread'";
  $stmt = $con->prepare($notifications_query);
  $stmt->bind_param("i", $custId);
  $stmt->execute();
  $notifications_result = $stmt->get_result();
  $notifications = $notifications_result->fetch_assoc();
  $unread_count = $notifications['unread_count'];

  $stmt->close();

  // Fetch the latest 5 notifications
  $recent_notifications_query = "SELECT action, actionDate, actionId, status FROM user_action_logs WHERE custId = ? ORDER BY actionDate DESC LIMIT 99";
  $stmt = $con->prepare($recent_notifications_query);
  $stmt->bind_param("i", $custId);
  $stmt->execute();
  $recent_notifications_result = $stmt->get_result();

  $recent_notifications = [];
  while ($row = $recent_notifications_result->fetch_assoc()) {
      $recent_notifications[] = $row;
  }

  $stmt->close();

// Check if the 'page' parameter exists in the URL
if (isset($_GET['page_title'])) {
    $page_title = $_GET['page_title'];

    // Set the title based on the page parameter
    switch ($page_title) {
        case 'dashboard':
            $page_title = 'Dashboard';
            break;
        case 'services':
            $page_title = 'Services';
            break;
        case 'products':
            $page_title = 'Products';
            break;
        case 'payments':
            $page_title = 'Payments';
            break;
        case 'tickets':
            $page_title = 'Raise Ticket';
            break;
            case 'transDoc':
              $page_title = 'Transaction Documents';
              break;
        case 'view service':
          $page_title = 'View Service';
          break;
        case 'view order':
          $page_title = 'View Order';
          break;
        case 'view ticket':
          $page_title = 'View Ticket';
          break;
        default:
            $page_title = 'Dashboard';
    }
}
?>
<!DOCTYPE html>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" type="text/css" href="../css/sidebar.css">
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- for DataTable -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" />
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<body>

<!-- Sidebar -->
<div class="w3-sidebar w3-bar-block w3-border-right bg oval-button bg" style="display:none" id="mySidebar">
  <button onclick="w3_close()" class="w3-bar-item w3-small w3-red oval-button">Close &times;</button>
  <a href="/traders/customer/dashboard.php" class="w3-bar-item w3-button custom-btn oval-button"> <i class="bi bi-speedometer"></i> Dashboard</a>
  <a href="/traders/customer/customerServiceList.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-gear-wide-connected"></i> Services</a>
  <a href="/traders/customer/services_customer.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-tools"></i> Request Service</a>
  <a href="/traders/customer/products_customer.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-box-fill"></i> Products</a>
  <a href="/traders/customer/payments_customer.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-credit-card-fill"></i> Payments</a>
  <a href="/traders/customer/tickets_customer.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-ticket-fill"></i> Raise Ticket</a>
  <!-- Dropdown for Statuses -->
  <a href="javascript:void(0)" class="w3-bar-item w3-button custom-btn oval-button" onclick="toggleDropdown()"><i class="bi bi-clipboard2-data-fill"></i> Statuses &#9662;</a>
  <div id="statusDropdown" class="w3-hide w3-white w3-bar-block">
    <a href="/traders/customer/vservice_customer.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-gear-fill"></i> View Service</a>
    <a href="/traders/customer/vorder_customer.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-bag-fill"></i> View Order</a>
    <a href="/traders/customer/vticket_customer.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-ticket-perforated-fill"></i> View Ticket</a>
  </div>
  <a href="/traders/customer/customer_transDoc.php" class="w3-bar-item w3-button custom-btn oval-button"><i class="bi bi-file-earmark-text-fill"></i> Transaction Documents</a>
  <!-- <br><br><br><br><br>
  <a href="logout.php" class="w3-bar-item w3-button w3-blue-grey oval-button"><i class="bi bi-box-arrow-left"></i> Logout</a> -->
</div>
  
<!-- Page Content -->
<div class="header-container w3-container">
    <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
    <h1><?php echo $page_title; ?></h1>
    <div class="notification-badge">
    <button class="btn btn-transparent dropdown-toggle" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="true">
        <i class="bi bi-bell-fill"></i>
        <?php if ($unread_count > 0): ?>
            <span class="badge badge-danger"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
        <?php if (!empty($recent_notifications)): ?>
            <?php foreach ($recent_notifications as $notification): ?>
                <li class="d-flex justify-content-between align-items-center py-2 px-3">
                    <a class="dropdown-item notification-link d-flex justify-content-between w-100" href="#" data-id="<?php echo $notification['actionId']; ?>">
                        <div class="notification-content">
                            <strong><?php echo $notification['action']; ?></strong>
                            <small class="text-muted d-block"><?php echo date('Y-m-d H:i:s', strtotime($notification['actionDate'])); ?></small>
                        </div>
                        <div class="mr-2">
                        <div style="margin-left: 10px;">
                          <?php if ($notification['status'] == 'unread'): ?>
                              <button class="btn btn-sm btn-success mark-as-read-btn" data-id="<?php echo $notification['actionId']; ?>">Mark as Read</button>
                          <?php else: ?>
                              <button class="btn btn-sm btn-secondary" disabled>Read</button>
                          <?php endif; ?>
                      </div>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li><a class="dropdown-item text-center" href="#">No recent notifications</a></li>
        <?php endif; ?>
        <li class="text-center mt-2">
            <button id="clearNotifications" class="dropdown-item text-danger" style="cursor: pointer;">
                Clear All Notifications
            </button>
        </li>
    </ul>
</div>

      <button class="btn btn-transparent dropdown-toggle " type="button" data-bs-toggle="dropdown" aria-expanded="true">
        <span class="username"><?php echo $name; ?></span>
      </button>
      
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="/traders/userDetails.php?userId=<?php echo $_SESSION['userId']; ?>">User Details</a></li>
        <li><a class="dropdown-item" href="/traders/customer/updateUserDetails.php?userId=<?php echo $_SESSION['userId']; ?>">Edit Profile</a></li>
        <li><a class="dropdown-item" href="/traders/logout.php">Logout</a></li>
      </ul>
</div>

<script>
function w3_open() {
  document.getElementById("mySidebar").style.display = "block";
}

function w3_close() {
  document.getElementById("mySidebar").style.display = "none";
}

function toggleDropdown() {
  var x = document.getElementById("statusDropdown");
  if (x.classList.contains("w3-hide")) {
    x.classList.remove("w3-hide");
  } else {
    x.classList.add("w3-hide");
  }
}
</script>
<script>
document.querySelectorAll('.mark-as-read-btn').forEach(button => {
    button.addEventListener('click', function() {
        const actionId = this.getAttribute('data-id');

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "customer_mark_as_read.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Update the notification status
                const notificationLink = document.querySelector(`[data-id="${actionId}"]`);
                const button = this;

                if (xhr.responseText === "Notification marked as read.") {
                    // Disable the button and update text
                    button.disabled = true;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-secondary');
                    button.innerText = 'Read';
                }
            }
        };
        xhr.send("actionId=" + actionId);
    });
});

// AJAX function to clear all notifications
document.getElementById("clearNotifications").addEventListener("click", function() {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "customer_update_notifications.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // Clear the notifications from the UI
            document.querySelector(".notification-dropdown").innerHTML = '<li><a class="dropdown-item" href="#">No recent notifications</a></li>';
        }
    };
    xhr.send("custId=" + <?php echo $custId; ?>);
});

$(document).ready(function () {
    $('#dataTable').DataTable({
        columnDefs: [
            { targets: '_all', defaultContent: '-' }
        ]
    });
});
</script>
</body>
</html>
