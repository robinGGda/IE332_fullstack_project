<?php
// Database connection (using your existing configuration)
$servername = "mydb.itap.purdue.edu";
$username = "g1140956";
$password = "group6";
$database = $username;

try {
    $conn = new mysqli($servername, $username, $password, $database);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions and set default values
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
$batch_id = isset($_POST['batch_id']) ? $_POST['batch_id'] : '';
$check_result = isset($_POST['check_result']) ? $_POST['check_result'] : '';
$process_type = isset($_POST['process_type']) ? $_POST['process_type'] : '';
$bottle_type = isset($_POST['bottle_type']) ? $_POST['bottle_type'] : '';
$production_line = isset($_POST['production_line']) ? $_POST['production_line'] : '';

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
  $conditions = ["qc.check_date BETWEEN '$start_date' AND '$end_date'"];

// Build the query conditions
if ($process_type) $conditions[] = "p.name = '$process_type'";
if ($bottle_type) $conditions[] = "b.ID = '$bottle_type'";
if ($production_line) $conditions[] = "pl.ID = '$production_line'";
if ($check_result) $conditions[] = "qc.check_result = '$check_result'";

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Main query for QC data
$sql = "SELECT 
            qc.batch_id,
            qc.check_date,
            qc.check_result,
            qc.remarks,
            p.name as process_name,
            b.shape as bottle_shape,
            b.capacity as bottle_capacity,
            b.material as bottle_material,
            pl.name as production_line_name,
            m.name as machine_name
        FROM QualityControl qc
        JOIN Processes p ON qc.process = p.ID
        JOIN Batches bat ON qc.batch_id = bat.ID
        JOIN Bottles b ON bat.bottle_id = b.ID
        JOIN ProductionLines pl ON bat.production_line = pl.ID
        JOIN Machines m ON p.machine_id = m.serial_number
        $where_clause
        ORDER BY qc.check_date DESC";

$result = $conn->query($sql);

// Get distinct values for dropdowns
$sql_processes = "SELECT DISTINCT name FROM Processes ORDER BY name";
$sql_bottles = "SELECT ID, CONCAT(shape, ' ', capacity, 'ml ', material) as bottle_desc FROM Bottles ORDER BY ID";
$sql_lines = "SELECT ID, name FROM ProductionLines ORDER BY name";

$process_types = $conn->query($sql_processes);
$bottle_types = $conn->query($sql_bottles);
$production_lines = $conn->query($sql_lines);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC Data</title>
    
    <!-- External Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="style.css">
    
    <!-- CSS Styles (using your existing styles)
    <style>
        /* CSS Variables for Theme Customization */
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-color: #1e293b;
            --heading-color: #0f172a;
            --border-radius: 8px;
            --transition-speed: 0.3s;
            --max-width: 1400px;
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Layout Container Styles */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: var(--max-width);
            width: 100%;
            margin: 2rem auto;
            padding: 0 2px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Navigation Styles */
        nav {
            background-color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            max-width: var(--max-width);
            margin: 0 auto;
        }

        nav ul li a {
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: all var(--transition-speed);
            font-weight: 500;
        }

        nav ul li a:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Dashboard Container Styles */
        .dashboard-container {
            width: 100%;
            margin: 2rem auto;
        }

        /* Metrics Grid Layout */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Metric Card Styles */
        .metric-card {
            background: var(--card-background);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all var(--transition-speed);
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
        }

        .metric-card h3 {
            color: var(--heading-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        /* Chart Container Styles */
        .chart-container {
            background: var(--card-background);
            padding: 2rem;
            border-radius: var(--border-radius);
            position: relative;
            height: 500px !important;
            width: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            transition: all var(--transition-speed);
        }

        /* Table Container Styles */
        .table-container {
            background: var(--card-background);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            transition: all var(--transition-speed);
        }

        /* Hover Effects */
        .chart-container:hover,
        .table-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
        }

        button {
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: var(--background-color);
            color: var(--heading-color);
            font-weight: 500;
        }

        tr:hover {
            background-color: var(--background-color);
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            nav ul {
                flex-direction: column;
                align-items: center;
            }

            .dashboard-container {
                padding: 0 1rem;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Adding specific styles for QC Data page */
        .filter-form {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
        }

        .filter-item label {
            margin-bottom: 0.5rem;
            color: var(--heading-color);
            font-weight: 500;
        }

        .filter-item select,
        .filter-item input {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .metrics-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Chart Container Styles */
        .chart-container {
            background: var(--card-background);
            padding: 2rem;
            border-radius: var(--border-radius);
            position: relative;
            height: 500px !important;
            width: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            transition: all var(--transition-speed);
        }

        /* Footer Styles */
        footer {
            background-color: var(--heading-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }
    </style> -->
</head>
<body>
    <!-- Header Section -->
    <header>
        <h1>Quality Control Data Analysis</h1>
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
        <!-- Advanced Filter Form -->
        <form method="POST" class="filter-form" data-aos="fade-up">
            <h2>Advanced Search Filters</h2>
            <div class="filter-grid">
                <div class="filter-item">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="filter-item">
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <!--<div class="filter-item">
                    <label for="batch_id">Batch ID:</label>
                    <input type="number" id="batch_id" name="batch_id" value="<?php echo $batch_id; ?>">
                </div>-->
                <div class="filter-item">
                    <label for="check_result">Check Result:</label>
                    <select id="check_result" name="check_result">
                        <option value="">All Results</option>
                        <option value="Pass" <?php echo $check_result === 'Pass' ? 'selected' : ''; ?>>Pass</option>
                        <option value="Fail" <?php echo $check_result === 'Fail' ? 'selected' : ''; ?>>Fail</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="process_type">Process Type:</label>
                    <select id="process_type" name="process_type">
                        <option value="">All Processes</option>
                        <?php while($process = $process_types->fetch_assoc()): ?>
                            <option value="<?php echo $process['name']; ?>" 
                                    <?php echo $process_type === $process['name'] ? 'selected' : ''; ?>>
                                <?php echo $process['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="bottle_type">Bottle Type:</label>
                    <select id="bottle_type" name="bottle_type">
                        <option value="">All Bottles</option>
                        <?php while($bottle = $bottle_types->fetch_assoc()): ?>
                            <option value="<?php echo $bottle['ID']; ?>"
                                    <?php echo $bottle_type === $bottle['ID'] ? 'selected' : ''; ?>>
                                <?php echo $bottle['bottle_desc']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="production_line">Production Line:</label>
                    <select id="production_line" name="production_line">
                        <option value="">All Lines</option>
                        <?php while($line = $production_lines->fetch_assoc()): ?>
                            <option value="<?php echo $line['ID']; ?>"
                                    <?php echo $production_line === $line['ID'] ? 'selected' : ''; ?>>
                                <?php echo $line['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button type="submit">
                Apply Filters
            </button>
        </form>

        <!-- Metrics Summary Section -->
        <div class="metrics-summary" data-aos="fade-up">
            <?php
            // Calculate summary metrics
            $total_checks = $result->num_rows;
            $pass_count = 0;
            $fail_count = 0;
            $data_for_chart = array();
            
            if ($total_checks > 0) {
                $result_copy = $result;
                while($row = $result_copy->fetch_assoc()) {
                    if ($row['check_result'] === 'Pass') $pass_count++;
                    else $fail_count++;
                    
                    $date = date('Y-m-d', strtotime($row['check_date']));
                    if (!isset($data_for_chart[$date])) {
                        $data_for_chart[$date] = ['pass' => 0, 'fail' => 0];
                    }
                    $data_for_chart[$date][$row['check_result'] === 'Pass' ? 'pass' : 'fail']++;
                }
            }
            ?>
            
            <div class="summary-card">
                <h3>Total Checks</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color);"><?php echo $total_checks; ?></p>
            </div>
            <div class="summary-card">
                <h3>Pass Rate</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #059669;">
                    <?php echo $total_checks > 0 ? round(($pass_count / $total_checks) * 100, 1) : 0; ?>%
                </p>
            </div>
            <div class="summary-card">
                <h3>Fail Rate</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #dc2626;">
                    <?php echo $total_checks > 0 ? round(($fail_count / $total_checks) * 100, 1) : 0; ?>%
                </p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-grid">
            <div class="chart-container" data-aos="fade-up">
                <h3>Quality Check Results Over Time</h3>
                <canvas id="resultsTimeChart"></canvas>
            </div>
            <div class="chart-container" data-aos="fade-up">
                <h3>Results by Process Type</h3>
                <canvas id="processPieChart"></canvas>
            </div>
        </div>

        <!-- Results Table -->
        <div class="table-container" data-aos="fade-up">
            <h3>Quality Control Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Batch</th>
                        <th>Process</th>
                        <th>Machine</th>
                        <th>Bottle Type</th>
                        <th>Result</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result->data_seek(0);
                    while($row = $result->fetch_assoc()) {
                        $bottle_desc = $row['bottle_shape'] . ' ' . $row['bottle_capacity'] . 'ml ' . $row['bottle_material'];
                        echo "<tr>";
                        echo "<td>" . date('Y-m-d H:i', strtotime($row['check_date'])) . "</td>";
                        echo "<td>" . $row['batch_id'] . "</td>";
                        echo "<td>" . $row['process_name'] . "</td>";
                        echo "<td>" . $row['machine_name'] . "</td>";
                        echo "<td>" . $bottle_desc . "</td>";
                        echo "<td style='color: " . ($row['check_result'] === 'Pass' ? '#059669' : '#dc2626') . "'>" 
                             . $row['check_result'] . "</td>";
                        echo "<td>" . $row['remarks'] . "</td>";
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

    <!-- JavaScript Section -->
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Prepare data for time series chart
        const timeChartData = {
            labels: <?php 
                $dates = array_keys($data_for_chart);
                echo json_encode($dates);
            ?>,
            datasets: [
                {
                    label: 'Pass',
                    data: <?php 
                        $pass_data = array_map(function($item) { return $item['pass']; }, $data_for_chart);
                        echo json_encode(array_values($pass_data));
                    ?>,
                    backgroundColor: 'rgba(5, 150, 105, 0.2)',
                    borderColor: 'rgb(5, 150, 105)',
                    borderWidth: 2,
                    tension: 0.1
                },
                {
                    label: 'Fail',
                    data: <?php 
                        $fail_data = array_map(function($item) { return $item['fail']; }, $data_for_chart);
                        echo json_encode(array_values($fail_data));
                    ?>,
                    backgroundColor: 'rgba(220, 38, 38, 0.2)',
                    borderColor: 'rgb(220, 38, 38)',
                    borderWidth: 2,
                    tension: 0.1
                }
            ]
        };

        // Create time series chart
        new Chart(document.getElementById('resultsTimeChart').getContext('2d'), {
            type: 'line',
            data: timeChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Quality Check Results Trend'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Checks'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });

        // Prepare data for process pie chart
        <?php
        // Calculate process-wise statistics
        $process_stats = array();
        $result->data_seek(0);
        while($row = $result->fetch_assoc()) {
            if (!isset($process_stats[$row['process_name']])) {
                $process_stats[$row['process_name']] = ['pass' => 0, 'fail' => 0];
            }
            $process_stats[$row['process_name']][$row['check_result'] === 'Pass' ? 'pass' : 'fail']++;
        }
        ?>

        const processChartData = {
            labels: <?php echo json_encode(array_keys($process_stats)); ?>,
            datasets: [{
                data: <?php 
                    $pass_rates = array_map(function($stats) {
                        $total = $stats['pass'] + $stats['fail'];
                        return $total > 0 ? round(($stats['pass'] / $total) * 100, 1) : 0;
                    }, $process_stats);
                    echo json_encode(array_values($pass_rates));
                ?>,
                backgroundColor: [
                    '#2563eb',
                    '#7c3aed',
                    '#db2777',
                    '#059669',
                    '#d97706'
                ]
            }]
        };

        // Create process pie chart
        new Chart(document.getElementById('processPieChart').getContext('2d'), {
            type: 'pie',
            data: processChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: 'Pass Rate by Process Type (%)'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });

        // Handle date range validation
        document.getElementById('dateRangeForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('Start date must be before or equal to end date');
            }
        });

       
        
        
            // Add export functionality
            $('#exportData').click(function() {
                const rows = [
                    ['Date', 'Batch', 'Process', 'Machine', 'Bottle Type', 'Result', 'Remarks']
                ];
                
                $('table tbody tr').each(function() {
                    const row = [];
                    $(this).find('td').each(function() {
                        row.push($(this).text());
                    });
                    rows.push(row);
                });

                let csvContent = "data:text/csv;charset=utf-8,";
                rows.forEach(function(rowArray) {
                    const row = rowArray.join(",");
                    csvContent += row + "\r\n";
                });

                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "qc_data_export.csv");
                document.body.appendChild(link);
                link.click();
            });

             // Fetch and display IP address
        $(document).ready(function() {
            $.getJSON('https://api.ipify.org?format=json', function(data) {
                $('#ip').text(data.ip);
            }).fail(function() {
                $('#ip').text('Unable to fetch IP');
            });
        });
        
    </script>
    <!-- Footer Section -->
    
</body>


<?php
// Close database connection
$conn->close();
?>
</html>