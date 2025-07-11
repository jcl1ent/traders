<?php
$page_title = "Admin Transaction Documents";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php"); 
include("includes/header.php"); 

// Define the upload directory
$uploadDir = "uploads/"; 

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure userId is set from session
if (!isset($_SESSION['userId'])) {
    die("User not logged in. Please log in to continue.");
}

$userId = $_SESSION['userId']; // Get userId from session

// Handle Folder Creation
if (isset($_POST['create_folder'])) {
    $folderName = preg_replace('/[^a-zA-Z0-9_\- ]/', '', trim($_POST['folder_name']));
    
    if (empty($folderName)) {
        echo "<div class='alert alert-danger'>Folder name cannot be empty.</div>";
    } else {
        $insertFolderSql = "INSERT INTO folders (FolderName, userId) VALUES (?, ?)";
        $insertFolderStmt = $con->prepare($insertFolderSql);
        $insertFolderStmt->bind_param("si", $folderName, $userId); 

        if ($insertFolderStmt->execute()) {
            // Create the folder on the server
            $folderPath = $uploadDir . $folderName;
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0777, true); // Create the directory if it doesn't exist
            }
            echo "<script>
    alert('Folder \"$folderName\" created successfully!');
    window.location.href = 'admin_folders.php';
</script>";
        } else {
            error_log("Error creating folder: " . htmlspecialchars($insertFolderStmt->error), 3, 'error_log.txt');
            echo "<div class='alert alert-danger'>Error creating folder: " . htmlspecialchars($insertFolderStmt->error) . "</div>";
        }
        $insertFolderStmt->close();
    }
}

// Handle Document Upload
if (isset($_POST['upload'])) {
    $folderName = trim($_POST['folder_name']); // Get folder name from the input
    $folderPath = $uploadDir . $folderName . '/'; // Define the folder path

    // Check if the folder exists before uploading files
    if (!is_dir($folderPath)) {
        echo "<div class='alert alert-danger'>Folder '$folderName' does not exist. Please create it first.</div>";
    } else {
        $files = $_FILES['documents']; // Allow multiple file uploads

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $docName = $files['name'][$i];
            $docType = $files['type'][$i];
            $tmpName = $files['tmp_name'][$i];
            $fileData = file_get_contents($tmpName); // Get file data for MEDIUMBLOB

            // Validate file type and size
            $allowedTypes = [
                'image/jpeg', 
                'image/png', 
                'application/pdf', 
                'image/gif', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
            ];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'doc', 'docx', 'xls', 'xlsx'];
            $fileExtension = pathinfo($docName, PATHINFO_EXTENSION);
            $maxFileSize =  50 * 1024 * 1024; // 2MB limit
            
            if (!in_array($fileExtension, $allowedExtensions) || !in_array($docType, $allowedTypes) || $files['size'][$i] > $maxFileSize) {
                echo "<div class='alert alert-danger'>Invalid file type or size for '$docName'.</div>";
                continue;
            }

            // Insert document into the database with Status 'Uploaded'
            $sql = "INSERT INTO documents (userId, DocName, DocType, Document, DocumentPath, Status, FolderId) VALUES (?, ?, ?, ?, ?, 'Uploaded', ?)";
            $stmt = $con->prepare($sql);
            
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($con->error));
            }

            // Get the folder ID from the database
            $folderQuery = "SELECT FolderId FROM folders WHERE FolderName = ?";
            $folderStmt = $con->prepare($folderQuery);
            $folderStmt->bind_param("s", $folderName);
            $folderStmt->execute();
            $folderResult = $folderStmt->get_result();
            $folderId = $folderResult->fetch_assoc()['FolderId'];
            $folderStmt->close();

            // Set the DocumentPath
            $documentPath = $folderPath . basename($docName);

            // Bind parameters and execute the statement
            $stmt->bind_param("issssi", $userId, $docName, $docType, $fileData, $documentPath, $folderId);
            
            if ($stmt->execute()) {
                move_uploaded_file($tmpName, $documentPath);
                echo "<script>
    alert('Document \"$docName\" uploaded successfully!');
    window.location.href = 'admin_searchDoc.php';
</script>";
            } else {
                echo "<div class='alert alert-danger'>Error uploading document '$docName': " . htmlspecialchars($stmt->error) . "</div>";
            }
            $stmt->close();
        }
    }
}
// Retrieve existing folders
$folders = [];
$sql = "SELECT * FROM folders";
$stmt = $con->prepare($sql);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Document Requests Button -->
    <div class="d-flex justify-content-end mb-4">
        <a href="admin_docReq.php" class="btn btn-primary me-2">Document Requests</a>
        <a href="admin_searchDoc.php" class="btn btn-danger me-2">Uploaded Documents</a>
        <a href="admin_folders.php" class="btn btn-warning">Folders</a>
    </div>

    <h2 class="mb-4">Create New Folder</h2>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="folder_name" class="form-label">Folder Name:</label>
            <input type="text" id="folder_name" name="folder_name" class="form-control" required>
        </div>
        <button type="submit" name="create_folder" class="btn btn-success">Create Folder</button>
    </form>

    <h2 class="mb-4">Upload Documents</h2>
    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <label for="folder_name" class="form-label">Select Folder:</label>
            <select id="folder_name" name="folder_name" class="form-select" required>
                <option value="">-- Select Folder --</option>
                <?php foreach ($folders as $folder): ?>
                    <option value="<?= htmlspecialchars($folder['FolderName']) ?>"><?= htmlspecialchars($folder['FolderName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="documents" class="form-label">Upload Documents:</label>
            <input type="file" id="documents" name="documents[]" class="form-control" multiple required>
        </div>
        <button type="submit" name="upload" class="btn btn-success">Upload Documents</button>
    </form>
</body>
</html>