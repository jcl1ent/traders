<?php
$page_title = "Staff Inquiry Tickets";
include("logincode.php");
include("sidebar_staff.php");
include("dbcon.php");
include("includes/header.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">                    
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="dataTable" class="table table-hover table-bordered">
                                        <thead>
                                            <tr class="text-center">                                        
                                                <th scope="col">Ticket Number</th>
                                                <th scope="col">Customer Name</th>
                                                <th scope="col">Title</th>
                                                <th scope="col">Description</th>
                                                
                                                <th scope="col">Issue Date</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Action</th>                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                // Get the information from the orders
                                                $vticket_query = "SELECT * FROM ticket ORDER BY tickNo DESC";
                                                $stmt_vticket = $con->prepare($vticket_query);                                                
                                                $stmt_vticket->execute();
                                                $result_ticket = $stmt_vticket->get_result();

                                                if ($result_ticket->num_rows > 0) {
                                                    while ($row = $result_ticket->fetch_assoc()) {                                                                                                                      
                                                                ?>
                                                                <tr class="text-center">
                                                                    
                                                                    <td data-label="Ticket Number"><?php echo $row['tickNo']; ?></td> 
                                                                    <td data-label="Customer Name"><?php echo $row['custName']; ?></td>                                                                    
                                                                    <td data-label="TItle"><?php echo $row['title']; ?></td>
                                                                    <td data-label="Description"><?php echo $row['description']; ?></td>
                                                                    
                                                                    <td data-label="Issue Date"><?php echo $row['issueDate']; ?></td>    
                                                                    <td data-label="Status"><?php echo $row['status']; ?></td> 
                                                                    <td data-label="Delete">
                                                                        <div class="buttons d-flex justify-content-center" style="gap: 5px;">
                                                                            <a href="staff_updateTicket.php?tickNo=<?php echo $row['tickNo']; ?>">
                                                                                <button type="button" class="btn  btn-success">
                                                                                <i class="bi bi-arrow-repeat"></i> Update
                                                                                </button>
                                                                            </a>
                                                                          
                                                                        </div>
                                                                    
                                                                    </td>                                                               
                                                                </tr>
                                                                <?php 
                                                                                                                    
                                                    }
                                                }                                       
                                            ?>
                                        </tbody>
                                    </table>
                                </div>    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>   
</body>
</html>