<?php
include("logincode.php");
$page_title = "Transaction Documents";
include("sidebar.php");
include("includes/header.php");
include("dbcon.php");

if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId'];

// Fetch document requests for the logged-in user
$requestSql = "SELECT dr.ReqNo, dr.DocNo, dr.ReqDate, dr.status, dr.description, d.DocName 
               FROM documents_request dr 
               LEFT JOIN documents d ON dr.DocNo = d.DocNo 
               WHERE dr.userId = ? AND dr.status != 'Done'
               ORDER BY dr.ReqNo DESC;";
$requestStmt = $con->prepare($requestSql);
$requestStmt->bind_param("i", $userId);
$requestStmt->execute();
$requestResult = $requestStmt->get_result();

$requestDocuments = [];
if ($requestResult->num_rows > 0) {
    while ($row = $requestResult->fetch_assoc()) {
        $requestDocuments[] = $row;
    }
}

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['ReqNo'])) {
    $reqNo = $_GET['ReqNo'];

    // Prepare the SQL statement to delete the request
    $deleteSql = "DELETE FROM documents_request WHERE ReqNo = ?";
    $deleteStmt = $con->prepare($deleteSql);
    $deleteStmt->bind_param("i", $reqNo);

    if ($deleteStmt->execute()) {
        echo "<script>alert('Request deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error: " . htmlspecialchars($deleteStmt->error) . "');</script>";
    }

    $deleteStmt->close();
    // Refresh the page to show updated requests
    echo "<script>setTimeout(() => window.location.href = 'customer_docReq.php', 2000);</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>User Document Requests</title>
</head>
<body>
<div class="container mt-5">
    <h2>Your Document Requests</h2>
    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Request No</th>
                <th>Description</th>
                <th>Request Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($requestDocuments)): ?>
                <?php foreach ($requestDocuments as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['ReqNo']); ?></td>
                        <td><?= htmlspecialchars($request['description']); ?></td>
                        <td><?= htmlspecialchars($request['ReqDate']); ?></td>
                        <td>
                            <a href="?delete=true&ReqNo=<?= htmlspecialchars($request['ReqNo']); ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this request?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No document requests found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="customer_transDoc.php" class="btn btn-secondary mt-4">Back</a>
</div>  
</body>
</html>

<?php
$con->close();
?>