<?php
include("logincode.php");
include("dbcon.php");

if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId'];

// Handle document sending
if (isset($_POST['send_doc'])) {
    $userIdToSend = $_POST['userId']; 
    $DocNo = $_POST['DocNo'];        
    $DocName = $_POST['DocName'];

    $custId = null;
    $ReqNo = null;
    
    // Fetch role from the users table
    $roleSql = "SELECT role FROM users WHERE userId = ?";
    $roleStmt = $con->prepare($roleSql);
    
    if ($roleStmt) {
        $roleStmt->bind_param("i", $userIdToSend);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
    
        if ($roleResult->num_rows > 0) {
            $roleRow = $roleResult->fetch_assoc();
    
            // Check if the role is 'customer'
            if ($roleRow['role'] === 'customer') {
                // Fetch custId from the customers table if the user is a customer
                $custSql = "SELECT custId FROM customers WHERE userId = ?";
                $custStmt = $con->prepare($custSql);
    
                if ($custStmt) {
                    $custStmt->bind_param("i", $userIdToSend);
                    $custStmt->execute();
                    $custResult = $custStmt->get_result();
    
                    if ($custResult->num_rows > 0) {
                        $custRow = $custResult->fetch_assoc();
                        $custId = $custRow['custId'];  // Assign custId from customers table
                    }
                    $custStmt->close();
                }
            }
    
            // Fetch ReqNo from the documents_request table
            $reqNoSql = "SELECT ReqNo FROM documents_request WHERE userId = ?";
            $reqNoStmt = $con->prepare($reqNoSql);
    
            if ($reqNoStmt) {
                $reqNoStmt->bind_param("i", $userIdToSend);
                $reqNoStmt->execute();
                $reqNoResult = $reqNoStmt->get_result();
    
                if ($reqNoResult->num_rows > 0) {
                    $reqNoRow = $reqNoResult->fetch_assoc();
                    $ReqNo = $reqNoRow['ReqNo'];  // Assign ReqNo from documents_request table
                }
                $reqNoStmt->close();
            }
        }
        $roleStmt->close();
    }

    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'Your Request No.' .$ReqNo. ' for the document has been successfully shared'; 
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();
    
    // Insert into shared_docs
    $insertSql = "INSERT INTO shared_docs (userId, DocNo) VALUES (?, ?)";
    $insertStmt = $con->prepare($insertSql);

    if ($insertStmt) {
        $insertStmt->bind_param("ii", $userIdToSend, $DocNo);

        if ($insertStmt->execute()) {
            echo "<script>
    alert('Document shared successfully.');
    window.location.href = 'admin_folders.php';
</script>";
        } else {
            $message = "Error sharing document: " . htmlspecialchars($insertStmt->error);
        }
        $insertStmt->close(); // Close the insert statement
    } else {
        $message = "Error preparing insert statement: " . htmlspecialchars($con->error);
    }

    // Fetch ReqNo of the user for the specified DocNo
    $reqNoSql = "SELECT ReqNo FROM documents_request WHERE userId = ?";
    $reqNoStmt = $con->prepare($reqNoSql);

    if ($reqNoStmt) {
        $reqNoStmt->bind_param("i", $userIdToSend);
        $reqNoStmt->execute();
        $reqNoResult = $reqNoStmt->get_result();

        if ($reqNoResult->num_rows > 0) {
            $reqNoRow = $reqNoResult->fetch_assoc();
            $ReqNo = $reqNoRow['ReqNo'];

            // Update the documents_request table to set the status to "Done" and update ReqDate
            $requestSql = "UPDATE documents_request SET status = 'Done', ReqDate = NOW(), DocNo = ? WHERE ReqNo = ?";
            $requestStmt = $con->prepare($requestSql);

            if ($requestStmt) { // Check if preparation was successful
                $requestStmt->bind_param("ii", $DocNo, $ReqNo);

                if ($requestStmt->execute()) {
                    echo "<script>
    alert('Document shared successfully.');
    window.location.href = 'admin_folders.php';
</script>";
                } else {
                    $message = "Error updating request history: " . htmlspecialchars($requestStmt->error);
                }
                $requestStmt->close(); // Close only if it was successfully created
            } else {
                $message = "Error preparing request update statement: " . htmlspecialchars($con->error);
            }
        } 
}

}
// Fetch users for sending documents
$userSql = "SELECT userId, fullName AS username FROM users WHERE role != 'admin'";
$userStmt = $con->prepare($userSql);
$userStmt->execute();
$userResult = $userStmt->get_result();
$users = $userResult->fetch_all(MYSQLI_ASSOC);
$userStmt->close();

