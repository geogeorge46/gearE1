<?php
include 'config.php';
session_start();

// Update query to include image_data
$query = "SELECT category_id, category_name, description, image_data FROM categories WHERE status = 'active'";
$result = mysqli_query($conn, $query);

// Store categories in array
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Categories - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .categories-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .category-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            height: 300px;
            background-color: #f8f9fa;
        }

        .category-card:hover {
            transform: translateY(-10px);
        }

        .category-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 20px;
            color: white;
        }

        .category-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .category-description {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'nav.php'; ?>
    
    <div class="categories-container">
        <h1 class="text-4xl font-bold text-center mb-8">Machine Categories</h1>
        
        <div class="categories-grid">
            <?php foreach($categories as $category): ?>
                <a href="user_subcategories.php?category=<?php echo (int)$category['category_id']; ?>" 
                   class="category-card">
                    <?php if(!empty($category['image_data'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($category['image_data']); ?>" 
                             alt="<?php echo htmlspecialchars($category['category_name']); ?>"
                             class="category-image">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-cogs"></i>
                        </div>
                    <?php endif; ?>
                    <div class="category-overlay">
                        <h2 class="category-title">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </h2>
                        <?php if(!empty($category['description'])): ?>
                            <p class="category-description">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 