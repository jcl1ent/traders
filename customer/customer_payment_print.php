<?php
include("logincode.php");
$page_title = "View Receipt";
include("sidebar.php");
include("includes/header.php"); 
include("dbcon.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" integrity="sha512-qZvrmS2ekKPF2mSznTQsxqPgnpkI4DNTlrdUmTzrDgektczlKNRRhy5X5AAOnx5S09ydFYWWNSfcEqDTTHgtNA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header"> 
                            <h4 class="mb-0"> Print Receipt
                                <a href="paymentsOrder_customer.php" class="btn btn-secondary btn-sm float-end">
                                    Back
                                </a>
                            </h4>                   
                        <div class="card-body">  
                            <div id="myBillingArea">
                            <?php                           
                                // Assume your code above this point is correct                          
                                if (isset($_GET['pymntNo'])) {
                                    $pymntNo = $_GET['pymntNo'];
                                
                                    // Fetch payment and customer information
                                    $recQuery = "
                                        SELECT c.*, p.*, s.*, CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS staffName, 
                                                CONCAT_WS(' ', c.firstname, c.middlename, c.lastname) AS custName, c.address AS cAddress,
                                                c.email AS cEmail, c.contact_number AS contactNo
                                        FROM payment p
                                        JOIN customers c ON c.custId = p.custId
                                        JOIN staffs s ON s.staffId = p.staffId
                                        WHERE p.pymntNo = ?";
                                    $stmt = $con->prepare($recQuery);
                                    $stmt->bind_param("i", $pymntNo);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                
                                    if ($result && $result->num_rows > 0) {
                                        $recRow = $result->fetch_assoc();
                                        $fullName = $recRow['custName'];
                                        $totalAmount = $recRow['totalAmount'];
                                        $paymentType = $recRow['paymentType'];
                                        $paymentStatus = $recRow['paymentStatus'];
                                        $pymntNo = $recRow['pymntNo'];
                                        $staffId = $recRow['staffName'];
                                        $Address = $recRow['cAddress'];
                                        $Email = $recRow['cEmail'];
                                        $contactNo = $recRow['contactNo'];
                                        ?>
                                        <table  style="width:100%; margin-bottom:20px;">
                                            <tbody>
                                                <tr>
                                                    <td style="text-align:center">
                                                    <img src="images_productsAndservices\download (1).png" style="width:100px; margin:10px;">
                                                        <p style="font-size: 28px; line-height:28px; margin:0px; padding:0; "><strong>RONYX TRADING INTEGRATED<br>ENGINEERING  SERVICES</strong></p>
                                                        <p style="font-size: 16px; line-height:24px; margin:1px; padding:0;">Bag-ong Silingan Mactan, Lapu-Lapu City</p>
                                                        <p style="font-size: 16px; line-height:24px; margin:1px; padding:0;">(032) 260-2483 / 09177448475</p>
                                                        <h3 style="font-size: 70px; line-height:35px; margin:60px; padding:0;">RECEIPT</h3>
                                                    </td>
                                                    
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <h5 style="font-size: 20px; line-height:30px; margin:0px; padding:0;">Customer Details</h5>
                                                        <p style="font-size: 14px; line-height:20px; margin:0px; padding:0;">Customer Name: <?php echo $fullName; ?></p>
                                                        <p style="font-size: 14px; line-height:24px; margin:1px; padding:0;">Phone number: <?php echo $contactNo; ?></p>
                                                        <p style="font-size: 14px; line-height:24px; margin:1px; padding:0;">Email: <?php echo $Email; ?></p>
                                                        <p style="font-size: 14px; line-height:24px; margin:1px; padding:0;">Address: <?php echo $Address; ?></p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <?php
                                
                                        // Fetch order items associated with the payment
                                        $orderItemsQuery = "
                                            SELECT oi.prodName, oi.prodPrice, oi.quantity, oi.totalProductPrice, p.totalAmount, p.staffId, CONCAT(s.firstname, ' ', s.middlename, ' ', s.lastname) AS staffName 
                                            FROM orders o
                                            JOIN order_items oi ON o.orderNo = oi.orderNo
                                            JOIN payment p ON p.orderNo = o.orderNo
                                            JOIN staffs s ON s.staffId = p.staffId
                                            WHERE p.pymntNo = ?";
                                        $oIstmt = $con->prepare($orderItemsQuery);
                                        $oIstmt->bind_param("i", $pymntNo);
                                        $oIstmt->execute();
                                        $OIresult = $oIstmt->get_result();
                                
                                        // Check if there are order items and display them in a table
                                        if ($OIresult && $OIresult->num_rows > 0) {
                                            ?>
                                            <div class="table-responsive mb-3 d-flex justify-content-center text-center">
                                                <table style="width:90%;" cellpadding="5">
                                                    <thead>
                                                        <tr >
                                                            
                                                            <th align="start" style="border-bottom: 1px solid #000000;">Product Name</th>
                                                            <th align="start" style="border-bottom: 1px solid #000000;" width="15%;">Price</th>
                                                            <th align="start" style="border-bottom: 1px solid #000000;" width="15%;">Quantity</th>
                                                            <th align="start" style="border-bottom: 1px solid #000000;" width="20%;">Total Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        while ($row = $OIresult->fetch_assoc()) {
                                                            ?>
                                                            <tr >
                                                                <td style="border-bottom: 1px solid #000000;"><?= htmlspecialchars($row['prodName']); ?></td>
                                                                <td style="border-bottom: 1px solid #000000;"><?= htmlspecialchars($row['prodPrice']); ?></td>
                                                                <td style="border-bottom: 1px solid #000000;"><?= htmlspecialchars($row['quantity']); ?></td>
                                                                <td style="border-bottom: 1px solid #000000;"><?= htmlspecialchars($row['totalProductPrice']); ?></td>
                                                                
                                                            </tr>
                                                            <?php
                                                        }
                                                        ?>
                                                            <tr>
                                                                <td colspan="3" align="right"><strong>Total Amount:</strong></td>
                                                                <td style="font-size: 18px;"><strong><?= htmlspecialchars($totalAmount); ?></strong></td>                
                                                            </tr>  
                                                                                                            
                                                    </tbody>
                                                    
                                                </table>
                                                
                                            </div>
                                            <div class="pymntTypeStatus ps-3 ms-3" style="font-size: 14px;" >
                                                 <p>Payment Type: <?= htmlspecialchars($paymentType); ?></p>

                                                <p></p>
                                                <p>Assigned Staff: <?= htmlspecialchars($staffId); ?></p>
                                                <div class="sig">
                                                <img src="images_productsAndservices\download.png" style="width:220px; margin:10px;">
                                                </div>
                                            </div> 
                                            <div class="line d-flex justify-content-center">
                                               <table style="width:90%;">
                                               <td style="border-bottom: 1px solid #000000; padding: 10px;"></td>
                                               </table>
                                            </div>
                                            <div class="footer text-center" style="padding: 10px;">
                                                <h5>Thank you for shopping with us!</h5>
                                                <p style="font-size: 12px;">Empower your trades with precision engineering services, where expertise meets <br> opportunity for seamless success in every transaction.</p>
                                            </div>
                                            <?php

                                        } else {
                                            echo "No items in the order.";
                                        }
                                    } else {
                                        echo "No record found.";
                                    }
                                }
                                ?>
                            </div>
                            <div class="mt-4 text-end">
                                <button class="btn btn-primary px-4 mx-1" onclick="printMyBillingArea()">Print</button>
                                <button class="btn btn-danger px-4 mx-1" onclick="downloadPDF('<?= $pymntNo; ?>')">Download PDF</button>
                            </div>
                        </div>    
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<script>
    function printMyBillingArea() {
        var divContents = document.getElementById("myBillingArea").innerHTML;
        var a = window.open('', '');
        a.document.write('<html><title>POS SYSTEM</title>');
        a.document.write('<body style="font-family: fangsong;">');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        a.print();
    }

    const { jsPDF } = window.jspdf;
    var docPDF = new jsPDF();
    function downloadPDF(pymntNo) {
        var elementHTML = document.querySelector("#myBillingArea");
        docPDF.html(elementHTML, {
            callback: function () {
                docPDF.save('Payment'+ pymntNo + '.pdf');
            },
            x: 15,
            y: 15,
            width: 170,
            windowWidth: 650
        });
    }
</script>