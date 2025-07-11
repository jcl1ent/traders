<?php
include("logincode.php");
$page_title = "View Ticket";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");

if (isset($_POST['deleteTicket'])) {
    $tickNo = $_POST['tickNo'];

    // Prepare and execute the delete statement
    $delete_query = "DELETE FROM ticket WHERE tickNo = ?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $tickNo);

    if ($stmt->execute()) {
        // Optionally set a session message for success or failure
        echo "<script>alert('Ticket deleted successfully.')</script>";
        echo '<script>window.location="vticket_customer.php"</script>';
    } else {
        echo "<script>alert('Failed to delete the ticket.')</script>";
        echo '<script>window.location="vticket_customer.php"</script>';
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
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
                                                <th scope="col">Ticket Number</th>
                                                <th scope="col">Title</th>
                                                <th scope="col">Description</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Issue Date</th>
                                                <th scope="col">Action</th>                                            
                                            </tr>
                                        </thead>
                                        <tbody>
    <?php
    if (isset($_SESSION['email']) && isset($_SESSION['userId'])) {
        $userId = $_SESSION['userId'];
        $email = $_SESSION['email'];

        // Get the information from the orders
        $vticket_query = "SELECT * FROM ticket WHERE userId = ?";
        $stmt_vticket = $con->prepare($vticket_query);
        $stmt_vticket->bind_param("i", $userId);
        $stmt_vticket->execute();
        $result_ticket = $stmt_vticket->get_result();

        if ($result_ticket->num_rows > 0) {
            while ($row = $result_ticket->fetch_assoc()) {
                ?>
                <tr class="text-center">
                    <td data-label="Ticket Number"><?php echo $row['tickNo']; ?></td>
                    <td data-label="Title"><?php echo $row['title']; ?></td>
                    <td data-label="Description"><?php echo $row['description']; ?></td>
                    <td data-label="Status"><?php echo $row['status']; ?></td>
                    <td data-label="Issue Date"><?php echo $row['issueDate']; ?></td>
                    <td data-label="Action">
                        <?php if ($row['status'] === 'Pending') { ?>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-ticket-no="<?php echo $row['tickNo']; ?>">
                                <i class="bi bi-trash3"></i> Delete
                            </button>
                        <?php } ?>
                        <?php 
    echo "<!-- Debugging: status: " . $row['status'] . " -->";
    if ($row['status'] === 'Closed') { ?>
        <div class="text-center">
            <form action="ticket_feedback.php" method="GET" class="d-inline">
                <input type="hidden" name="userId" value="<?php echo $loggedUserId; ?>">
                <input type="hidden" name="trscnType" value="<?php echo htmlspecialchars($row['title']); ?>">
                <input type="hidden" name="tickNo" value="<?php echo htmlspecialchars($row['tickNo']); ?>">
                <button type="submit" class="btn btn-warning">Feedback</button>
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
    </div>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this ticket?
      </div>
      <div class="modal-footer">
        <form method="POST" action="vticket_customer.php" id="deleteForm">
          <input type="hidden" name="tickNo" id="tickNo">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="deleteTicket" class="btn btn-danger">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>   
</body>
<script type="text/javascript">
    // Trigger the modal and set the orderNo value
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var tickNo = button.getAttribute('data-ticket-no'); // Extract orderNo from data-* attributes
        var inputTicketNo = deleteModal.querySelector('#tickNo'); // Get the hidden input inside the form
        inputTicketNo.value = tickNo; // Set the orderNo value in the hidden input
    });
</script>
</html>
