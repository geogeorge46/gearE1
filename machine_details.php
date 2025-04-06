<?php
require_once 'includes/db_connection.php';
session_start();

if (!isset($_GET['id'])) {
    header('Location: machines.php');
    exit;
}

$machine_id = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT * FROM machines WHERE id = '$machine_id'";
$result = mysqli_query($conn, $query);
$machine = mysqli_fetch_assoc($result);

if (!$machine) {
    header('Location: machines.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Details - <?php echo htmlspecialchars($machine['name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .machine-details {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .machine-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }

        .maintenance-history {
            margin-top: 30px;
        }

        .history-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="machine-details">
            <h1><?php echo htmlspecialchars($machine['name']); ?></h1>
            
            <div class="machine-stats">
                <div class="stat-card">
                    <h3>Status</h3>
                    <p><?php echo htmlspecialchars($machine['status']); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Location</h3>
                    <p><?php echo htmlspecialchars($machine['location']); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Last Maintenance</h3>
                    <p><?php echo htmlspecialchars($machine['last_maintenance']); ?></p>
                </div>
            </div>

            <div class="machine-description">
                <h2>Description</h2>
                <p><?php echo htmlspecialchars($machine['description']); ?></p>
            </div>

            <div class="maintenance-history">
                <h2>Maintenance History</h2>
                <?php
                $history_query = "SELECT * FROM maintenance_history WHERE machine_id = '$machine_id' ORDER BY date DESC";
                $history_result = mysqli_query($conn, $history_query);
                
                while ($history = mysqli_fetch_assoc($history_result)) {
                    echo '<div class="history-item">';
                    echo '<p><strong>Date:</strong> ' . htmlspecialchars($history['date']) . '</p>';
                    echo '<p><strong>Type:</strong> ' . htmlspecialchars($history['maintenance_type']) . '</p>';
                    echo '<p><strong>Notes:</strong> ' . htmlspecialchars($history['notes']) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 