<?php
include 'config.php';
session_start();

// Get subcategory ID from URL
$subcategory_id = isset($_GET['subcategory']) ? (int)$_GET['subcategory'] : 0;

// Fetch subcategory and category details
$category_query = "SELECT s.subcategory_name, s.description as sub_desc, 
                         c.category_name, c.category_id
                  FROM subcategories s
                  JOIN categories c ON s.category_id = c.category_id
                  WHERE s.subcategory_id = ?";
$stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($stmt, "i", $subcategory_id);
mysqli_stmt_execute($stmt);
$nav_result = mysqli_stmt_get_result($stmt);
$nav_info = mysqli_fetch_assoc($nav_result);

// Check if subcategory exists
if (!$nav_info) {
    // Handle missing subcategory more gracefully
    // Just redirect without setting an error message
    header('Location: user_categories.php');
    exit();
}

// Fetch machines
$query = "SELECT * FROM machines WHERE subcategory_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $subcategory_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$machines = [];
while ($row = mysqli_fetch_assoc($result)) {
    $machines[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Machines - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .machines-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .machine-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .machine-card:hover {
            transform: translateY(-10px);
        }

        .machine-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .machine-details {
            padding: 20px;
        }

        .machine-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .machine-description {
            color: #4a5568;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .machine-specs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #718096;
            font-size: 0.9rem;
        }

        .price-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f7fafc;
            border-top: 1px solid #edf2f7;
        }

        .price {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .rent-button {
            padding: 8px 20px;
            background: #4CAF50;
            color: white;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .rent-button:hover {
            background: #43A047;
        }

        .login-button {
            padding: 8px 20px;
            background: #3498db;
            color: white;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .login-button:hover {
            background: #2980b9;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding: 15px 25px;
            background: white;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: #2980b9;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'nav.php'; ?>
    
    <div class="machines-container">
        <div class="breadcrumb">
            <a href="user_categories.php"><i class="fas fa-home"></i> Categories</a>
            <?php if ($nav_info): ?>
                <i class="fas fa-chevron-right text-gray-400"></i>
                <a href="user_subcategories.php?category=<?php echo htmlspecialchars($nav_info['category_id']); ?>">
                    <?php echo htmlspecialchars($nav_info['category_name']); ?>
                </a>
                <i class="fas fa-chevron-right text-gray-400"></i>
                <span class="text-gray-600"><?php echo htmlspecialchars($nav_info['subcategory_name']); ?></span>
            <?php endif; ?>
        </div>

        <h1 class="text-4xl font-bold text-center mb-4">
            <?php echo $nav_info ? htmlspecialchars($nav_info['subcategory_name']) : 'Machines'; ?>
        </h1>
        <p class="text-center text-gray-600 mb-8">
            <?php echo $nav_info ? htmlspecialchars($nav_info['sub_desc']) : ''; ?>
        </p>

        <div class="machines-grid">
            <?php foreach($machines as $machine): ?>
                <div class="machine-card">
                    <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($machine['name']); ?>"
                         class="machine-image">
                    
                    <div class="machine-details">
                        <h2 class="machine-name"><?php echo htmlspecialchars($machine['name']); ?></h2>
                        <p class="machine-description"><?php echo htmlspecialchars($machine['description']); ?></p>
                        
                        <div class="machine-specs">
                            <div class="spec-item">
                                <i class="fas fa-industry"></i>
                                <span><?php echo htmlspecialchars($machine['manufacturer']); ?></span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo htmlspecialchars($machine['manufacturing_year']); ?></span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-barcode"></i>
                                <span><?php echo htmlspecialchars($machine['model_number']); ?></span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-circle <?php echo $machine['status'] === 'available' ? 'text-green-500' : 'text-red-500'; ?>"></i>
                                <span class="<?php echo $machine['status'] === 'available' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($machine['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="price-section">
                        <div class="price">
                            ₹<?php echo number_format($machine['daily_rate'], 2); ?>/day
                        </div>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($machine['status'] === 'available'): ?>
                                <a href="cart.php?action=add&machine=<?php echo $machine['machine_id']; ?>" 
                                   class="rent-button">
                                    Add to Cart
                                </a>
                            <?php else: ?>
                                <button class="bg-gray-400 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                                    Currently Rented
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="login-button">
                                Login to Rent
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($machines)): ?>
                <div class="col-span-full text-center py-10">
                    <i class="fas fa-tools text-5xl text-gray-400 mb-4"></i>
                    <p class="text-xl text-gray-600">No machines found in this subcategory.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 