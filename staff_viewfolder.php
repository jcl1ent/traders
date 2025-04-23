<?php
$page_title = "Staff Transaction Documents";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId'];
if (!isset($_GET['view_folder'])) {
    die("No folder specified.");
}

$folderName = trim($_GET['view_folder']);
$uploadDir = 'uploads/' . $folderName . '/';

// Check if the directory exists
if (!is_dir($uploadDir)) {
    die("Folder does not exist: " . htmlspecialchars($uploadDir));
}

if (isset($_POST['delete_doc'])) {
    $DocNo = $_POST['DocNo'];
    $deleteSql = "UPDATE documents SET Status = 'Deleted' WHERE DocNo = ?";
    $deleteStmt = $con->prepare($deleteSql);
    $deleteStmt->bind_param("i", $DocNo);

    if ($deleteStmt->execute()) {
        echo "<div class='alert alert-success'>Document marked as deleted.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error deleting document: " . htmlspecialchars($deleteStmt->error) . "</div>";
    }

    $deleteStmt->close();
}

// Get all users except those with role 'admin' and excluding the logged-in user
$userSql = "SELECT userId, fullName AS username FROM users WHERE role != 'admin' AND userId != ?";
$userStmt = $con->prepare($userSql);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$allUsers = $userResult->fetch_all(MYSQLI_ASSOC);
$userStmt->close();

$sql = "SELECT * FROM documents WHERE FolderId IN (SELECT FolderId FROM folders WHERE FolderName = ?) AND Status != 'Deleted'";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $folderName);
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
    <h2 class="mb-4">Documents in Folder: <?= htmlspecialchars($folderName) ?></h2>
    <ul class="list-group">
        <?php if (count($documents) > 0): ?>
            <?php foreach ($documents as $document): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span><?= htmlspecialchars($document['DocName']) ?></span>
                        <span class="badge bg-success ms-3"><?= htmlspecialchars($document['Status']) ?></span>
                    </div>
                    <div class="d-flex">
                        <a href="<?= htmlspecialchars($uploadDir . $document['DocName']) ?>" target="_blank" class="btn btn-primary btn-sm me-2">View</a>
                        <a href="<?= htmlspecialchars($uploadDir . $document['DocName']) ?>" download class="btn btn-success btn-sm me-2">Download</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="DocNo" value="<?= htmlspecialchars($document['DocNo']) ?>">
                            <button type="submit" name="delete_doc" class="btn btn-danger btn-sm me-2">Delete</button>
                        </form>
                        <a href="send_doc_to_user2.php?DocNo=<?= htmlspecialchars($document['DocNo']) ?>" class="btn btn-info btn-sm">Share</a>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="list-group-item">No documents found in this folder.</li>
        <?php endif; ?>
    </ul>
    <a href="staff_folders.php" class="btn btn-secondary mt-4">Back</a>
</div>

</body>
</html>
