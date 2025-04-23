<?php
$page_title = "Admin Inquiry Tickets";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if (isset($_POST['deleteTicket'])) {
    $tickNo = $_POST['tickNo'];

    // Prepare and execute the delete statement
    $delete_query = "DELETE FROM ticket WHERE tickNo=?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $tickNo);

    if ($stmt->execute()) {
        // Optionally set a session message for success or failure
        echo "<script>alert('Ticket removed successfully.')</script>";
        echo '<script>window.location="admin_tickets.php"</script>';
    } else {
        echo "<script>alert('Failed to removed ticket.')</script>";
        echo '<script>window.location="admin_tickets.php"</script>';
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
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Title</th>
                                                <th scope="col">Description</th>
                                                
                                                <th scope="col">Issue Date</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Action</th>                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                // Get the information from the orders
                                                $vticket_query = "SELECT * FROM ticket ORDER BY tickNo DESC";
                                                $stmt_vticket = $con->prepare($vticket_query);                                                
                                                $stmt_vticket->execute();
                                                $result_ticket = $stmt_vticket->get_result();

                                                if ($result_ticket->num_rows > 0) {
                                                    while ($row = $result_ticket->fetch_assoc()) {                                                                                                                      
                                                                ?>
                                                                <tr class="text-center">
                                                                    
                                                                    <td data-label="Ticket Number"><?php echo $row['tickNo']; ?></td> 
                                                                    <td data-label="Customer Name"><?php echo $row['custName']; ?></td>                                                                    
                                                                    <td data-label="TItle"><?php echo $row['title']; ?></td>
                                                                    <td data-label="Description"><?php echo $row['description']; ?></td>
                                                                    
                                                                    <td data-label="Issue Date"><?php echo $row['issueDate']; ?></td>    
                                                                    <td data-label="Status"><?php echo $row['status']; ?></td> 
                                                                    <td data-label="Delete">
                                                                        <div class="buttons d-flex justify-content-center" style="gap: 5px;">
                                                                            <a href="admin_updateTicket.php?tickNo=<?php echo $row['tickNo']; ?>">
                                                                                <button type="button" class="btn  btn-success">
                                                                                 Update
                                                                                </button>
                                                                            </a>
                                                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-ticket-no="<?php echo $row['tickNo']; ?>">
                                                                                <i class="bi bi-trash3"></i> Delete
                                                                            </button>
                                                                        </div>
                                                                    
                                                                    </td>                                                               
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
        <form method="POST" action="admin_tickets.php" id="deleteForm">
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