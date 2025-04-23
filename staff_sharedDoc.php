<?php
$page_title = "Staff Transaction Documents";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId'];

// Fetch documents sent to other users
$sentDocumentsSql = "SELECT d.DocNo, d.DocName, d.Document, d.DocumentPath, f.FolderName 
                     FROM shared_docs sd 
                     JOIN documents d ON sd.DocNo = d.DocNo 
                     JOIN folders f ON d.FolderId = f.FolderId 
                     WHERE sd.userId = ?";
$sentStmt = $con->prepare($sentDocumentsSql);
$sentStmt->bind_param("i", $userId);
$sentStmt->execute();
$sentResult = $sentStmt->get_result();

$sentDocuments = [];
if ($sentResult->num_rows > 0) {
    while ($row = $sentResult->fetch_assoc()) {
        $sentDocuments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Request Form</title>
</head>
<body>
<div class="container mt-5">
<h3 class="mt-4">Shared Documents</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Document Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sentDocuments)): ?>
                <?php foreach ($sentDocuments as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['DocName']); ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($doc['DocumentPath']); ?>" target="_blank" class="btn btn-info btn-sm">View</a>
                            <a href="<?= htmlspecialchars($doc['DocumentPath']); ?>" download class="btn btn-danger btn-sm">Download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" class="text-center">No sent documents available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="staff_transDocs.php" class="btn btn-secondary mt-4">Back</a>
</div>
</body>
</html>

<?php
$con->close();
?>