<?php
$page_title = "Services";
include("logincode.php");
include("sidebar.php");
include("dbcon.php");
include("includes/header.php");

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Get userId from users table using email
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
}

function findServiceByName($servName) {
    global $con; // Ensure you have access to the database connection
    
    $sql = "SELECT servCode, servName, rateService, servCategory FROM services WHERE servName = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $servName);  // Bind the service name to the query
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc(); // Return the first matching service
    } else {
        return null; // Return null if no service is found
    }
}


// Form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $servCodes = $_POST['servCode']; 
    $servCategory = $_POST['servCategory']; 
    $servType = isset($_POST['servType']) ? $_POST['servType'] : '';
    $selectedServices = explode(',', $servType);
    $urgent = isset($_POST['urgent']) && $_POST['urgent'] === 'Yes' ? 'Yes' : 'No';
    $description = $_POST['description']; 
    $payOpt = $_POST['payOpt'];
    $paymentType = $_POST['paymentType'];
    $payable = $_POST['payable'];
    $totalAmount = $_POST['totalAmount'];
    $adminId = $_POST['adminId'];
    $newAddress = $_POST['address']; 
    $staffId = $_POST['staffId']; 

    // Update the address in customers table
    $sql = "UPDATE customers SET address = ? WHERE userId = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("si", $newAddress, $userId);
    $stmt->execute();
    $stmt->close();

    $log_action_query = "INSERT INTO user_action_logs (adminId, action, status) VALUES (?, ?, ?)";
    $action = $fullName . ' submitted a service request.';
    $status = 'unread';
    $log_action_stmt = $con->prepare($log_action_query);
    $log_action_stmt->bind_param("iss", $adminId, $action, $status);
    $log_action_stmt->execute();
    $log_action_stmt->close();

    $sql = "SELECT rateService FROM services WHERE servCode = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $servCode);
    $stmt->execute();
    $stmt->bind_result($rateService);
    $stmt->fetch();
    $stmt->close();
    
    $sql = "INSERT INTO reqserv (userId, servType, urgent, description, payOpt, paymentType, payable, totalAmount, adminId, servStatus, servArchive, staffId) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $servStatus = 'Pending Request';
    $servArchive = '0';
    $stmt = $con->prepare($sql);
    $stmt->bind_param("isssssddisii", $userId, $servType, $urgent, $description, $payOpt, $paymentType, $payable, $totalAmount, $adminId, $servStatus, $servArchive, $staffId);

    if ($stmt->execute()) {
    $reqservId = $con->insert_id;  

    if ($reqservId) {
    foreach ($selectedServices as $servName) {
        $service = findServiceByName($servName);  
        
        if ($service) {
            $checkReqservSql = "SELECT 1 FROM reqserv WHERE reqserv = ?";
            $checkStmt = $con->prepare($checkReqservSql);
            $checkStmt->bind_param("i", $reqservId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $sqlInsert = "INSERT INTO reqserv_service (reqserv, servCode, servName, rateService, servCategory) 
                            VALUES (?, ?, ?, ?, ?)";
                $stmtInsert = $con->prepare($sqlInsert);
                $stmtInsert->bind_param("iisds", $reqservId, $service['servCode'], $service['servName'], $service['rateService'], $servCategory);
                
                if ($stmtInsert->execute()) {
                } else {
                    echo "<div class='alert alert-danger'>Error inserting service: " . $stmtInsert->error . "</div>";
                }
                
                $stmtInsert->close(); 
            } else {
                echo "<div class='alert alert-danger'>Error: Invalid reqservId, foreign key violation.</div>";
            }

            $checkStmt->close(); 
        }
    }
    echo "<script>alert('Service request submitted successfully!');</script>";
    } else {
    echo "<div class='alert alert-danger'>Error: reqservId is NULL.</div>";
    }
    } else {
    echo "<div class='alert alert-danger'>Error in Submitting: " . $stmt->error . "</div>";
    }

    $stmt->close();  
        }
    // Fetch services by category
    $servicesByCategory = [];
    $sql = "SELECT servCode, servName, rateService, servCategory FROM services WHERE servArchive = 'Available'";
    $result = $con->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $servicesByCategory[$row['servCategory']][] = $row;
        }
    } else {
        $servicesByCategory = [];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <form action="" method="POST" class="row g-3">
                    <input type="hidden" name="adminId" value="<?php echo $adminId; ?>">
                    <input type="hidden" name="servCode" id="servCode" value="">
                    <input type="hidden" name="servType" id="servType" value="">

                    <div class="col-12">
                        <label for="ServiceType" class="form-label">Service Category</label>
                        <select class="form-select" name="servCategory" id="servCategory" onchange="updateServices()">
                            <option value="">Select Service Category</option>
                            <option value="Welding and Fusion Welding">Welding and Fusion Welding</option>
                            <option value="Turbocharger Components">Turbocharger Components</option>
                            <option value="General Engine Parts">General Engine Parts</option>
                            <option value="Casting and Surface Alloying">Casting and Surface Alloying</option>
                            <option value="Mechanical Parts">Mechanical Parts</option>
                            <option value="Dynamic Balancing and In-Place Services">Dynamic Balancing and In-Place Services</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="services" class="form-label">Services</label>
                        <div id="services" name="services">
                        </div>
                    </div>

                    <div class="col-12">
                        <h5>Selected Services</h5>
                        <table class="table" id="selectedServicesTable">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Rate</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <label for="urgent" class="form-label">Urgent</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="urgent" name="urgent" value="Yes">
                            <label class="form-check-label" for="urgent">Mark as Urgent</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="Description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="col-3">
                        <label for="PaymentMethod" class="form-label">Payment Method</label>
                        <select class="form-select" name="payOpt" required>
                            <option value="" selected disabled>Payment Method</option>
                            <option value="Check">Check</option>
                            <option value="Cash on Delivery">Cash on Delivery</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="PaymentType" class="form-label">Payment Type</label>
                        <select class="form-select" name="paymentType" id="paymentType" onchange="calculateAmounts()" required>
                            <option value="" selected disabled>Payment Type</option>
                            <option value="Full">Full</option>
                            <option value="Partial">Partial</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="Payable" class="form-label">Payable</label>
                        <input type="number" class="form-control" name="payable" id="payable" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="totalAmount" class="form-label">Total Amount</label>
                        <input type="number" class="form-control" name="totalAmount" id="totalAmount" readonly>
                    </div>

                    <div class="col-md-9">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($address); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="staffId" class="form-label">Branch</label>
                        <select class="form-select" name="staffId" required>
                            <option value="" selected disabled>Select Branch</option>
                            <?php                          
                            // Fetch staffId from the staffs table
                            $sql = "SELECT staffId, branch FROM staffs";
                            $stmt = $con->prepare($sql);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                // Loop through the results and create options
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['staffId']) . '">' . htmlspecialchars($row['branch']) . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>No Staff Available</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
let selectedServices = []; // Keep track of selected services globally

