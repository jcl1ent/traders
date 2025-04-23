<?php
$page_title = "Admin Manpower";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if (isset($_POST['deleteManpower'])) {
    $mpId = $_POST['mpId'];

    // Prepare and execute the delete statement
    $delete_query = "UPDATE manpower SET mpArchive = 1 WHERE mpId = ?";
    $stmt = $con->prepare($delete_query);
    $stmt->bind_param("i", $mpId);

    if ($stmt->execute()) {
        // Optionally set a session message for success or failure
        echo "<script>alert('Manpower removed successfully.')</script>";
        echo '<script>window.location="admin_manpower.php"</script>';
    } else {
        echo "<script>alert('Failed to removed manpower.')</script>";
        echo '<script>window.location="admin_manpower.php"</script>';
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
                        <div class="add_manpower d-flex justify-content-end mt-2 me-2" style="gap: 10px;">
                            <a href="admin_manpower.php">
                                <button type="button" class="btn  btn-secondary bg-gradient">
                                <i class="bi bi-arrow-left"></i> Back
                                </button>
                            </a>
                                                                                       
                        </div>
                            <div class="card-body">
                                <div class="row shadow">
                                    
                                    <?php
                                    // Fetch manpower entries
                                    $query = "SELECT * FROM manpower 
                                    WHERE mpArchive  = 1 ";

                                    $result = mysqli_query($con, $query);

                                    while ($row = mysqli_fetch_array($result)) {
                                        $imageData = base64_encode($row['mpImg']); 
                                    ?>
                                    <div class="col-lg-4 col-md-6 col-sm-12 p-2">
                                        <div class="border border-dark rounded p-3">
                                            <div class="delete_button d-flex justify-content-end mb-3" style="gap: 10px;">
                                            <a href="admin_updateManpower.php?mpId=<?php echo $row['mpId']; ?>">
                                                <button type="button" class="btn btn-success">
                                                <i class="bi bi-arrow-clockwise"></i>Update
                                                </button>
                                            </a>
                                            </div>
                                            <!-- Added border and padding to each card -->
                                            <form method="POST" action="admin_manpower.php">
                                                <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid rounded" style="height: 150px; object-fit: cover;">
                                                <div class="d-flex flex-column align-items-center text-center mt-2">
                                                    <p>Manpower ID: <span class="fw-bold"><?= $row['mpId'] ?></span></p>
                                                    <p>Name: <span class="fw-bold"><?php echo $row['fullName']; ?></span></p>
                                                    <p>Age: <span class="fw-bold"><?php echo $row['age']; ?></span></p>
                                                    <p>Address: <span class="fw-bold"><?php echo $row['address']; ?></span></p>
                                                    <p>Contact No: <span class="fw-bold"><?php echo $row['contactNo']; ?></span></p>
                                                    <p>Status: <span class="fw-bold"><?php echo $row['mpStatus']; ?></span></p>
                                                    
                                                      
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
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this manpower?
      </div>
      <div class="modal-footer">
        <form method="POST" action="admin_manpower.php" id="deleteForm">
          <input type="hidden" name="mpId" id="mpId">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="deleteManpower" class="btn btn-danger">Yes</button>
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
        var mpId = button.getAttribute('data-mp-no'); // Extract pdngReqsNo
        var inputmpId = deleteModal.querySelector('#mpId'); // Get the hidden input inside the form
        inputmpId.value = mpId; // Set the pdngReqsNo value in the hidden input
    });
</script>