    <?php
include 'config.php';
session_start();

// Get category ID from URL
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Fetch category details
$category_query = "SELECT category_name FROM categories WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$category_result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($category_result);

// Fetch subcategories
$query = "SELECT subcategory_id, subcategory_name, description, image_data 
          FROM subcategories 
          WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$subcategories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subcategories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['category_name']); ?> Subcategories - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .subcategories-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .subcategories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .subcategory-card {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.4s ease;
            cursor: pointer;
            text-decoration: none;
            height: 350px;
            background-color: #ffffff;
        }

        .subcategory-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .subcategory-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .subcategory-card:hover .subcategory-image {
            transform: scale(1.1);
        }

        .subcategory-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0.2), transparent);
            padding: 30px;
            color: white;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .subcategory-card:hover .subcategory-overlay {
            background: linear-gradient(to top, rgba(0,0,0,0.95), rgba(0,0,0,0.5), transparent);
        }

        .subcategory-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .subcategory-description {
            font-size: 15px;
            line-height: 1.6;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .subcategory-card:hover .subcategory-description {
            opacity: 1;
            transform: translateY(0);
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

        .breadcrumb span {
            color: #718096;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .page-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .page-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: #3498db;
            margin: 20px auto;
            border-radius: 2px;
        }

        .no-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #3498db, #2c3e50);
            font-size: 60px;
            color: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'nav.php'; ?>
    
    <div class="subcategories-container">
        <div class="breadcrumb">
            <a href="user_categories.php"><i class="fas fa-home"></i> Categories</a>
            <i class="fas fa-chevron-right text-gray-400"></i>
            <span class="font-medium"><?php echo htmlspecialchars($category['category_name']); ?></span>
        </div>

        <div class="page-title">
            <h1><?php echo htmlspecialchars($category['category_name']); ?> Subcategories</h1>
        </div>
        
        <div class="subcategories-grid">
            <?php foreach($subcategories as $subcategory): ?>
                <a href="machines.php?subcategory=<?php echo (int)$subcategory['subcategory_id']; ?>" 
                   class="subcategory-card">
                    <?php if(!empty($subcategory['image_data'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($subcategory['image_data']); ?>" 
                             alt="<?php echo htmlspecialchars($subcategory['subcategory_name']); ?>"
                             class="subcategory-image">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-tools"></i>
                        </div>
                    <?php endif; ?>
                    <div class="subcategory-overlay">
                        <h2 class="subcategory-title">
                            <?php echo htmlspecialchars($subcategory['subcategory_name']); ?>
                        </h2>
                        <?php if(!empty($subcategory['description'])): ?>
                            <p class="subcategory-description">
                                <?php echo htmlspecialchars($subcategory['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($subcategories)): ?>
                <div class="text-center text-gray-600 py-8 col-span-full bg-white rounded-lg shadow-md p-8">
                    <i class="fas fa-folder-open text-5xl mb-4 text-gray-400"></i>
                    <p class="text-xl">No subcategories available for this category yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 