<?php
$page_title = "Archived Service";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if (isset($_POST['unArchive'])) {
    $servCode = $_POST['servCode'];

    // Prepare and execute the delete statement
    $delete_query = "UPDATE services SET servArchive = 'Available' WHERE servCode = ?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $servCode);

    if ($stmt->execute()) {
        // Optionally set a session message for success or failure
        echo "<script>alert('Service is now available.')</script>";
        echo '<script>window.location="admin_services.php"</script>';
    } else {
        echo "<script>alert('Failed to make service available.')</script>";
        echo '<script>window.location="admin_services.php"</script>';
    }

    $stmt->close();

}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="container-fluid">
        <div class="col">
            <div class="row">
                <div class="col">
                    
                    <div class="card">
                        <div class="d-flex justify-content-end mt-2 me-2" style="gap: 10px;">
                            <a href="admin_services.php">
                                <button type="button" class="btn  btn-secondary bg-gradient">
                                <i class="bi bi-arrow-left"></i> Back
                                </button>
                            </a>
                                                                                       
                        </div>
                            <div class="card-body">
                                <div class="row shadow">
                                    
                                    <?php
                                    // Fetch manpower entries
                                    $query = "SELECT * FROM services 
                                    WHERE servArchive  = 'Unavailable' ";

                                    $result = mysqli_query($con, $query);

                                    while ($row = mysqli_fetch_array($result)) {
                                        $imageData = base64_encode($row['servImg']); 
                                    ?>
                                    <div class="col-lg-4 col-md-6 col-sm-12 p-2">
                                        <div class="border border-dark rounded p-3">
                                            <div class="delete_button d-flex justify-content-end mb-3" style="gap: 10px;">
                                            <button type="button" class="btn  btn-success bg-gradient" data-bs-toggle="modal" data-bs-target="#deleteModal" data-serv-no="<?php echo $row['servCode']; ?>">
                                             Available
                                            </button>
                                            </div>
                                            <!-- Added border and padding to each card -->
                                            <form method="POST" action="admin_services.php">
                                                <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid rounded" style="height: 150px; object-fit: cover;">
                                                <div class="d-flex flex-column align-items-center text-center mt-2">
                                                    <p>Service ID: <span class="fw-bold"><?= $row['servCode'] ?></span></p>
                                                    <p>Service: <span class="fw-bold"><?php echo $row['servName']; ?></span></p>
                                                    <p>Rate: <span class="fw-bold"><?php echo $row['rateService']; ?></span></p>    
                                                </div>
                                                    
                                            </form>
                                        </div> <!-- End of bordered card -->
                                    </div>
                                <?php } ?>
                                
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
        <h5 class="modal-title" id="deleteModalLabel">Confirm Availability</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to make this service available?
      </div>
      <div class="modal-footer">
        <form method="POST" action="admin_archivedService.php" id="deleteForm">
          <input type="hidden" name="servCode" id="servCode">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="unArchive" class="btn btn-danger">Yes</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<script type="text/javascript">
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var servCode = button.getAttribute('data-serv-no'); // Extract pdngReqsNo
        var inputservCode = deleteModal.querySelector('#servCode'); // Get the hidden input inside the form
        inputservCode.value = servCode; // Set the pdngReqsNo value in the hidden input
    });
</script>