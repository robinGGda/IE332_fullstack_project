<?php 
/**
 * Machine Productivity - Main Configuration and Database Connection
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
    <title>Machine Productivity</title>
    
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
        <h1>Machine Productivity and Information</h1>
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
        <div class="Machine_Productivity-container">

            <!-- Date Range Selector -->
            <div class="category-selector" data-aos="fade-up" style="margin-bottom: 2rem;">
                <form id="categoryFilterForm" method="POST" 
                    style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                        <div>
                            <label for="maintenance_category">Last Maintenance Date:</label>
                            <select id="maintenance_category" name="maintenance_category" 
                                style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <option value="0-6 months" <?php echo isset($_POST['maintenance_category']) && $_POST['maintenance_category'] == '0-6 months' ? 'selected' : ''; ?>>0-6 months ago</option>
                                <option value="6-12 months" <?php echo isset($_POST['maintenance_category']) && $_POST['maintenance_category'] == '6-12 months' ? 'selected' : ''; ?>>6-12 months ago</option>
                                <option value="12+ months" <?php echo isset($_POST['maintenance_category']) && $_POST['maintenance_category'] == '12+ months' ? 'selected' : ''; ?>>12 or more months ago</option>
                            </select>
                        </div>

                        <button type="submit" name="button">
                        Perform Search  
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Metrics Grid Section -->
        <div class="metrics-grid">

            <!-- Pie Chart Card -->
            <div class="table-container" data-aos="fade-up" data-aos-delay="100">
                <h3>Last Maintained by Machine Type</h3>
                    <div id="currentProduction">
                        <?php
                        $date_condition = isset($_POST['maintenance_category']) ? 
                        ( $_POST['maintenance_category'] == '0-6 months' ? 
                                "ma.date > NOW() - INTERVAL 6 MONTH" :
                        ($_POST['maintenance_category'] == '6-12 months' ? 
                                "ma.date BETWEEN NOW() - INTERVAL 12 MONTH AND NOW() - INTERVAL 6 MONTH" :
                        ($_POST['maintenance_category'] == '12+ months' ? 
                                "ma.date <= NOW() - INTERVAL 12 MONTH" : 
                                "" ) ) ) : 

                        "ma.date > NOW() - INTERVAL 6 MONTH";

                        //SQL Query
                        $sql = "SELECT 
                                COUNT(DISTINCT CASE WHEN m.type = 'filler' THEN m.serial_number END) AS filler_machines,
                                COUNT(DISTINCT CASE WHEN m.type = 'capper' THEN m.serial_number END) AS capper_machines,
                                COUNT(DISTINCT CASE WHEN m.type = 'labeler' THEN m.serial_number END) AS labeler_machines
                                FROM 
                                    Machines m
                                JOIN 
                                    Maintenance ma ON m.serial_number = ma.serial_number
                                WHERE 
                                    $date_condition";   
                        $result = $conn->query($sql);
                        $data = $result->fetch_assoc();

                        //Machine Count Data 
                        $filler_machines = $data['filler_machines'];
                        $capper_machines = $data['capper_machines'];
                        $labeler_machines = $data['labeler_machines'];
                        ?>

                        <canvas id="machineTypeChart" width="100" height="100"></canvas>
                        
                    </div>
            </div>

            <!-- Machine IDs Card -->
            <div class="table-container" data-aos="fade-up" data-aos-delay="100">
                <h3>Machine Function, ID, and Last Maintenance Date</h3>
                <table>
                        <thead>
                            <tr>
                                <th>Process</th>
                                <th>Machine ID</th>
                                <th>Last Maintenance</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        //SQL Query
                        $sql = "SELECT 
                                    m.type, m.serial_number, MAX(ma.date) AS most_recent_maintenance
                                FROM 
                                    Machines m
                                JOIN 
                                    Maintenance ma ON m.serial_number = ma.serial_number
                                WHERE 
                                    $date_condition
                                GROUP BY 
                                    m.type, m.serial_number
                                ORDER BY 
                                    m.type, m.serial_number";

                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {      
                        //Filling the Table
                        while ($data = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($data['type']) . "</td>";
                            echo "<td>" . htmlspecialchars($data['serial_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($data['most_recent_maintenance']) . "</td>";
                            echo "</tr>";
                        }

                        //If no data 
                        } else {
                            echo "<p style='text-align: center; font-size: 1.2em; color: red;'>No machines found for the selected date range.</p>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

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
    
    <!-- Date and Process Selector -->
    <div class="date-selector" data-aos="fade-up" style="margin-bottom: 2rem;">
        <form id="dateRangeForm" method="POST" style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div>
                    <label  for="start_date">Start Date:</label>
                    <input  type="date" id="start_date" name="start_date" 
                            value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days')); ?>"
                            style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 4px;">
                </div>

                <div>
                    <label  for="end_date">End Date:</label>
                    <input  type="date" id="end_date" name="end_date" 
                            value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d'); ?>"
                            style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 4px;">
                </div>

                <div>
                    <label  for="process_type">Process Type:</label>
                    <select id="process_type" name="process_type" 
                            style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <option value="Filling" <?php echo isset($_POST['process_type']) && $_POST['process_type'] == 'Filling' ? 'selected' : ''; ?>>Filling</option>
                                <option value="Capping" <?php echo isset($_POST['process_type']) && $_POST['process_type'] == 'Capping' ? 'selected' : ''; ?>>Capping</option>
                                <option value="Labeling" <?php echo isset($_POST['process_type']) && $_POST['process_type'] == 'Labeling' ? 'selected' : ''; ?>>Labeling</option>
                    </select>
                </div>

                <button type="submit" name="update_range_2">
                    Show Data
                </button>
            </div>
        </form>
    </div>

      
    <!-- Average Process Time Chart Section -->
    <div class="chart-container">
        <h3>Average Process Time per Machine</h3>
        <canvas id="processTimeChart"></canvas>

            <?php
            // Initialize variables for filters
                $processTypeCondition = isset($_POST['process_type']) && !empty($_POST['process_type']) 
                ? "AND Processes.name = '{$_POST['process_type']}'" 
                : "";

                $dateCondition = isset($_POST['update_range_2']) 
                ? "Processes.start_time BETWEEN '{$_POST['start_date']}' AND '{$_POST['end_date']}'" 
                : "Processes.start_time >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";

                // Query to calculate average process time per machine
                $sql = "SELECT 
                            Machines.serial_number, 
                            AVG(TIMESTAMPDIFF(HOUR, Processes.start_time, Processes.end_time)) AS avg_process_time
                        FROM Processes
                        JOIN Machines ON Processes.machine_id = Machines.serial_number
                        WHERE Processes.end_time IS NOT NULL
                            AND $dateCondition
                                $processTypeCondition
                        GROUP BY Machines.serial_number";

                $result = $conn->query($sql);
                $machineSerials = [];
                $avgProcessTimes = [];

                // Process query results for chart data
                while ($row = $result->fetch_assoc()) {
                    $machineSerials[] = $row['serial_number'];
                    $avgProcessTimes[] = round($row['avg_process_time'], 2); // Round to 2 decimal places
                }
            ?>
    </div>
    </div>
    </div>
    
    <!-- Footer Section -->
    <footer>
        <p>&copy; 2024 Digital Engineering Solutions. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- JavaScript Section -->
    <script>
        // Initialize AOS (Animate On Scroll)
        AOS.init({
            duration: 1000,
            once: true
        });

        //Initialize Pie Chart
        document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('machineTypeChart').getContext('2d');
        var chartData = {
            labels: ['Filler Machines', 'Capper Machines', 'Labeler Machines'],
            datasets: [{
                data: [<?php echo $filler_machines; ?>, <?php echo $capper_machines; ?>, <?php echo $labeler_machines; ?>],
                backgroundColor: ['#6050DC ', '#D52DB7 ', '#FF2E7E '], 
                borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                borderWidth: 1
            }]
        };

        // Configuration for the pie chart
        var config = {
            type: 'pie',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        };

        // Create Pie Chart
        var machineTypeChart = new Chart(ctx, config);
        });


        
        // Initialize Production Chart
        const ctx = document.getElementById('processTimeChart').getContext('2d');
        const machineSerials = <?php echo json_encode($machineSerials); ?>;
        const avgProcessTimes = <?php echo json_encode($avgProcessTimes); ?>;
        
        // Create Chart.js instance
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: machineSerials,
                datasets: [{
                    label: 'Average Process Duration',
                    data: avgProcessTimes,
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
                        text: 'Average Process Duration per Machine'
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
                            text: 'Time (hours)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Machine Serial ID'
                        },
                        ticks: {
                            maxRotation: 35,
                            minRotation: 35,
                        }
                    }
                }
            }
        });    
    </script>

</body>



<?php
// Close database connection
$conn->close();
?>
</html>