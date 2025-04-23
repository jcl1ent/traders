<?php
$page_title = "Admin Transaction Documents";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php"); 
include("includes/header.php"); 

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId']; // Get userId from session

// Create a directory for uploaded documents if it doesn't exist
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) { // Create the directory with permissions
        die("Failed to create upload directory: $uploadDir");
    }
}

// Check if a delete request was made
if (isset($_POST['delete_doc'])) {
    $DocNo = $_POST['DocNo'];
    $deleteSql = "UPDATE documents SET Status = 'Deleted' WHERE DocNo = ?";
    $deleteStmt = $con->prepare($deleteSql);
    $deleteStmt->bind_param("i", $DocNo);

    if ($deleteStmt->execute()) {
        echo "<script>
    alert('Document deleted successfully!');
    window.location.href = 'admin_searchDoc.php';
</script>";
    } else {
        echo "<div class='alert alert-danger'>Error deleting document: " . htmlspecialchars($deleteStmt->error) . "</div>";
    }

    $deleteStmt->close();
}

// Search Functionality
$searchQuery = '';
if (isset($_POST['search'])) {
    $searchQuery = $_POST['search_query'];
}

// Retrieve documents, excluding those marked as Deleted
$sql = "SELECT * FROM documents WHERE Status != 'Deleted' AND DocName LIKE ?";
$stmt = $con->prepare($sql);
$searchTerm = '%' . $searchQuery . '%';
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$documents = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Uploaded Documents</h2>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <input type="text" name="search_query" class="form-control" placeholder="Search document..." value="<?= htmlspecialchars($searchQuery) ?>">
        </div>
        <button type="submit" name="search" class="btn btn-primary">Search</button>
    </form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Document Name</th>
                <th>Document Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($documents) > 0): ?>
                <?php foreach ($documents as $document): ?>
                    <tr>
                        <td><?= htmlspecialchars($document['DocName']) ?></td>
                        <td><?= htmlspecialchars($document['DocType']) ?></td>
                        <td><?= htmlspecialchars($document['Status']) ?></td>
                        <td>
                            <!-- Ensure the file opens inline in the browser -->
                            <a href="<?= htmlspecialchars($document['DocumentPath']) ?>" class="btn btn-success btn-sm" target="_blank">View</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="DocNo" value="<?= htmlspecialchars($document['DocNo']) ?>">
                                <button type="submit" name="delete_doc" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this document?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No documents uploaded.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="admin_transDocs.php" class="btn btn-secondary mt-4">Back</a>
</div>
</body>
</html>