// Fetch documents shared with the user
$sql = "SELECT d.DocNo, d.DocName, d.Status FROM documents_request dr JOIN documents d ON dr.DocNo = d.DocNo WHERE dr.userId = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if the document to be sent is specified
$DocNoToSend = isset($_GET['DocNo']) ? $_GET['DocNo'] : null;

// Fetch document details if sending
if ($DocNoToSend) {
    $docSql = "SELECT * FROM documents WHERE DocNo = ?";
    $docStmt = $con->prepare($docSql);
    $docStmt->bind_param("i", $DocNoToSend);
    $docStmt->execute();
    $documentToSend = $docStmt->get_result()->fetch_assoc();
    $docStmt->close();
}

// Handle document download
if (isset($_GET['download'])) {
    $DocNo = $_GET['download'];

    // Fetch the document path from the database
    $downloadSql = "SELECT DocName, Document FROM documents WHERE DocNo = ?";
    $downloadStmt = $con->prepare($downloadSql);
    $downloadStmt->bind_param("i", $DocNo);
    $downloadStmt->execute();
    $downloadStmt->store_result();
    
    if ($downloadStmt->num_rows > 0) {
        $downloadStmt->bind_result($docName, $documentPath);
        $downloadStmt->fetch();

        $fullPath = realpath($documentPath);

        // Verify file exists before sending it
        if (file_exists($fullPath)) {
            // Set headers for the download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($docName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));

            // Output the file content
            readfile($fullPath);
            exit;
        } else {
            echo "Document not found.";
        }
    } else {
        echo "Document not found.";
    }

    $downloadStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <h4>Share Document</h4>
        <form method="POST">
            <div class="mb-3">
                <label for="userId" class="form-label">Select User</label>
                <select name="userId" id="userId" class="form-select" required>
                    <option value="">Choose a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['userId']) ?>"><?= htmlspecialchars($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="DocNo" value="<?= isset($documentToSend) ? $documentToSend['DocNo'] : '' ?>">
            <input type="hidden" name="DocName" value="<?= isset($documentToSend) ? htmlspecialchars($documentToSend['DocName']) : '' ?>">
            <button type="submit" name="send_doc" class="btn btn-primary" <?= isset($documentToSend) ? '' : 'disabled' ?>>Send Document</button>
        </form>
    </div>

    <a href="admin_transDocs.php" class="btn btn-secondary mt-4">Back</a>
</div>
</body>
</html>
<?php
include("logincode.php");
include("dbcon.php");

if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId'];

// Handle document sending
if (isset($_POST['send_doc'])) {
    $userIdToSend = $_POST['userId']; 
    $DocNo = $_POST['DocNo'];        
    $DocName = $_POST['DocName'];

    $custId = null;
    $ReqNo = null;
    
    // Fetch role from the users table
    $roleSql = "SELECT role FROM users WHERE userId = ?";
    $roleStmt = $con->prepare($roleSql);
    
    if ($roleStmt) {
        $roleStmt->bind_param("i", $userIdToSend);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
    
        if ($roleResult->num_rows > 0) {
            $roleRow = $roleResult->fetch_assoc();
    
            // Check if the role is 'customer'
            if ($roleRow['role'] === 'customer') {
                // Fetch custId from the customers table if the user is a customer
                $custSql = "SELECT custId FROM customers WHERE userId = ?";
                $custStmt = $con->prepare($custSql);
    
                if ($custStmt) {
                    $custStmt->bind_param("i", $userIdToSend);
                    $custStmt->execute();
                    $custResult = $custStmt->get_result();
    
                    if ($custResult->num_rows > 0) {
                        $custRow = $custResult->fetch_assoc();
                        $custId = $custRow['custId'];  // Assign custId from customers table
                    }
                    $custStmt->close();
                }
            }
    
            // Fetch ReqNo from the documents_request table
            $reqNoSql = "SELECT ReqNo FROM documents_request WHERE userId = ?";
            $reqNoStmt = $con->prepare($reqNoSql);
    
            if ($reqNoStmt) {
                $reqNoStmt->bind_param("i", $userIdToSend);
                $reqNoStmt->execute();
                $reqNoResult = $reqNoStmt->get_result();
    
                if ($reqNoResult->num_rows > 0) {
                    $reqNoRow = $reqNoResult->fetch_assoc();
                    $ReqNo = $reqNoRow['ReqNo'];  // Assign ReqNo from documents_request table
                }
                $reqNoStmt->close();
            }
        }
        $roleStmt->close();
    }

    $log_action_query = "INSERT INTO user_action_logs (custId, action, status) VALUES (?, ?, ?)";
    $action = 'Your Request No.' .$ReqNo. ' for the document has been successfully shared'; 
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $custId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();
    
    // Insert into shared_docs
    $insertSql = "INSERT INTO shared_docs (userId, DocNo) VALUES (?, ?)";
    $insertStmt = $con->prepare($insertSql);

    if ($insertStmt) {
        $insertStmt->bind_param("ii", $userIdToSend, $DocNo);

        if ($insertStmt->execute()) {
            echo "<script>
    alert('Document shared successfully.');
    window.location.href = 'admin_folders.php';
</script>";
        } else {
            $message = "Error sharing document: " . htmlspecialchars($insertStmt->error);
        }
        $insertStmt->close(); // Close the insert statement
    } else {
        $message = "Error preparing insert statement: " . htmlspecialchars($con->error);
    }

    // Fetch ReqNo of the user for the specified DocNo
    $reqNoSql = "SELECT ReqNo FROM documents_request WHERE userId = ? and status='unread'";
    $reqNoStmt = $con->prepare($reqNoSql);

    if ($reqNoStmt) {
        $reqNoStmt->bind_param("i", $userIdToSend);
        $reqNoStmt->execute();
        $reqNoResult = $reqNoStmt->get_result();

        if ($reqNoResult->num_rows > 0) {
            $reqNoRow = $reqNoResult->fetch_assoc();
            $ReqNo = $reqNoRow['ReqNo'];

            // Update the documents_request table to set the status to "Done" and update ReqDate
            $requestSql = "UPDATE documents_request SET status = 'Done', ReqDate = NOW(), DocNo = ? WHERE ReqNo = ?";
            $requestStmt = $con->prepare($requestSql);

            if ($requestStmt) { // Check if preparation was successful
                $requestStmt->bind_param("ii", $DocNo, $ReqNo);

                if ($requestStmt->execute()) {
                    echo "<script>
    alert('Document shared successfully.');
    window.location.href = 'admin_folders.php';
</script>";
                } else {
                    $message = "Error updating request history: " . htmlspecialchars($requestStmt->error);
                }
                $requestStmt->close(); // Close only if it was successfully created
            } else {
                $message = "Error preparing request update statement: " . htmlspecialchars($con->error);
            }
        } 
}

}
// Fetch users for sending documents
$userSql = "SELECT userId, fullName AS username FROM users WHERE role != 'admin'";
$userStmt = $con->prepare($userSql);
$userStmt->execute();
$userResult = $userStmt->get_result();
$users = $userResult->fetch_all(MYSQLI_ASSOC);
$userStmt->close();

