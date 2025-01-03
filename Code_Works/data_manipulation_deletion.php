<?php
// Database connection parameters
$servername = "mydb.itap.purdue.edu";
$username = "g1140956";
$password = "group6";
$database = $username;

// Start the session to manage form submissions
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Establish database connection
try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle data deletion request
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'true') {
    try {
        // Start transaction using older syntax
        $conn->autocommit(FALSE);

        // First, disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=0");

        
        // Delete data in specific order to maintain referential integrity
        $delete_queries = [
            "DELETE FROM SensorResults",
            "DELETE FROM Faults",
            "DELETE FROM QualityControl",
            "DELETE FROM Maintenance WHERE date >= '2023-01-01'", // Only delete maintenance records from 2023 onwards
            "DELETE p1 FROM Processes p1 INNER JOIN Processes p2",
            "DELETE FROM Batches",
            "DELETE FROM Inventory"
        ];

        // Execute each deletion query
        $success = true;
        $error_messages = [];
        
        foreach ($delete_queries as $query) {
            try {
                if (!$conn->query($query)) {
                    $success = false;
                    $error_messages[] = "Error executing query ($query): " . $conn->error;
                    break;
                }
            } catch (Exception $e) {
                $success = false;
                $error_messages[] = "Exception executing query ($query): " . $e->getMessage();
                break;
            }
        }

        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");

        if ($success) {
            $conn->commit();
            $success_message = "Dynamic data has been successfully deleted. Static configurations remain intact.";
        } else {
            $conn->rollback();
            $error_message = "Failed to delete data:<br>" . implode("<br>", $error_messages);
        }
        
        // Restore autocommit mode
        $conn->autocommit(TRUE);
        
    } catch (Exception $e) {
        // Make sure to re-enable foreign key checks and autocommit even if an error occurs
        try {
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            $conn->rollback();
            $conn->autocommit(TRUE);
        } catch (Exception $e2) {
            // Log this error but don't override the main error message
        }
        
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get messages from session if they exist
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Get current data counts with error handling
$data_counts = [];
$count_queries = [
    'Batches' => "SELECT COUNT(*) as count FROM Batches",
    'Maintenance' => "SELECT COUNT(*) as count FROM Maintenance WHERE date >= '2023-01-01'",
    'Processes' => "SELECT COUNT(*) as count FROM Processes",
    'Quality Checks' => "SELECT COUNT(*) as count FROM QualityControl",
    'Sensor Results' => "SELECT COUNT(*) as count FROM SensorResults",
    'Faults' => "SELECT COUNT(*) as count FROM Faults",
    'Inventory Records' => "SELECT COUNT(*) as count FROM Inventory"
];

foreach ($count_queries as $name => $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $data_counts[$name] = $result->fetch_assoc()['count'];
        } else {
            $data_counts[$name] = "Error";
        }
    } catch (Exception $e) {
        $data_counts[$name] = "Error";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Administration</title>
    
    <!-- External Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Database Deletion</h1>
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

    <div id = "deletion">    
        <div class="main-container">
            <?php if (isset($success_message)): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="admin-grid" data-aos="fade-up">
                <?php foreach ($data_counts as $name => $count): ?>
                <div class="admin-card">
                    <h3><?php echo htmlspecialchars($name); ?></h3>
                    <div class="data-count"><?php echo number_format($count); ?></div>
                    <p>Current record count</p>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="danger-zone" data-aos="fade-up">
                <h2>⚠️ Danger Zone</h2>
                <p>This action will delete all dynamic data while preserving static configurations (machines, production lines, sensors, maintenance people, and static processes).</p>
                
                <div class="confirmation-box">
                    <form id="deleteForm" method="POST" onsubmit="return confirmDelete()">
                        <label>
                            <input type="checkbox" id="confirmCheckbox" required>
                            I understand that this action is irreversible
                        </label>
                        <br><br>
                        <input type="hidden" name="confirm_delete" value="true">
                        <button type="submit" class="delete-btn" id="deleteButton" disabled>
                            Delete Dynamic Data
                        </button>
                    </form>
                </div>
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

        // Handle delete confirmation
        document.getElementById('confirmCheckbox').addEventListener('change', function() {
            document.getElementById('deleteButton').disabled = !this.checked;
        });

        function confirmDelete() {
            return confirm('Are you absolutely sure you want to delete all dynamic data? This action cannot be undone.');
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>