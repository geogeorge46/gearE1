<?php
include 'config.php';
session_start();

// Get the category ID from URL
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if (!$category_id) {
    header('Location: machines.php');
    exit();
}

// Get category details
$category_query = "SELECT category_name, description FROM categories WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$category_result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($category_result);

// Get subcategories for this category
$subcategories_query = "SELECT * FROM subcategories WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $subcategories_query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$subcategories_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['category_name']); ?> - Subcategories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .subcategories-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        .subcategories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .subcategory-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .subcategory-card:hover {
            transform: translateY(-5px);
        }

        .subcategory-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .subcategory-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 20px;
            color: white;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: #2c3e50;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background: #34495e;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="subcategories-container">
        <a href="machines.php" class="back-button">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Categories
        </a>

        <h1 class="text-3xl font-bold mb-4">
            <?php echo htmlspecialchars($category['category_name']); ?> - Subcategories
        </h1>
        
        <p class="text-gray-600 mb-8"><?php echo htmlspecialchars($category['description']); ?></p>

        <div class="subcategories-grid">
            <?php while($subcategory = mysqli_fetch_assoc($subcategories_result)): ?>
                <a href="display_machines.php?category=<?php echo $category_id; ?>&subcategory=<?php echo $subcategory['subcategory_id']; ?>" 
                   class="subcategory-card">
                    <img src="images/subcategories/<?php echo strtolower(str_replace(' ', '-', $subcategory['subcategory_name'])); ?>.jpg" 
                         alt="<?php echo htmlspecialchars($subcategory['subcategory_name']); ?>"
                         class="subcategory-image"
                         onerror="this.src='images/default-subcategory.jpg'">
                    <div class="subcategory-overlay">
                        <h2 class="text-xl font-semibold">
                            <?php echo htmlspecialchars($subcategory['subcategory_name']); ?>
                        </h2>
                        <p class="text-sm mt-2">
                            <?php echo htmlspecialchars($subcategory['description']); ?>
                        </p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>

        <?php if(mysqli_num_rows($subcategories_result) == 0): ?>
            <div class="text-center py-8">
                <p class="text-gray-500">No subcategories available.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add any additional JavaScript functionality for the subcategories page here
    });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>