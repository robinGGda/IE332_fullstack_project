<?php
/**
 * Production Dashboard - Main Configuration and Database Connection
 * 
 * This file implements a production dashboard with real-time metrics,
 * charts, and quality control data visualization.
 */

// Database connection parameters
$servername = "mydb.itap.purdue.edu";
$username = "g1140956";    // CAREER / group username
$password = "group6";      // group password
$database = $username;     // ITaP database name (same as career login)

// Establish database connection
try {
    $conn = new mysqli($servername, $username, $password, $database);
    
    // Enable detailed error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle date range selection with validation
if(isset($_POST['start_date']) && isset($_POST['end_date'])) {
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];
  $error_message = '';

  // Validate date formats
  if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) || 
      !preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
      $error_message = "Invalid date format. Please use YYYY-MM-DD format.";
  }
  // Validate date logic
  else if (strtotime($start_date) > strtotime($end_date)) {
      $error_message = "Start date cannot be after end date.";
  }
  // Validate against future dates
  else if (strtotime($end_date) > strtotime('today')) {
      $error_message = "End date cannot be in the future.";
  }
  // Validate against dates before data existence (2023-01-01)
  else if (strtotime($start_date) < strtotime('2023-01-01')) {
      $error_message = "Start date cannot be before 2023-01-01 (earliest data available).";
  }

  // If validation fails, set default dates
  if ($error_message) {
      $start_date = date('Y-m-d', strtotime('-30 days'));
      $end_date = date('Y-m-d');
  }
} else {
  // Set default date range (last 30 days)
  $start_date = date('Y-m-d', strtotime('-30 days'));
  $end_date = date('Y-m-d');
  $error_message = '';
}