// Function to update services based on selected category
function updateServices() {
    const category = document.getElementById('servCategory').value;
    const servicesContainer = document.getElementById('services');
    const selectedServicesTableBody = document.querySelector('#selectedServicesTable tbody');
    const services = <?php echo json_encode($servicesByCategory); ?>;

    // Clear the servicesContainer (checkboxes) but keep selected services intact
    servicesContainer.innerHTML = '';

    if (category) {
        const availableServices = services[category] || [];

        availableServices.forEach(service => {
            // Create checkbox for each service
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = service.servCode;
            checkbox.id = 'service_' + service.servCode;

            // Check if the service is already selected
            checkbox.checked = selectedServices.includes(service.servName);
            checkbox.onchange = (e) => toggleServiceSelection(e, service);

            const label = document.createElement('label');
            label.setAttribute('for', 'service_' + service.servCode);
            label.innerHTML = service.servName + ' (Rate: ' + service.rateService + ')';

            // Create div to wrap checkbox and label
            const serviceDiv = document.createElement('div');
            serviceDiv.appendChild(checkbox);
            serviceDiv.appendChild(label);

            servicesContainer.appendChild(serviceDiv);
        });
    }

    // Rebuild the selected services table based on the currently selected services
    rebuildSelectedServicesTable();
}