// Fetch documents shared with the user
$sql = "SELECT d.DocNo, d.DocName, d.Status FROM documents_request dr JOIN documents d ON dr.DocNo = d.DocNo WHERE dr.userId = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if the document to be sent is specified
$DocNoToSend = isset($_GET['DocNo']) ? $_GET['DocNo'] : null;

// Fetch document details if sending
if ($DocNoToSend) {
    $docSql = "SELECT * FROM documents WHERE DocNo = ?";
    $docStmt = $con->prepare($docSql);
    $docStmt->bind_param("i", $DocNoToSend);
    $docStmt->execute();
    $documentToSend = $docStmt->get_result()->fetch_assoc();
    $docStmt->close();
}

// Handle document download
if (isset($_GET['download'])) {
    $DocNo = $_GET['download'];

    // Fetch the document path from the database
    $downloadSql = "SELECT DocName, Document FROM documents WHERE DocNo = ?";
    $downloadStmt = $con->prepare($downloadSql);
    $downloadStmt->bind_param("i", $DocNo);
    $downloadStmt->execute();
    $downloadStmt->store_result();
    
    if ($downloadStmt->num_rows > 0) {
        $downloadStmt->bind_result($docName, $documentPath);
        $downloadStmt->fetch();

        $fullPath = realpath($documentPath);

        // Verify file exists before sending it
        if (file_exists($fullPath)) {
            // Set headers for the download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($docName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));

            // Output the file content
            readfile($fullPath);
            exit;
        } else {
            echo "Document not found.";
        }
    } else {
        echo "Document not found.";
    }

    $downloadStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <h4>Share Document</h4>
        <form method="POST">
            <div class="mb-3">
                <label for="userId" class="form-label">Select User</label>
                <select name="userId" id="userId" class="form-select" required>
                    <option value="">Choose a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['userId']) ?>"><?= htmlspecialchars($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="DocNo" value="<?= isset($documentToSend) ? $documentToSend['DocNo'] : '' ?>">
            <input type="hidden" name="DocName" value="<?= isset($documentToSend) ? htmlspecialchars($documentToSend['DocName']) : '' ?>">
            <button type="submit" name="send_doc" class="btn btn-primary" <?= isset($documentToSend) ? '' : 'disabled' ?>>Send Document</button>
        </form>
    </div>

    <a href="admin_folders.php" class="btn btn-secondary mt-4">Back</a>
</div>
</body>
</html>