// Update POST variables for form persistence
$_POST['start_date'] = $start_date;
$_POST['end_date'] = $end_date;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Dashboard</title>
    
    <!-- External Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <h1>Production Dashboard</h1>
    </header>
    
    <!-- Navigation Menu -->
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
    
    <!-- Main Content Container -->
    <div class="main-container">
        <!--seperate php code for if error styling and message displaying -->
        <?php if ($error_message): ?>
            <div id="error-message" class="error-message" style="background-color: #fee2e2; border: 1px solid #ef4444; color: #991b1b; 
                padding: 1rem; margin: 1rem 0; border-radius: 8px; text-align: center; transition: opacity 1s;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <script>
                // JavaScript to hide the error message after 7 seconds
                setTimeout(function() {
                    const errorMessage = document.getElementById('error-message');
                    if (errorMessage) {
                        // Start the fade-out by changing opacity
                        errorMessage.style.opacity = '0';

                        // After the transition is done, set display to none
                        setTimeout(function() {
                            errorMessage.style.display = 'none';
                        }, 1000); // Allow 1 second for fade-out to complete
                    }
                }, 3000); // 3000 milliseconds = 3 seconds
            </script>
        <?php endif; ?>
        <div class="dashboard-container">
            <!-- Date Range Selector -->
            <div class="date-selector" data-aos="fade-up" style="margin-bottom: 2rem;">
                <form id="dateRangeForm" method="POST" style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                        <div>
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" 
                                  value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days')); ?>"
                                  style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" 
                                  value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d'); ?>"
                                  style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 4px;">
                        </div>
                        <button type="submit" name="update_range">
                            Update Dashboard
                        </button>
                    </div>
                </form>
            </div>

            <!-- Metrics Grid Section -->
            <div class="metrics-grid">
                <!-- Production Status Card -->
                <div class="metric-card" data-aos="fade-up" data-aos-delay="100">
                    <h3>Production Status</h3>
                    <div id="currentProduction">
                        <?php
                        // Set date condition based on form submission
                        $date_condition = isset($_POST['update_range']) ? 
                            "b.start_time BETWEEN '{$_POST['start_date']}' AND '{$_POST['end_date']}'" : 
                            "b.status = 'In Progress' AND (f.occurred >= NOW() - INTERVAL 24 HOUR OR f.occurred IS NULL)";

                        // Query for production metrics
                        $sql = "SELECT 
                                COUNT(DISTINCT b.ID) as active_batches,
                                COUNT(DISTINCT b.production_line) as active_lines,
                                SUM(CASE WHEN f.severity = 'Critical' THEN 1 ELSE 0 END) as critical_faults
                              FROM Batches b
                              LEFT JOIN Faults f ON b.ID = f.batch
                              WHERE $date_condition";
                        $result = $conn->query($sql);
                        $data = $result->fetch_assoc();
                        
                        // Display date range if specified
                        if(isset($_POST['update_range'])) {
                            echo "Period: " . date('M d, Y', strtotime($_POST['start_date'])) . 
                                " to " . date('M d, Y', strtotime($_POST['end_date'])) . "<br>";
                        }
                        
                        // Display production metrics
                        echo "Total Batches: " . $data['active_batches'] . "<br>";
                        echo "Active Lines: " . $data['active_lines'] . "<br>";
                        echo "Critical Faults: " . $data['critical_faults'];
                        ?>
                    </div>
                </div>

                <!-- Quality Metrics Card -->
                <div class="metric-card" data-aos="fade-up" data-aos-delay="100">
                    <h3>Quality Metrics</h3>
                    <div id="qualityMetrics">
                        <?php
                        // Set date condition for quality metrics
                        $date_condition = isset($_POST['update_range']) ? 
                            "check_date BETWEEN '{$_POST['start_date']}' AND '{$_POST['end_date']}'" : 
                            "check_date >= NOW() - INTERVAL 7 DAY";

                        // Query for quality metrics
                        $sql = "SELECT 
                                COUNT(*) as total_checks,
                                SUM(CASE WHEN check_result = 'Pass' THEN 1 ELSE 0 END) as passed_checks,
                                ROUND(SUM(CASE WHEN check_result = 'Pass' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as pass_rate
                              FROM QualityControl
                              WHERE $date_condition";
                        $result = $conn->query($sql);
                        $data = $result->fetch_assoc();
                        
                        // Display quality metrics
                        echo "Quality Rate: " . $data['pass_rate'] . "%<br>";
                        echo "Total Checks: " . $data['total_checks'] . "<br>";
                        echo "Passed: " . $data['passed_checks'];
                        ?>
                    </div>
                </div>

                <!-- Machine Health Card -->
                <div class="metric-card" data-aos="fade-up" data-aos-delay="100">
                    <h3>Machine Health</h3>
                    <div id="machineHealth">
                        <?php
                        // Set date condition for machine health metrics
                        $date_condition = isset($_POST['update_range']) ? 
                            "f.occurred BETWEEN '{$_POST['start_date']}' AND '{$_POST['end_date']}'" : 
                            "f.occurred >= NOW() - INTERVAL 24 HOUR";

                        // Query for machine health metrics
                        $sql = "SELECT 
        (SELECT COUNT(*) FROM Machines) as total_machines,
        COUNT(DISTINCT f.machine) as machines_with_faults,
        (SELECT ROUND(AVG(value), 1)
         FROM SensorResults sr
         JOIN Sensors s ON sr.machine = s.machine 
            AND sr.sensor_number = s.id_number
         WHERE s.type = 'Flow Rate (m^3/s)'
         AND sr.sensor_number = 1
         AND sr.recorded_at BETWEEN '$start_date' AND '$end_date'
        ) as avg_flow_rate
    FROM Machines m
    LEFT JOIN Faults f ON m.serial_number = f.machine 
    AND " . $date_condition;
                        
                        $result = $conn->query($sql);
                        $data = $result->fetch_assoc();
                        
                        // Display machine health metrics
                        echo "Total Machines: " . $data['total_machines'] . "<br>";
                        echo "Machines with Faults: " . $data['machines_with_faults'] . "<br>";
                        echo "Avg Flow Rate (1h): " . round($data['avg_flow_rate'], 2);
                        ?>
                    </div>
                </div>
            </div>

            <!-- Production Chart Section -->
            <div class="chart-container">
                <h3>Production Quantity by Bottle Type Between Selected Dates</h3>
                <canvas id="productionChart"></canvas>
                <?php
                // Set date condition for production data
                $date_condition = isset($_POST['update_range']) ? 
                    "b.start_time BETWEEN '{$_POST['start_date']}' AND '{$_POST['end_date']}'" : // Changed from i.date_updated to b.start_time
                    "b.start_time >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";

                // Query for bottle production data
                $sql = "SELECT 
                        bt.ID,
                        bt.shape,
                        bt.capacity,
                        bt.material,
                        SUM(CASE 
                            WHEN b.status IN ('Completed', 'In Progress') 
                            THEN 1000 -- Assuming each batch produces 1000 units, adjust as needed
                            ELSE 0 
                        END) as total_quantity
                    FROM Bottles bt
                    LEFT JOIN Batches b ON bt.ID = b.bottle_id 
                        AND $date_condition
                    GROUP BY 
                        bt.ID,
                        bt.shape,
                        bt.capacity,
                        bt.material
                    ORDER BY 
                        total_quantity,
                        bt.capacity";
                
                $result = $conn->query($sql);
                $bottleTypes = [];
                $quantities = [];
                
                // Process query results for chart data
                while($row = $result->fetch_assoc()) {
                    $bottleType = $row['shape'] . ' ' . number_format($row['capacity'], 0) . 'ml ' . $row['material'];
                    $bottleTypes[] = $bottleType;
                    $quantities[] = (int)$row['total_quantity']; // Added explicit cast to integer
                }

                // Debug output
                if (count($bottleTypes) === 0) {
                    echo "<!-- No results found for date range: {$_POST['start_date']} to {$_POST['end_date']} -->";
                }
                ?>
            </div>

            <!-- Quality Issues Table -->
            <div class="table-container">
                <h3>Quality Issues</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Batch</th>
                            <th>Process</th>
                            <th>Result</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Set date condition for quality issues
                        $date_condition = isset($_POST['update_range']) ? 
                            "qc.check_date BETWEEN '{$_POST['start_date']}' AND '{$_POST['end_date']}'" : 
                            "qc.check_date >= NOW() - INTERVAL 7 DAY";

                        // Query for quality issues
                        $sql = "SELECT 
                                qc.check_date,
                                qc.batch_id,
                                p.name as process_name,
                                qc.check_result,
                                qc.remarks
                              FROM QualityControl qc
                              JOIN Processes p ON qc.process = p.ID
                              WHERE qc.check_result = 'Fail'
                              AND $date_condition
                              ORDER BY qc.check_date DESC
                              LIMIT 10";
                        $result = $conn->query($sql);
                        
                        // Display quality issues
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['check_date'] . "</td>";
                            echo "<td>" . $row['batch_id'] . "</td>";
                            echo "<td>" . $row['process_name'] . "</td>";
                            echo "<td>" . $row['check_result'] . "</td>";
                            echo "<td>" . $row['remarks'] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer>
        <p>&copy; 2024 Digital Engineering Solutions. All rights reserved.</p>
    </footer>

    <!-- JavaScript Section -->
    <script>
        // Initialize AOS (Animate On Scroll)
        AOS.init({
            duration: 1000,
            once: true
        });

        // Initialize Production Chart
        const ctx = document.getElementById('productionChart').getContext('2d');
        const bottleTypes = <?php echo json_encode($bottleTypes); ?>;
        const quantities = <?php echo json_encode($quantities); ?>;
        
        // Create Chart.js instance
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: bottleTypes,
                datasets: [{
                    label: 'Current Inventory Level',
                    data: quantities,
                    backgroundColor: [
                        '#2563eb',
                        '#7c3aed',
                        '#db2777',
                        '#059669',
                        '#d97706'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Current Inventory Levels by Bottle Type'
                    }
                },
                layout: {
                    padding: {
                        bottom: 50
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity in Stock'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Bottle Type'
                        },
                        ticks: {
                            maxRotation: 35,
                            minRotation: 35,
                        }
                    }
                }
            }
        });

        // Fetch and display IP address
        /*$(document).ready(function() {
            $.getJSON('https://api.ipify.org?format=json', function(data) {
                $('#ip').text(data.ip);
            }).fail(function() {
                $('#ip').text('Unable to fetch IP');
            });
        });*/
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
