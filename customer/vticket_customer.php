<?php
require_once __DIR__ . "/../logincode.php";
require_once __DIR__ . "/../dbcon.php";

if (isset($_POST['deleteTicket'])) {
    $tickNo = $_POST['tickNo'];

    // Prepare and execute the delete statement
    $delete_query = "DELETE FROM ticket WHERE tickNo = ?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $tickNo);

    if ($stmt->execute()) {
        $_SESSION['status'] = "Ticket deleted successfully.";
    } else {
        $_SESSION['status'] = "Failed to delete the ticket.";
    }

    $stmt->close();
    redirect('customer/vticket_customer.php');
}

// Handle feedback submission
if (isset($_POST['submitFeedback'])) {
    $userId = $_SESSION['userId'];
    $tickNo = $_POST['tickNo'];
    $title = $_POST['feedbackTitle'];
    $description = $_POST['feedbackDescription'];
    $satisfaction = isset($_POST['satisfaction']) && is_numeric($_POST['satisfaction']) ? (int)$_POST['satisfaction'] : 0;
    $trscnType = "Ticket No. " . htmlspecialchars($tickNo) . " - " . htmlspecialchars($title);

    // Check if feedback already submitted for this ticket
    $checkQuery = "SELECT COUNT(*) FROM feedback WHERE userId = ? AND trscnType = ?";
    $checkStmt = $con->prepare($checkQuery);
    $checkStmt->bind_param("is", $userId, $trscnType);
    $checkStmt->execute();
    $checkStmt->bind_result($feedbackCount);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($feedbackCount > 0) {
        $_SESSION['status'] = "You have already submitted feedback for this ticket.";
    } else {
        // Insert feedback with satisfaction rating
        $insertQuery = "INSERT INTO feedback (userId, trscnType, description, satisfaction) VALUES (?, ?, ?, ?)";
        $insertStmt = $con->prepare($insertQuery);
        $insertStmt->bind_param("issi", $userId, $trscnType, $description, $satisfaction);
        
        if ($insertStmt->execute()) {
            $_SESSION['status'] = "Feedback submitted successfully!";
        } else {
            $_SESSION['status'] = "Error submitting feedback. Please try again.";
        }
        $insertStmt->close();
    }
    redirect('customer/vticket_customer.php');
}

$page_title = "View Ticket";
include __DIR__ . "/sidebar.php";
include __DIR__ . "/../includes/header.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?= url('css/style.css') ?>">
</head>

<body>
    <div class="py-3">
        <div class="container">
            <?php
            if (isset($_SESSION['status'])) {
                echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
                ' . $_SESSION['status'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
                unset($_SESSION['status']);
            }
            ?>
        </div>
    </div>
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
                                                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#feedbackModal" data-ticket-no="<?php echo $row['tickNo']; ?>">
                                                                        <i class="bi bi-hand-thumbs-up"></i> Feedback
                                                                    </button>                                                                   
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
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" name="tickNo" id="tickNo">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                        <button type="submit" name="deleteTicket" class="btn btn-danger">Yes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Submit Feedback</h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="feedbackTickNo" name="tickNo" value="">
                        <input type="hidden" id="feedbackTitle" name="feedbackTitle" value="">
                        <input type="hidden" id="satisfactionRating" name="satisfaction" value="0">
                        <div class="rating mb-3">
                            <label class="form-label">Satisfaction Level:</label>
                            <div id="starContainer">
                                <span class="star" data-value="1" title="Very Poor">&#9733;</span>
                                <span class="star" data-value="2" title="Poor">&#9733;</span>
                                <span class="star" data-value="3" title="Fair">&#9733;</span>
                                <span class="star" data-value="4" title="Good">&#9733;</span>
                                <span class="star" data-value="5" title="Excellent">&#9733;</span>
                            </div>
                            <p><small id="ratingText" class="text-muted">Click to rate</small></p>
                        </div>
                        <div class="form-group mb-3">
                            <label for="feedbackDescription" class="form-label">Your Feedback:</label>
                            <textarea class="form-control" id="feedbackDescription" name="feedbackDescription" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submitFeedback" class="btn btn-primary">Submit Feedback</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script type="text/javascript">
    // Trigger the modal and set the orderNo value
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var tickNo = button.getAttribute('data-ticket-no'); // Extract orderNo from data-* attributes
        var inputTicketNo = deleteModal.querySelector('#tickNo'); // Get the hidden input inside the form
        inputTicketNo.value = tickNo; // Set the orderNo value in the hidden input
    });

    var feedbackModal = document.getElementById('feedbackModal');
    feedbackModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var tickNo = button.getAttribute('data-ticket-no');
        var titleText = button.closest('tr').querySelector('td:nth-child(2)').textContent;
        document.getElementById('feedbackTickNo').value = tickNo;
        document.getElementById('feedbackTitle').value = titleText;
        document.getElementById('feedbackDescription').value = '';
        resetStarRating(); // Reset rating when modal opens
    });

    // Star rating functionality
    function resetStarRating() {
        document.querySelectorAll('.star').forEach(star => star.classList.remove('active'));
        document.getElementById('satisfactionRating').value = 0;
        document.getElementById('ratingText').textContent = 'Click to rate';
    }

    document.querySelectorAll('.star').forEach(star => {
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            document.getElementById('satisfactionRating').value = value;
            
            // Highlight stars
            document.querySelectorAll('.star').forEach(s => {
                if (s.getAttribute('data-value') <= value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            
            // Update label
            const labels = { 5: 'Excellent', 4: 'Good', 3: 'Fair', 2: 'Poor', 1: 'Very Poor' };
            document.getElementById('ratingText').textContent = labels[value] + ' (' + value + '/5)';
        });

        // Hover effect
        star.addEventListener('mouseover', function() {
            const value = this.getAttribute('data-value');
            document.querySelectorAll('.star').forEach(s => {
                if (s.getAttribute('data-value') <= value) {
                    s.classList.add('hover');
                } else {
                    s.classList.remove('hover');
                }
            });
        });
    });

    document.getElementById('starContainer').addEventListener('mouseleave', function() {
        document.querySelectorAll('.star').forEach(s => s.classList.remove('hover'));
    });
</script>

</html>