// Function to toggle service selection
function toggleServiceSelection(event, service) {
    if (event.target.checked) {
        // Add service name to selected services array
        selectedServices.push(service.servName);
    } else {
        // Remove service name from selected services array
        const index = selectedServices.indexOf(service.servName);
        if (index !== -1) {
            selectedServices.splice(index, 1);
        }
    }

    // Rebuild the selected services table and update the hidden input
    rebuildSelectedServicesTable();
}

// Function to rebuild the selected services table
function rebuildSelectedServicesTable() {
    const selectedServicesTableBody = document.querySelector('#selectedServicesTable tbody');
    selectedServicesTableBody.innerHTML = ''; // Clear the table body

    const services = <?php echo json_encode($servicesByCategory); ?>;
    let totalAmount = 0; // Variable to store total amount

    selectedServices.forEach(servName => {
        const service = findServiceByName(servName, services);

        if (service) {
            // Create table row for selected service
            const row = document.createElement('tr');
            row.id = 'row_' + service.servCode;

            const serviceNameCell = document.createElement('td');
            serviceNameCell.textContent = service.servName;

            const rateCell = document.createElement('td');
            rateCell.textContent = service.rateService;
            totalAmount += parseFloat(service.rateService); // Add to total amount

            const actionCell = document.createElement('td');
            const removeBtn = document.createElement('button');
            removeBtn.textContent = 'Remove';
            removeBtn.classList.add('btn', 'btn-danger');
            removeBtn.onclick = () => removeServiceFromSelection(service.servName);

            actionCell.appendChild(removeBtn);

            row.appendChild(serviceNameCell);
            row.appendChild(rateCell);
            row.appendChild(actionCell);

            selectedServicesTableBody.appendChild(row);
        }
    });

    // Update the hidden servType input with selected service names
    const servTypeInput = document.getElementById('servType');
    servTypeInput.value = selectedServices.join(',');  // Insert service names into the hidden input

    // Calculate and display total amount and payable
    displayTotalAmountAndPayable(totalAmount);
}

// Function to remove service from selection
function removeServiceFromSelection(servName) {
    const index = selectedServices.indexOf(servName);
    if (index !== -1) {
        selectedServices.splice(index, 1);
    }

    // Rebuild the selected services table and update the hidden input
    rebuildSelectedServicesTable();

    // Uncheck the corresponding checkbox
    const checkbox = document.getElementById('service_' + servName);
    if (checkbox) {
        checkbox.checked = false;
    }
}

// Function to find a service by its name from all categories
function findServiceByName(servName, services) {
    // Search for a service in all categories by its servName
    for (let category in services) {
        const service = services[category].find(s => s.servName === servName);
        if (service) return service;
    }
    return null;
}

// Function to calculate total amount and payable
function displayTotalAmountAndPayable(totalAmount) {
    const totalAmountInput = document.getElementById('totalAmount');
    const payableInput = document.getElementById('payable');
    const paymentType = document.querySelector('select[name="paymentType"]').value; // Get payment type value

    // Display total amount in the input field
    totalAmountInput.value = totalAmount.toFixed(2);

    // Calculate payable amount based on payment type
    let payableAmount = totalAmount;
    if (paymentType === 'Partial') {
        payableAmount = totalAmount / 2;
    }

    // Display payable amount in the input field
    payableInput.value = payableAmount.toFixed(2);
}

// Function to recalculate payable amount when payment type changes
function calculateAmounts() {
    // Recalculate amounts whenever payment type is changed
    const totalAmount = parseFloat(document.getElementById('totalAmount').value) || 0;
    const paymentType = document.querySelector('select[name="paymentType"]').value; // Get payment type value
    let payableAmount = totalAmount;

    if (paymentType === 'Partial') {
        payableAmount = totalAmount / 2;
    }

    // Update the payable input field
    document.getElementById('payable').value = payableAmount.toFixed(2);
}
</script>
</body>
</html>