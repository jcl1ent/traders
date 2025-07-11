<?php
$page_title = "Staff Transaction Documents";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId']; // Get userId from session

// Handle Folder Deletion
if (isset($_POST['delete_folder'])) {
    $folderId = intval($_POST['folder_id']); // Get the folder ID from the form

    // Delete the folder record from the database
    $deleteFolderSql = "DELETE FROM folders WHERE FolderId = ?";
    $deleteFolderStmt = $con->prepare($deleteFolderSql);
    $deleteFolderStmt->bind_param("i", $folderId);

    if ($deleteFolderStmt->execute()) {
        echo "<script>
    alert('Folder deleted successfully!');
    window.location.href = 'staff_folders.php';
</script>";
    } else {
        echo "<div class='alert alert-danger'>Error deleting folder: " . htmlspecialchars($deleteFolderStmt->error) . "</div>";
    }
    $deleteFolderStmt->close();
}

// Retrieve existing folders for the logged-in user
$folders = [];
$sql = "SELECT * FROM folders WHERE userId = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();
while ($folder = $result->fetch_assoc()) {
    $folders[] = $folder;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
</head>
<body>
    
<div class="container mt-5">
<h2 class="mb-4">Existing Folders</h2>
    <ul class="list-group mb-4">
        <?php if (count($folders) > 0): ?>
            <?php foreach ($folders as $folder): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($folder['FolderName']) ?>
                    
                    <div>
                        <a href="staff_viewfolder.php?view_folder=<?= urlencode($folder['FolderName']) ?>" class="btn btn-info btn-sm me-2">View Files</a>

                        <!-- Delete button form -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="folder_id" value="<?= $folder['FolderId'] ?>">
                            <button type="submit" name="delete_folder" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this folder?');">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="list-group-item">No folders found.</li>
        <?php endif; ?>
    </ul>
    <a href="staff_transDocs.php" class="btn btn-secondary mt-4">Back</a>
</div>

</body>
</html>