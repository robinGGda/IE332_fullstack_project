<?php
// Database connection parameters
$servername = "mydb.itap.purdue.edu";
$username = "g1140956";
$password = "group6";
$database = $username;

// Establish database connection
try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Database connection parameters
$servername = "mydb.itap.purdue.edu";
$username = "g1140956";
$password = "group6";
$database = $username;

// Establish database connection
try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

session_start();

// Handle form submission for batch creation
if (isset($_POST['update_inventory'])) {
    $bottle_id = (int)$_POST['bottle_id'];
    $quantity = (int)$_POST['quantity'];
    $production_line = (int)$_POST['production_line'];

    // Create batch
    $batch_sql = "INSERT INTO Batches (production_line, bottle_id, start_time, status) 
                  VALUES ($production_line, $bottle_id, CURRENT_TIMESTAMP, 'In Progress')";
    
    if ($conn->query($batch_sql)) {
        $batch_id = $conn->insert_id;
        $_SESSION['quantities'][$batch_id] = $quantity; // Store quantity in session
        $success_message = "Batch created successfully!";
    } else {
        $error_message = "Error creating batch: " . $conn->error;
    }
}

// Handle Batch status update
if (isset($_POST['update_batch_status'])) {
    $batch_id = $_POST['batch_id'];
    $new_status = $_POST['new_status'];
    $quantity = isset($_SESSION['quantities'][$batch_id]) ? $_SESSION['quantities'][$batch_id] : 0;

    if ($new_status == 'Completed' && $quantity > 0) {
        // Get bottle_id for this batch
        $batch_query = "SELECT bottle_id FROM Batches WHERE ID = $batch_id";
        $batch_result = $conn->query($batch_query);
        $batch_data = $batch_result->fetch_assoc();
        $bottle_id = $batch_data['bottle_id'];

        // Update the inventory
        $check_sql = "SELECT quantity FROM Inventory WHERE bottle_id = $bottle_id";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $sql = "UPDATE Inventory 
                   SET quantity = quantity + $quantity,
                       date_updated = CURRENT_TIMESTAMP 
                   WHERE bottle_id = $bottle_id";
        } else {
            $sql = "INSERT INTO Inventory (bottle_id, quantity, date_updated) 
                   VALUES ($bottle_id, $quantity, CURRENT_TIMESTAMP)";
        }

        // Update batch status
        $status_sql = "UPDATE Batches 
                      SET status = '$new_status',
                          end_time = CURRENT_TIMESTAMP 
                      WHERE ID = $batch_id";

        if ($conn->query($sql) && $conn->query($status_sql)) {
            unset($_SESSION['quantities'][$batch_id]);
            $success_message = "Batch completed and inventory updated (+$quantity units)";
        } else {
            $error_message = "Error updating: " . $conn->error;
        }
    } else {
        // Just update status if not completing
        $status_sql = "UPDATE Batches 
                      SET status = '$new_status',
                          end_time = " . ($new_status == 'Completed' ? 'CURRENT_TIMESTAMP' : 'NULL') . "
                      WHERE ID = $batch_id";
        
        if ($conn->query($status_sql)) {
            $success_message = "Status updated to $new_status";
        } else {
            $error_message = "Error updating status: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="styles.css">
     <!-- External Dependencies -->
     <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="style.css">

    
</head>
<body>
    <header>
        <h1>Inventory Management</h1>
    </header>
    
    <nav>
        <ul>
            <li><a href="index.php" class="index">Home</a></li>
            <li><a href="dashboard.php" class="dashboard">Dashboard</a></li>
            <li><a href="machines_productivity.php" class="machines_productivity">Machines Productivity</a></li>
            <li><a href="QC_data.php" class="QC_data">QC Data</a></li>
            <li><a href="inventory.php" class="inventory">Inventory</a></li>
            <li><a href="sensors_and_faults.php" class="sensors_and_faults">Sensors & Faults</a></li>
            <li><a href="data_manipulation_deletion.php" class="data_manipulation_deletion">Data Deletion</a></li>
            <li><a href="data_manipulation_addition.php" class="data_manipulation_addition">Data Addition</a></li>
        </ul>
    </nav>

    <div class="main-container" data-aos="fade-up" style="margin-bottom: 2rem;">

        <!-- Messages Display -->
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Input Form -->
        <div class="form-section" >
            <h2>Update Inventory & Create Batch</h2>

            <form method="POST">
                <div class="input-group">
                    <label for="bottle_id">Select Bottle Type:</label>
                    <select name="bottle_id" id="bottle_id" required>
                        <option value="">Select a bottle...</option>
                        <?php
                            $sql = "SELECT 
                                        b.shape,
                                        b.capacity,
                                        b.material,
                                        b.ID,
                                        COALESCE(SUM(i.quantity), 0) as total_stock
                                    FROM Bottles b
                                    LEFT JOIN Inventory i ON b.ID = i.bottle_id
                                    GROUP BY b.shape, b.capacity, b.material, b.ID
                                    ORDER BY b.shape, b.capacity";

                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                $bottle_info = $row['shape'] . ' ' . $row['capacity'] . 'ml ' . 
                                            $row['material'] . ' (Current Stock: ' . $row['total_stock'] . ')';
                                echo "<option value='" . $row['ID'] . "'>" . 
                                    htmlspecialchars($bottle_info) . 
                                    "</option>";
                            }
                        ?>
                    </select>
                </div>

        <div class="input-group">
            <label for="production_line">Select Production Line:</label>
            <select name="production_line" id="production_line" required>
                <option value="">Select a production line...</option>
                <?php
                $line_sql = "SELECT 
                            pl.ID, 
                            pl.name,
                            COUNT(b.ID) as active_batches
                        FROM ProductionLines pl
                        LEFT JOIN Batches b ON pl.ID = b.production_line 
                            AND b.status = 'In Progress'
                        GROUP BY pl.ID, pl.name
                        ORDER BY pl.name";
                
                $line_result = $conn->query($line_sql);
                while ($row = $line_result->fetch_assoc()) {
                    echo "<option value='" . $row['ID'] . "'>" . 
                         htmlspecialchars($row['name']) . 
                         " (Active Batches: " . $row['active_batches'] . ")" .
                         "</option>";
                }
                ?>
            </select>
        </div>

        <div class="input-group">
            <label for="quantity">Production Quantity:</label>
            <div class="quantity-options">
                <input type="number" name="quantity" id="quantity" min="0" required placeholder= "Insert Quantity...">
                <button type="button" class="quantity-btn" onclick="document.getElementById('quantity').value='1000'">+1000</button>
                <button type="button" class="quantity-btn" onclick="document.getElementById('quantity').value='5000'">+5000</button>
                <button type="button" class="quantity-btn" onclick="document.getElementById('quantity').value='10000'">+10000</button>
            </div>
        </div>

        <button type="submit" name="update_inventory" class="submit-btn">Create Production Batch</button>
    </form>
</div>

        <!-- Active Batches Table -->
        <div class="table-container">
            <h2>Active Production Batches</h2>
            <table>
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Bottle Type</th>
                        <th>Production Line</th>
                        <th>Start Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            b.ID,
                            CONCAT(bt.shape, ' ', bt.capacity, 'ml ', bt.material) as bottle_type,
                            pl.name as line_name,
                            b.start_time,
                            b.status
                           FROM Batches b
                           JOIN Bottles bt ON b.bottle_id = bt.ID
                           JOIN ProductionLines pl ON b.production_line = pl.ID
                           WHERE b.status != 'Completed'
                           ORDER BY b.start_time DESC";
                    
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        if (strtolower($row['status']) !== 'cancelled') {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['bottle_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['line_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
                            echo "<td><span class='status-badge status-" . strtolower(str_replace(' ', '-', $row['status'])) . "'>" 
                                . htmlspecialchars($row['status']) . "</span></td>";
                            echo "<td>
                                <form method='POST' style='display: inline;'>
                                    <input type='hidden' name='batch_id' value='" . $row['ID'] . "'>
                                    <select name='new_status' onchange='this.form.submit()' style='width: auto;'>
                                        <option value=''>Update Status...</option>
                                        <option value='Completed'>Mark Completed</option>
                                        <option value='Paused'>Pause</option>
                                        <option value='In Progress'>Resume</option>
                                    </select>
                                    <input type='hidden' name='update_batch_status' value='1'>
                                </form>
                            </td>";
                            echo "</tr>";
                        }
                    }   
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Current Inventory Table -->
        <div class="table-container">
            <h2>Current Inventory Levels</h2>
            <table>
                <thead>
                    <tr>
                        <th>Bottle Type</th>
                        <th>Current Stock</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT 
                            CONCAT(b.shape, ' ', b.capacity, 'ml ', b.material) as bottle_type,
                            COALESCE(i.quantity, 0) as current_stock,
                            COALESCE(i.date_updated, 'Never') as last_updated
                           FROM Bottles b
                           LEFT JOIN Inventory i ON b.ID = i.bottle_id
                           ORDER BY b.shape, b.capacity";
                    
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['bottle_type']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['current_stock']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_updated']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Digital Engineering Solutions. All rights reserved.</p>
    </footer>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
        // Optional: Add JavaScript for dynamic updates
        document.getElementById('bottle_id').addEventListener('change', function() {
            // You could add AJAX here to fetch current inventory for the selected bottle
        });

        function toggleBatchFields(checked) {
            document.getElementById('batch_fields').style.display = checked ? 'block' : 'none';
            document.getElementById('create_batch_input').value = checked ? 'yes' : 'no';
        }
    </script>
</body>
</html>
