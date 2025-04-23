<?php
$page_title = "Staff Transaction Documents";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

$requestSql = "SELECT dr.ReqNo, dr.DocNo, dr.ReqDate, dr.status, dr.description, d.DocName 
                FROM documents_request dr 
                LEFT JOIN documents d ON dr.DocNo = d.DocNo 
                WHERE dr.status != 'Done' 
                ORDER BY dr.ReqNo DESC;";
$requestStmt = $con->prepare($requestSql);
$requestStmt->execute();
$requestResult = $requestStmt->get_result();

$requestDocuments = [];
if ($requestResult->num_rows > 0) {
    while ($row = $requestResult->fetch_assoc()) {
        $requestDocuments[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Document Requests</title>
</head>
<body>
<div class="container mt-5">
    <h2>Document Requests</h2>
    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Request Number</th>
                <th>Description</th>
                <th>Request Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($requestDocuments)): ?>
                <?php foreach ($requestDocuments as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['ReqNo']); ?></td>
                        <td><?= htmlspecialchars($request['description']); ?></td>
                        <td><?= htmlspecialchars($request['ReqDate']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No document requests found.</td>
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