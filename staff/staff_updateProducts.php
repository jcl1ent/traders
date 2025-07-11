<?php
$page_title = "Edit Product";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");


// Check if form was submitted
if (isset($_POST['updateProduct'])) {
    $prodNo = $_POST['prodNo'];
    $prodName = $_POST['prodName'];
    $productStatus = $_POST['productStatus'];
    $prodPrice = $_POST['prodPrice'];
    $quantity = $_POST['quantity'];

    //fetch product
    $prodQuery = "SELECT prodName, productStatus, prodPrice, quantity FROM products WHERE prodNo = ?";
    $stmt = $con->prepare($prodQuery);
    $stmt->bind_param("i", $prodNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        $prodNo = !empty($_POST['prodNo']) ? $_POST['prodNo'] : $row['prodNo'];
        $prodName = !empty($_POST['prodName']) ? $_POST['prodName'] : $row['prodName'];
        $productStatus = !empty($_POST['productStatus']) ? $_POST['productStatus'] : $row['productStatus'];
        $prodPrice = !empty($_POST['prodPrice']) ? $_POST['prodPrice'] : $row['prodPrice'];
        $quantity = !empty($_POST['quantity']) ? $_POST['quantity'] : $row['quantity'];
    } else {
        echo "<script>alert('Invalid product ID.');</script>";
        $stmt->close();
        return;
    }
    // Handle the image upload
    if (isset($_FILES['prodImg']) && $_FILES['prodImg']['error'] === 0) {
        $prodImg = $_FILES['prodImg']['tmp_name'];
        $prodImgData = file_get_contents($prodImg);
    } else {
        // If no new image is uploaded, retain the old image
        $query = "SELECT prodImg FROM products WHERE prodNo = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $prodNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $prodImgData = $row['prodImg']; // Retain the existing image data
    }
    
    // Update the product information in the database
    $query = "UPDATE products SET prodName=?, prodImg=?, prodPrice=?, productStatus=?, quantity=? WHERE prodNo=?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ssdsii", $prodName, $prodImgData, $prodPrice, $productStatus, $quantity, $prodNo);
    
    if ($stmt->execute()) {
        echo "<script>alert('Product information updated successfully');</script>";
        echo "<script>window.location.href='staff_products.php';</script>";
    } else {
        echo "<script>alert('Error updating product information');</script>";
    }
    
    $stmt->close();

    $query_fetch = "SELECT quantity, productStatus FROM products WHERE prodNo = ?";
    $stmt_fetch = $con->prepare($query_fetch);
    $stmt_fetch->bind_param("i", $prodNo);
    $stmt_fetch->execute();
    $sql_update_product_status = "UPDATE products SET productStatus = 'Available' WHERE prodNo = ?";
    $stmt_update = $con->prepare($sql_update_product_status);
    $stmt_update->bind_param("i", $prodNo);
    $stmt_update->execute();
    $stmt_update->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
</head>
<body>
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="text-center">EDIT MANPOWER INFORMATION</h3>
                    </div>
                    <div class="card-body mb-3">
                        <?php 
                        if (isset($_GET['prodNo'])) {
                            $prodNo = $_GET['prodNo'];
                            
                            // Fetch the manpower data based on the provided mpId
                            $query = "SELECT * FROM products WHERE prodNo = ?";
                            $stmt = $con->prepare($query);
                            $stmt->bind_param("i", $prodNo);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                $imageData = base64_encode($row['prodImg']);
                                
                        ?>    
                            
                            <form action="staff_updateProducts.php" method="POST" enctype="multipart/form-data">   
                                <input type="hidden" name="prodNo" value="<?php echo $row['prodNo']; ?>">
                                <div class="mb-3 row">
                                    <label for="prodName" class="col col-form-label">Product ID:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="prodNo" value="<?php echo $row['prodNo']; ?>" disabled readonly>                                                   
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="prodName" class="col col-form-label">Product Name:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="prodName" value="<?php echo $row['prodName']; ?>">                                                   
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="prodPrice" class="col col-form-label">Product Price:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="prodPrice" value="<?php echo $row['prodPrice']; ?>">                                                   
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                        <label for="productStatus" class="col col-form-label" >Status:</label>
                                        <div class="col">
                                            <select class="form-select" name="productStatus" aria-required> <!-- Correct name attribute -->
                                                <option value=""><?php echo $row['productStatus']; ?></option>
                                                <option value="Available">Available</option>
                                                <option value="Unavailable">Unavailable</option>                                               
                                            </select>
                                        </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="quantity" class="col col-form-label">Quantity:</label>
                                    <div class="col">
                                        <input class="form-control" type="text" name="quantity" value="<?php echo $row['quantity']; ?>">                                                   
                                    </div>
                                </div> 

                                <div class="mb-3 row">
                                    <label for="prodImg" class="col col-form-label">Product Image:</label>
                                    <div class="col">
                                        <input class="form-control" type="file" name="prodImg">
                                        <img src="data:image/jpeg;base64,<?= $imageData ?>" class="d-block mx-auto img-fluid" style="height: 150px; object-fit: cover;">                                                   
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center m-3" style="gap: 10px;">
                                    <a href="staff_products.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-90deg-left"></i> Back 
                                    </a>
                                    <button type="submit" name="updateProduct" class="btn btn-primary">
                                        <i class="bi bi-arrow-clockwise"></i> Update
                                    </button>
                                </div>
                            </form>
                        <?php
                            } else {
                                echo "<p>Product information not found.</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p>No Product ID provided.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>  
</html>
