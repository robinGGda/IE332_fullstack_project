<?php
session_start();

// Database connection parameters
$servername = "mydb.itap.purdue.edu";
$username = "g1140956";
$password = "group6";
$database = $username;

// Check if this is a POST request and redirect immediately
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['post_data'] = $_POST;  // Save POST data in session
    header("Location: " . $_SERVER['PHP_SELF']);  // Redirect to same page
    exit();
}

// Process the saved POST data if it exists
if (isset($_SESSION['post_data'])) {
    $_POST = $_SESSION['post_data'];  // Restore POST data
    unset($_SESSION['post_data']);    // Clear saved POST data

    // Rest of your original code stays exactly the same
    try {
        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // Your original form processing code remains unchanged
    if (isset($_POST['update_sensor_data'])) {
        $serial_number = (int)$_POST['serial_number'];
        $sensor_number = (int)$_POST['sensor_ID'];
        $sensor_value = (int)$_POST['quantity'];

        $sql = "INSERT INTO SensorResults (machine, sensor_number, value, recorded_at) 
                VALUES ('$serial_number', '$sensor_number', '$sensor_value', CURRENT_TIMESTAMP)";
        if ($conn->query($sql)) {
            $success_message_1 = "Sensor data updated successfully!";
        } else {
            $error_message_1 = "Error updating sensor data: " . $conn->error;
        }
    }

    if (isset($_POST['update_fault_data'])) {
        $serial_number = (int)$_POST['serial_number'];
        $batch_ID = (int)$_POST['ID'];
        $fault_description = htmlspecialchars($_POST['fault_description']);
        $fault_severity = htmlspecialchars($_POST['fault_severity']);

        $sql = "INSERT INTO Faults (machine, batch, description, severity, occurred) 
                VALUES ('$serial_number', '$batch_ID', '$fault_description', '$fault_severity', CURRENT_TIMESTAMP)";

        if ($conn->query($sql)) {
            $success_message_2 = "Fault added successfully!";
        } else {
            $error_message_2 = "Error adding fault: " . $conn->error;
        }
    }

    if (isset($_POST['update_batch_status'])) {
        try {
            $batch_id = $_POST['batch_id'];
            $new_status = $_POST['new_status'];
            $end_time = ($new_status == 'Completed') ? 'CURRENT_TIMESTAMP' : 'NULL';
            
            $sql = "UPDATE Batches SET status = ?, end_time = $end_time WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_status, $batch_id);
            
            if ($stmt->execute()) {
                $success_message = "Batch status updated successfully!";
            } else {
                throw new Exception("Error updating batch status");
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
} else {
    // Normal database connection for non-POST requests
    try {
        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Metadata and character encoding for proper rendering -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Page title displayed in browser tab -->
    <title>Sensor & Fault Management</title>
    <!-- External CSS stylesheet link -->
    <link rel="stylesheet" href="styles.css">
     <!-- External Dependencies -->
     <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Page header with title -->
    <header>
        <h1>Sensor & Fault Management</h1>
    </header>
    
    <!-- Navigation menu with links to different pages -->
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

    <!-- Main content container with fade-up animation -->
    <div class="main-container" data-aos="fade-up" style="margin-bottom: 2rem;">

        <!-- Messages Display -->
        <?php if (isset($success_message_1)): ?>
            <div class="message success"><?php echo $success_message_1; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message_1)): ?>
            <div class="message error"><?php echo $error_message_1; ?></div>
        <?php endif; ?>

        <!-- Input Form -->
        <div class="form-section" >
            <h2>Update Sensor Results</h2>
            
            <!-- Machine Serial Number Dropdown -->
            <form method="POST">
                <div class="input-group">
                    <label for="serial_number">Select Machine Serial Number:</label>
                    <select name="serial_number" id="serial_number" required>
                        <option value="">Machine Serial Number...</option>
                        <?php
                            // SQL query to fetch all machine serial numbers
                            $sql = "SELECT serial_number
                                    FROM Machines";

                            $result = $conn->query($sql);
                                // Dynamically populate dropdown with machine serial numbers
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['serial_number']) . "'>" . 
                                    "Machine Serial ID: " . htmlspecialchars($row['serial_number']) . 
                                    "</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
        
                <!-- Sensor Number Dropdown -->
                <div class="input-group">
                    <label for="sensor_ID">Select Sensor Number:</label>
                    <select name="sensor_ID" id="sensor_ID" required>
                        <option value="">Sensor Number...</option>
                        <?php
                        // SQL query to fetch distinct sensor IDs and types
                        $line_sql = "SELECT DISTINCT id_number, type FROM Sensors;";
                        $sensor_ID = $conn->query($line_sql);
                    
                        // Dynamically populate dropdown with sensor IDs and types
                        if ($sensor_ID->num_rows > 0) {
                            while ($row = $sensor_ID->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['id_number']) . "' title='Sensor Type: " . htmlspecialchars($row['type']) . "'>" . 
                                    "Sensor ID: " . htmlspecialchars($row['id_number']) . " - " . htmlspecialchars($row['type']) . 
                                    "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
        
                <!-- Sensor Value Input -->        
                <div class="input-group">
                    <label for="quantity">Sensor Value:</label>
                    <div class="quantity-options">
                        <input type="number" name="quantity" id="quantity" min="0" required placeholder= "Enter Sensor Value...">
                    </div>
                </div>
                <!-- Submit Button for Sensor Data -->        
                <button type="submit" name="update_sensor_data" class="submit-btn">Update Sensor Result</button>
            </form>
        </div>
    

        <!-- Messages Display -->
        <?php if (isset($success_message_2)): ?>
            <div class="message success"><?php echo $success_message_2; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message_2)): ?>
            <div class="message error"><?php echo $error_message_2; ?></div>
        <?php endif; ?>

        <!-- Input Form -->
        <div class="form-section" >
            <h2>Update Faults</h2>

            <form method="POST">
                <!-- Machine Serial Number Selection -->
                <div class="input-group">
                    <label for="serial_number">Select Machine Serial Number:</label>
                    <select name="serial_number" id="serial_number" required>
                        <option value="">Machine Serial Number...</option>
                        <?php
                            // Query and populate machine serial numbers from database
                            $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['serial_number']) . "'>" . 
                                    "Machine Serial ID: " . htmlspecialchars($row['serial_number']) . 
                                    "</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
                
                <!-- Batch Number Selection -->
                <div class="input-group">
                    <label for="ID">Select Batch Number:</label>
                    <select name="ID" id="ID" required>
                        <option value="">Batch...</option>
                        <?php
                            // Query to get distinct batch IDs from Batches table
                            $line_sql = "SELECT DISTINCT ID FROM Batches ORDER BY ID ASC;";
                            $batch_ID = $conn->query($line_sql);
        
                            if ($batch_ID->num_rows > 0) {
                                // Loop through all rows in the result
                                while ($row = $batch_ID->fetch_assoc()) {
                                    // Output each batch ID as an option
                                    echo "<option value='" . htmlspecialchars($row['ID']) . "'>" . 
                                     "Batch: " . htmlspecialchars($row['ID']) . 
                                     "</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
        
                <!-- Fault Description Input -->
                <div class="input-group">
                    <label for="fault_description">Fault Description:</label>
                    <div class="quantity-options">
                        <input type="text" name="fault_description" id="fault_description" min="0" required placeholder= "Insert Fault Description...">
                    </div>
                </div>
                
                <!-- Fault Severity Selection -->
                <div class="input-group">
                    <label for="fault_severity">Severity:</label>
                        <select id="fault_severity" name="fault_severity" required>
                            <option value=  >Fault Severity...</option>
                            <option value="Minor" <?php echo isset($_POST['fault_severity']) && $_POST['fault_severity'] == 'Minor' ? 'selected' : ''; ?>>Minor</option>
                            <option value="Major" <?php echo isset($_POST['fault_severity']) && $_POST['fault_severity'] == 'Major' ? 'selected' : ''; ?>>Major</option>
                            <option value="Critical" <?php echo isset($_POST['fault_severity']) && $_POST['fault_severity'] == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" name="update_fault_data" class="submit-btn">Update Fault</button>
            </form>
        </div>

    <div id="sensors-page">    
        <!-- Current Inventory Table -->
        <div class="table-wrapper">
            <!-- Sensor Data Table -->
            <div class="table-container">
                <h2>Current Sensor Data</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Machine Serial Number</th>
                            <th>Sensor ID</th>
                            <th>Sensor Type</th>
                            <th>Sensor Value</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query to join Machines, Sensors, and SensorResults tables
                        $sql = "SELECT 
                                m.serial_number AS machine_serial_number,
                                s.id_number AS sensor_id,
                                s.type AS sensor_type,
                                sr.value AS sensor_value,
                                sr.recorded_at AS last_updated
                            FROM SensorResults sr
                            JOIN Sensors s ON sr.machine = s.machine AND sr.sensor_number = s.id_number
                            JOIN Machines m ON sr.machine = m.serial_number
                            ORDER BY sr.recorded_at DESC";
            
                        $result = $conn->query($sql);
            
                        // Display sensor data in table rows
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['machine_serial_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['sensor_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['sensor_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['sensor_value']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['last_updated']) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Fault Data Table -->
            <div class="table-container">
                <h2>Current Fault Data</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Machine Serial Number</th>
                            <th>Batch ID</th>
                            <th>Fault Description</th>
                            <th>Fault Severity</th>
                            <th>Occurred</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            // Query to join Machines and Faults tables
                            $sql = "SELECT 
                                        m.serial_number AS machine_serial_number,
                                        f.batch AS batch_id,
                                        f.description AS fault_description,
                                        f.severity AS fault_severity,
                                        f.occurred AS fault_occurred
                                    FROM Faults f
                                    JOIN Machines m ON f.machine = m.serial_number
                                    ORDER BY f.occurred DESC"; 
            
                            $result = $conn->query($sql);
            
                            // Display fault data in table rows
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['machine_serial_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['batch_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['fault_description']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['fault_severity']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['fault_occurred']) . "</td>";
                                echo "</tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Fotter Section -->
    <footer>
        <p>&copy; 2024 Digital Engineering Solutions. All rights reserved.</p>
    </footer>

    <!-- JavaScript Section -->
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

<?php
// Close database connection
$conn->close();
?>
</html>