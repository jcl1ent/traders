<?php
$page_title = "Admin Add Product";
include("logincode.php");
include("sidebar_admin.php");
include("dbcon.php");
include("includes/header.php");
if (isset($_POST['addProduct_btn'])) {
    $prodName = $_POST['prodName'];
    $quantity = $_POST['quantity'];
    $prodPrice = $_POST['prodPrice'];
    $productStatus = $_POST['productStatus'];
    

    if (isset($_FILES['prodImg']) && $_FILES['prodImg']['error'] === 0) {
        $prodImg = $_FILES['prodImg']['tmp_name'];
        $prodImgData = file_get_contents($prodImg); // Get the binary image data
        
        $sql = "INSERT INTO products (prodName, quantity, prodPrice, productStatus, prodImg) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sidss", $prodName, $quantity, $prodPrice, $productStatus, $prodImgData); // Change 'b' to 's' as bind_param needs 's' for string

        if ($stmt->execute()) {
            echo "<script>alert('Product uploaded successfully!');</script>";
            echo "<script>window.location.href='admin_products.php';</script>";
        } else {
            echo "<script>alert('Error: Could not upload the data.');</script>";
            echo "<script>window.location.href='admin_products.php';</script>";
        }
    } else {
        echo "Error: No image selected or there was an error uploading.";
    }

    if (isset($stmt)) {
        $stmt->close(); // Close the statement if it was created
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h5>Add Product</h5>
                    </div>
                    <div class="card-body">
                        <form name ="addProduct" action="" method="POST" enctype="multipart/form-data">
                            <div class="form-group mb-3">
                                <label for="">Product Name</label>
                                <input type="text" name="prodName" class="form-control" required>
                            </div>   
                            <div class="form-group mb-3">
                                <label for="">Product Price</label>
                                <input type="text" name="prodPrice" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">    
                                <label for="">Product Status</label>                                                                           
                                    <select class="form-select" name="productStatus" required> <!-- Correct name attribute -->
                                        <option value="">Select Status...</option>
                                        <option value="Available">Available</option>
                                        <option value="Out of Stock">Out of Stock</option>                                                
                                    </select>
                            </div>        
                            <div class="form-group mb-3">
                                <label for="">quantity</label>
                                <input type="text" name="quantity" class="form-control" required>
                            </div>                                           
                            
                            <div class="form-group mb-3">
                                <label for="">Product Image</label>
                                <input type="file" name="prodImg" class="form-control" required>                               
                            </div>
                            
                            <div class="form-group d-flex justify-content-center" style="gap: 5px;">
                                <a href="admin_products.php">
                                    <button type="button" class="btn btn-secondary">
                                        <i class="bi bi-arrow-90deg-left"></i> Back
                                    </button>
                                </a>
                                <button type="submit" name="addProduct_btn" class="btn btn-primary">Add Product</button>                                
                            </div>
                        </form>    
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>