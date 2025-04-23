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

// Fetch documents sent to other users
$sentDocumentsSql = "SELECT d.DocNo, d.DocName, d.Document, d.DocumentPath, f.FolderName 
                     FROM documents_request dr 
                     JOIN documents d ON dr.DocNo = d.DocNo 
                     JOIN folders f ON d.FolderId = f.FolderId 
                     WHERE dr.userId = ?";
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
$sql = "SELECT userId FROM users WHERE email = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId);
    $stmt->fetch();
    $stmt->close();

    // Use userId to get address from customers table
    $sql = "SELECT address, custId, firstname, middlename, lastname FROM customers WHERE userId = ?";
    $stmt = $con->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // Bind the results to variables
        $stmt->bind_result($address, $custId, $firstname, $middlename, $lastname);

        if (!$stmt->fetch()) {
            // If no result, set default values
            $address = '';
            $custId = null;
            $firstname = '';
            $middlename = '';
            $lastname = '';
        }

        $stmt->close();
    } else {
        // Handle the case where the statement couldn't be prepared
        die("Error preparing the SQL query: " . $con->error);
    }
    // Combine the full name (optional)
    $fullName = trim("$firstname $middlename $lastname");

    // Fetch the first available adminId from admin table
    $sql = "SELECT adminId FROM admin LIMIT 1";  
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($adminId);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT staffId FROM staffs";  
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($staffId);
    $stmt->fetch();
    $stmt->close();


// Check for form submission for document request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the form data
    $reqDoc = $_POST['description']; // Map description to ReqDoc
    $status = 'Pending'; // Set status to Pending
    $reqDate = date('Y-m-d H:i:s'); // Get current datetime
    $docNo = null; // Set DocNo to NULL explicitly

    $log_action_query2 = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
    $action = $fullName . ' submitted a document request.';
    $status = 'unread';
    $log_action_stmt2 = $con->prepare($log_action_query2);
    $log_action_stmt2->bind_param("iss", $adminId, $action, $status);
    $log_action_stmt2->execute();
    $log_action_stmt2->close();

    $log_action_query2 = "INSERT INTO user_action_logs (staffId, action, status) VALUES (?, ?, ?)";
    $action = $fullName . ' submitted a document request.';
    $status = 'unread';
    $log_action_stmt2 = $con->prepare($log_action_query2);
    $log_action_stmt2->bind_param("iss", $staffId, $action, $status);
    $log_action_stmt2->execute();
    $log_action_stmt2->close();

    // Prepare the SQL statement to insert the request
    $sql = "INSERT INTO documents_request (DocNo, userId, ReqDate, description, status) 
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("iisss", $docNo, $userId, $reqDate, $reqDoc, $status);

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>alert('Request submitted successfully.');</script>";
        echo "<script>setTimeout(() => window.location.href = 'customer_transDoc.php', 2000);</script>";
    } else {
        echo "<script>alert('Error: " . htmlspecialchars($stmt->error) . "');</script>";
    }

    // Close the statement
    $stmt->close();
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

<div class="d-flex justify-content-end mb-4">
    <a href="customer_docReq.php" class="btn btn-primary">Document Requests</a>
</div>

    <h2>Document Request Form</h2>
    <form action="" method="POST" class="mt-4">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter a description..." required></textarea>
        </div>
        
        <input type="hidden" name="userId" value="<?= htmlspecialchars($userId); ?>">
        <button type="submit" class="btn btn-success">Submit Request</button>
    </form>
    <br>

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
</div>
</body>
</html>

<?php
$con->close();
?>

