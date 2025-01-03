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

// Function to generate random date between two dates
function randomDate($start_date, $end_date) {
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $rand_time = mt_rand($min, $max);
    return date('Y-m-d H:i:s', $rand_time);
}

// Function to generate random decimal
function randomDecimal($min, $max, $decimals = 2) {
    $scale = pow(10, $decimals);
    return round(mt_rand($min * $scale, $max * $scale) / $scale, $decimals);
}

// Handle data generation request
if (isset($_GET['generate'])) {
    try {
        $conn->autocommit(FALSE);
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        // Get existing static data
        $bottle_ids = [];
        $result = $conn->query("SELECT ID FROM Bottles");
        while ($row = $result->fetch_assoc()) {
            $bottle_ids[] = $row['ID'];
        }

        $production_lines = [];
        $result = $conn->query("SELECT ID FROM ProductionLines");
        while ($row = $result->fetch_assoc()) {
            $production_lines[] = $row['ID'];
        }

        $machines = [];
        $result = $conn->query("SELECT serial_number, type FROM Machines");
        while ($row = $result->fetch_assoc()) {
            $machines[] = $row;
        }

// Generate 65 Batches
        $batch_statuses = ['Completed', 'In Progress', 'Paused', 'Cancelled'];
        $batches_sql = "INSERT INTO Batches (production_line, bottle_id, start_time, end_time, status) VALUES ";
        $batch_values = [];

        for ($i = 0; $i < 65; $i++) {
            $status = $batch_statuses[array_rand($batch_statuses)];
            $start_time = randomDate('2023-01-01', '2024-12-20');
            $end_time = $status == 'Completed' ? 
                       randomDate($start_time, '2024-12-20') : 
                       ($status == 'In Progress' ? 'NULL' : randomDate($start_time, '2024-12-20'));
            
            $batch_values[] = sprintf(
                "(%d, %d, '%s', %s, '%s')",
                $production_lines[array_rand($production_lines)],
                $bottle_ids[array_rand($bottle_ids)],
                $start_time,
                $end_time === 'NULL' ? 'NULL' : "'$end_time'",
                $status
            );
        }
        $batches_sql .= implode(",", $batch_values);
        $conn->query($batches_sql);

        // Get generated batch IDs
        $batch_ids = [];
        $result = $conn->query("SELECT ID FROM Batches ORDER BY ID DESC LIMIT 65");
        while ($row = $result->fetch_assoc()) {
            $batch_ids[] = $row['ID'];
        }

// Generate Maintenance Records
        $maintenance_types = ['Preventive', 'Corrective', 'Emergency', 'Scheduled', 'Calibration'];
        $maintenance_descriptions = [
            'Preventive' => [
                'Regular scheduled maintenance check',
                'Quarterly system inspection',
                'Routine component check',
                'Preventive wear inspection'
            ],
            'Corrective' => [
                'Component replacement',
                'System adjustment',
                'Repair worn parts',
                'Fix mechanical issue'
            ],
            'Emergency' => [
                'Urgent system failure repair',
                'Critical component replacement',
                'Emergency breakdown fix',
                'Unexpected malfunction repair'
            ],
            'Scheduled' => [
                'Planned maintenance service',
                'Scheduled system upgrade',
                'Regular calibration check',
                'Planned component replacement'
            ],
            'Calibration' => [
                'Sensor calibration',
                'System alignment check',
                'Precision adjustment',
                'Measurement system calibration'
            ]
        ]; 

        // Get all machines and maintenance people
        $machines_query = $conn->query("SELECT serial_number, type FROM Machines");
        $machines = [];
        while ($row = $machines_query->fetch_assoc()) {
            $machines[] = $row['serial_number'];
        }

        $maintenance_people_query = $conn->query("SELECT ID FROM MaintenancePeople");
        $maintenance_people = [];
        while ($row = $maintenance_people_query->fetch_assoc()) {
            $maintenance_people[] = $row['ID'];
        }

        // Generate maintenance records for each machine
        foreach ($machines as $machine) {
            // Generate 4 maintenance records per year (quarterly)
            $current_date = '2023-01-01';
            while (strtotime($current_date) <= strtotime('2024-12-20')) {
                $type = $maintenance_types[array_rand($maintenance_types)];
                $performed_by = $maintenance_people[array_rand($maintenance_people)];
                
                // Random date within the quarter
                $maintenance_date = date('Y-m-d H:i:s', strtotime($current_date . ' + ' . rand(0, 90) . ' days'));
                
                // Get random description for the maintenance type
                $description = $maintenance_descriptions[$type][array_rand($maintenance_descriptions[$type])];
                
                $sql = "INSERT INTO Maintenance (serial_number, date, type, description, performed_by) VALUES " .
                    "($machine, '$maintenance_date', '$type', '$description', $performed_by)";
                $conn->query($sql);
                
                // Move to next quarter
                $current_date = date('Y-m-d', strtotime($current_date . ' + 3 months'));
            }
        }

//Generate processes
        $process_machine_mapping = [
            'A1' => [
                'Filling' => 10001,  // Filler A1
                'Capping' => 10002,  // Capper A1
                'Labeling' => 10003  // Labeler A1
            ],
            'B1' => [
                'Filling' => 10004,  // Filler B1
                'Capping' => 10005,  // Capper B1
                'Labeling' => 10006  // Labeler B1
            ],
            'C1' => [
                'Filling' => 10007,  // Filler C1
                'Capping' => 10008,  // Capper C1
                'Labeling' => 10009  // Labeler C1
            ],
            'D1' => [
                'Filling' => 10010,  // Filler D1
                'Capping' => 10011,  // Capper D1
                'Labeling' => 10012  // Labeler D1
            ],
            'E1' => [
                'Filling' => 10013,  // Filler E1
                'Capping' => 10014,  // Capper E1
                'Labeling' => 10015  // Labeler E1
            ]
        ];
        
        $process_names = ['Filling', 'Capping', 'Labeling'];
        $process_statuses = ['Completed', 'In Progress', 'Paused', 'Failed'];
        $processes_sql = "INSERT INTO Processes (batch_id, machine_id, name, start_time, end_time, status) VALUES ";
        $process_values = [];
        
        try {
            // Get batch IDs
            $batch_result = $conn->query("SELECT ID FROM Batches ORDER BY ID DESC LIMIT 65");
            if (!$batch_result) {
                throw new Exception("Error getting batch IDs: " . $conn->error);
            }
            
            $batch_ids = [];
            while ($row = $batch_result->fetch_assoc()) {
                $batch_ids[] = $row['ID'];
            }
            
            if (empty($batch_ids)) {
                throw new Exception("No batch IDs found");
            }
        
            foreach ($batch_ids as $batch_id) {
                // Select a random production line (A1, B1, C1, D1, or E1)
                $line_group = array_rand($process_machine_mapping);
                
                foreach ($process_names as $process_name) {
                    $status = $process_statuses[array_rand($process_statuses)];
                    
                    // Get the correct machine ID for this process in the selected line
                    $machine_id = $process_machine_mapping[$line_group][$process_name];
                    
                    $start_time = randomDate('2023-01-01', '2024-12-20');
                    
                    if ($status == 'In Progress') {
                        $end_time = 'NULL';
                    } else {
                        $hours_to_add = rand(6, 24);
                        $end_time = date('Y-m-d H:i:s', strtotime($start_time . " + {$hours_to_add} hours"));
                    }
        
                    $process_values[] = sprintf(
                        "(%d, %d, '%s', '%s', %s, '%s')",
                        $batch_id,
                        $machine_id,
                        $process_name,
                        $start_time,
                        $end_time === 'NULL' ? 'NULL' : "'$end_time'",
                        $status
                    );
                }
            }
        
            if (!empty($process_values)) {
                $processes_sql .= implode(",", $process_values);
                if (!$conn->query($processes_sql)) {
                    throw new Exception("Error inserting processes: " . $conn->error);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Error generating processes: " . $e->getMessage());
        }

        // Get generated process IDs
        $process_ids = [];
        $result = $conn->query("SELECT ID FROM Processes ORDER BY ID DESC LIMIT 195"); // 65 * 3
        while ($row = $result->fetch_assoc()) {
            $process_ids[] = $row['ID'];
        }

// Generate 65 Quality Control entries
        $qc_results = ['Pass', 'Fail'];
        $qc_sql = "INSERT INTO QualityControl (batch_id, process, check_date, check_result, remarks) VALUES ";
        $qc_values = [];

        for ($i = 0; $i < 65; $i++) {
            $result = $qc_results[array_rand($qc_results)];
            $remarks = $result == 'Pass' ? 
                      'All parameters within specification' : 
                      'Parameters out of specification - adjustment needed';

            $qc_values[] = sprintf(
                "(%d, %d, '%s', '%s', '%s')",
                $batch_ids[array_rand($batch_ids)],
                $process_ids[array_rand($process_ids)],
                randomDate('2023-01-01', '2024-12-20'),
                $result,
                $remarks
            );
        }
        $qc_sql .= implode(",", $qc_values);
        $conn->query($qc_sql);

// Generate 65 Sensor Results
        $valid_machines = range(10001, 10015); // Valid machine IDs
        $valid_sensors = range(1, 6); // Valid sensor numbers

        $sensor_sql = "INSERT INTO SensorResults (machine, sensor_number, value, recorded_at) VALUES ";
        $sensor_values = [];

        try {
            for ($i = 0; $i < 65; $i++) {
                $sensor_values[] = sprintf(
                    "(%d, %d, %.3f, '%s')",
                    $valid_machines[array_rand($valid_machines)], // Random machine ID between 10001 and 10015
                    $valid_sensors[array_rand($valid_sensors)], // Random sensor number between 1 and 6
                    randomDecimal(50, 100, 3), // Random sensor value between 50 and 100
                    randomDate('2023-01-01', '2024-12-20')
                );
            }

            if (!empty($sensor_values)) {
                $sensor_sql .= implode(",", $sensor_values);
                if (!$conn->query($sensor_sql)) {
                    throw new Exception("Error inserting sensor results: " . $conn->error);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Error generating sensor results: " . $e->getMessage());
        }



// Generate 65 Faults
        $severities = ['Minor', 'Major', 'Critical'];
        $fault_descriptions = [
            'Temperature fluctuation detected',
            'Pressure drop observed',
            'Flow rate variance',
            'Alignment drift',
            'Calibration error',
            'Sensor malfunction',
            'Motor overload',
            'Belt tension issue'
        ];

        // Generate array of valid machine IDs
        $valid_machines = range(10001, 10015);

        $faults_sql = "INSERT INTO Faults (machine, batch, description, severity, occurred) VALUES ";
        $fault_values = [];

        try {
            // Get existing batch IDs
            $batch_result = $conn->query("SELECT ID FROM Batches ORDER BY ID DESC LIMIT 65");
            if (!$batch_result) {
                throw new Exception("Error getting batch IDs: " . $conn->error);
            }
            
            $batch_ids = [];
            while ($row = $batch_result->fetch_assoc()) {
                $batch_ids[] = $row['ID'];
            }
            
            if (empty($batch_ids)) {
                throw new Exception("No batch IDs found");
            }

            for ($i = 0; $i < 65; $i++) {
                $fault_values[] = sprintf(
                    "(%d, %d, '%s', '%s', '%s')",
                    $valid_machines[array_rand($valid_machines)], // Random machine ID between 10001 and 10015
                    $batch_ids[array_rand($batch_ids)],
                    $conn->real_escape_string($fault_descriptions[array_rand($fault_descriptions)]),
                    $severities[array_rand($severities)],
                    randomDate('2023-01-01', '2024-12-20')
                );
            }

            if (!empty($fault_values)) {
                $faults_sql .= implode(",", $fault_values);
                if (!$conn->query($faults_sql)) {
                    throw new Exception("Error inserting faults: " . $conn->error);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Error generating faults: " . $e->getMessage());
        }

// Generate 65 Inventory records
try {
    // First, ensure we update all 10 bottles at least once
    for ($bottle_id = 1; $bottle_id <= 10; $bottle_id++) {
        // Generate a random inventory date
        $inventory_date = randomDate('2023-01-01', '2024-12-20');
        
        // Generate a random quantity
        $quantity = mt_rand(2000, 8000);

        // Since we know we need to handle all bottles, we can use REPLACE INTO
        // This will either insert a new record or update existing one
        $sql = sprintf(
            "REPLACE INTO Inventory (bottle_id, quantity, date_updated) VALUES (%d, %d, '%s')",
            $bottle_id,
            $quantity,
            $inventory_date
        );

        if (!$conn->query($sql)) {
            throw new Exception("Error managing inventory for bottle $bottle_id: " . $conn->error);
        }
    }

    // Now generate the remaining updates (55 more random updates to existing bottles)
    for ($i = 0; $i < 55; $i++) {
        $bottle_id = mt_rand(1, 10);
        $inventory_date = randomDate('2023-01-01', '2024-12-20');
        $quantity = mt_rand(2000, 8000);

        $sql = sprintf(
            "UPDATE Inventory SET quantity = %d, date_updated = '%s' WHERE bottle_id = %d",
            $quantity,
            $inventory_date,
            $bottle_id
        );

        if (!$conn->query($sql)) {
            throw new Exception("Error updating inventory for bottle $bottle_id: " . $conn->error);
        }
    }

    echo "Successfully generated inventory records for all bottles plus additional updates.";
} catch (Exception $e) {
    throw new Exception("Error generating inventory: " . $e->getMessage());
}

//setting foreign keys check back to normal
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $conn->commit();
        $conn->autocommit(TRUE);
        
        $_SESSION['success_message'] = "Successfully generated test data for all tables.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(TRUE);
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        $_SESSION['error_message'] = "Error generating data: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get current data counts
$tables = ['Batches', 'Maintenance', 'Processes', 'QualityControl', 'SensorResults', 'Faults', 'Inventory'];
$data_counts = [];

foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $data_counts[$table] = $result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Generator</title>
    
    <!-- External Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Test Data Generator</h1>
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

    <div id="addition">    
        <div class="main-container">
            <?php if (isset($success_message)): ?>
                <div class="message success" data-aos="fade-up">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="message error" data-aos="fade-up">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="data-grid" data-aos="fade-up">
                <?php foreach ($data_counts as $table => $count): ?>
                    <div class="data-card">
                        <h3><?php echo htmlspecialchars($table); ?></h3>
                        <div class="count"><?php echo number_format($count); ?></div>
                        <p>Current Records</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="generate-section" data-aos="fade-up">
                <h2>Generate Test Data</h2>
                <p style="margin: 1rem 0;">
                    This will generate 65 realistic entries for each dynamic table while maintaining data consistency and relationships.
                </p>
                <p style="margin-bottom: 2rem;">
                    Generated data will include:
                    <br>• Production batches with realistic timestamps
                    <br>• Maintenance records that are meaningful
                    <br>• Process records with proper machine assignments
                    <br>• Quality control checks with meaningful results
                    <br>• Sensor readings within reasonable ranges
                    <br>• Equipment faults with appropriate severity levels
                    <br>• Inventory records with realistic quantities
                </p>
                
                <form method="GET">
                    <input type="hidden" name="generate" value="1">
                    <button type="submit" class="generate-btn">
                        Generate Test Data
                    </button>
                </form>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Digital Engineering Solutions. All rights reserved.</p>
    </footer>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Confirmation dialog for data generation
        function confirmGenerate() {
            return confirm('Are you sure you want to generate test data? This will add 65 new entries to each dynamic table.');
        }

        // Optional: Add loading indicator
        document.querySelector('form').addEventListener('submit', function() {
            if (confirm('Are you sure you want to generate test data?')) {
                document.querySelector('.generate-btn').disabled = true;
                document.querySelector('.generate-btn').textContent = 'Generating Data...';
            } else {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>