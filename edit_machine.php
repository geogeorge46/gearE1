<?php
include 'config.php';
session_start();

// Handle image serving if 'image' parameter is present
if (isset($_GET['image'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT image_data FROM machines WHERE machine_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($image_data);
        $stmt->fetch();
        header("Content-Type: image/jpeg");
        echo $image_data;
        exit();
    }
    exit();
}

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Get machine details
if (isset($_GET['id'])) {
    $machine_id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM machines WHERE machine_id = '$machine_id'";
    $result = mysqli_query($conn, $query);
    $machine = mysqli_fetch_assoc($result);
    
    if (!$machine) {
        header('Location: manage_machines.php');
        exit();
    }
} else {
    header('Location: manage_machines.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    
    // Validate machine name
    if (!preg_match("/^[a-zA-Z][a-zA-Z0-9\s-]*$/", $name)) {
        $error_message = "Machine name must start with a letter and can contain only letters, numbers, spaces and hyphens";
    } else {
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $daily_rate = mysqli_real_escape_string($conn, $_POST['daily_rate']);
        $security_deposit = mysqli_real_escape_string($conn, $_POST['security_deposit']);
        $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
        $subcategory_id = mysqli_real_escape_string($conn, $_POST['subcategory_id']);
        $model_number = mysqli_real_escape_string($conn, $_POST['model_number']);
        $manufacturer = mysqli_real_escape_string($conn, $_POST['manufacturer']);
        $manufacturing_year = mysqli_real_escape_string($conn, $_POST['manufacturing_year']);
        $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date']);
        $purchase_price = mysqli_real_escape_string($conn, $_POST['purchase_price']);
        $maintenance_interval = mysqli_real_escape_string($conn, $_POST['maintenance_interval']);
        $next_maintenance_date = mysqli_real_escape_string($conn, $_POST['next_maintenance_date']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Handle image upload if new image is provided
        $image_url = $machine['image_url']; // Keep existing image by default
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $upload_dir = "uploads/machines/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = uniqid() . '.' . $filetype;
                $new_image_url = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $new_image_url)) {
                    // Delete old image if exists
                    if ($image_url && file_exists($image_url)) {
                        unlink($image_url);
                    }
                    $image_url = $new_image_url;
                }
            }
        }

        // Update query with image_url
        $query = "UPDATE machines SET 
                  name = ?,
                  description = ?,
                  daily_rate = ?,
                  security_deposit = ?,
                  category_id = ?,
                  subcategory_id = ?,
                  model_number = ?,
                  manufacturer = ?,
                  manufacturing_year = ?,
                  purchase_date = ?,
                  purchase_price = ?,
                  maintenance_interval = ?,
                  next_maintenance_date = ?,
                  status = ?,
                  image_url = ?
                  WHERE machine_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssddiiisssdisssi", 
            $name, $description, $daily_rate, $security_deposit,
            $category_id, $subcategory_id, $model_number, $manufacturer,
            $manufacturing_year, $purchase_date, $purchase_price,
            $maintenance_interval, $next_maintenance_date, $status,
            $image_url, $machine_id
        );
        
        if ($stmt->execute()) {
            $success_message = "Machine updated successfully!";
            // Refresh machine data
            $result = mysqli_query($conn, "SELECT * FROM machines WHERE machine_id = '$machine_id'");
            $machine = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error updating machine: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Machine - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="manage_machines.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Manage Machines
            </a>
        </div>

        <!-- Edit Machine Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Edit Machine</h2>
            
            <?php if(isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category_id" id="category_id" required class="w-full px-3 py-2 border rounded-lg">
                            <?php
                            $categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
                            while($category = mysqli_fetch_assoc($categories)) {
                                $selected = ($category['category_id'] == $machine['category_id']) ? 'selected' : '';
                                echo "<option value='" . $category['category_id'] . "' $selected>" . 
                                     htmlspecialchars($category['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subcategory</label>
                        <select name="subcategory_id" id="subcategory_id" required class="w-full px-3 py-2 border rounded-lg">
                            <?php
                            $subcategories = mysqli_query($conn, "SELECT * FROM subcategories WHERE category_id = '{$machine['category_id']}' ORDER BY subcategory_name");
                            while($subcategory = mysqli_fetch_assoc($subcategories)) {
                                $selected = ($subcategory['subcategory_id'] == $machine['subcategory_id']) ? 'selected' : '';
                                echo "<option value='" . $subcategory['subcategory_id'] . "' $selected>" . 
                                     htmlspecialchars($subcategory['subcategory_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Name</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($machine['name']); ?>"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model Number</label>
                        <input type="text" name="model_number" required value="<?php echo htmlspecialchars($machine['model_number']); ?>"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Daily Rate (₹)</label>
                        <input type="number" name="daily_rate" required value="<?php echo htmlspecialchars($machine['daily_rate']); ?>"
                               min="10" max="10000" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Security Deposit (₹)</label>
                        <input type="number" name="security_deposit" required value="<?php echo htmlspecialchars($machine['security_deposit']); ?>"
                               step="0.01" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" required class="w-full px-3 py-2 border rounded-lg">
                            <?php
                            $statuses = ['available', 'rented', 'maintenance', 'damaged'];
                            foreach($statuses as $status) {
                                $selected = ($status == $machine['status']) ? 'selected' : '';
                                echo "<option value='$status' $selected>" . ucfirst($status) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                        <input type="text" name="manufacturer" required value="<?php echo htmlspecialchars($machine['manufacturer']); ?>"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Manufacturing Year</label>
                        <input type="number" name="manufacturing_year" required 
                               value="<?php echo htmlspecialchars($machine['manufacturing_year']); ?>"
                               min="1900" max="<?php echo date('Y'); ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                        <input type="date" name="purchase_date" required 
                               value="<?php echo htmlspecialchars($machine['purchase_date']); ?>"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Price (₹)</label>
                        <input type="number" name="purchase_price" required 
                               value="<?php echo htmlspecialchars($machine['purchase_price']); ?>"
                               step="0.01" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Maintenance Interval (Days)</label>
                        <input type="number" name="maintenance_interval" required 
                               value="<?php echo htmlspecialchars($machine['maintenance_interval']); ?>"
                               min="1" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Next Maintenance Date</label>
                    <input type="date" name="next_maintenance_date" required 
                           value="<?php echo htmlspecialchars($machine['next_maintenance_date']); ?>"
                           class="w-full px-3 py-2 border rounded-lg">
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" required 
                              class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($machine['description']); ?></textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                    <?php if(isset($machine['image_url']) && $machine['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                             alt="Machine Image" 
                             class="h-32 mb-2">
                    <?php else: ?>
                        <div class="w-32 h-32 bg-gray-200 rounded flex items-center justify-center mb-2">
                            <span class="text-gray-500">No image</span>
                        </div>
                    <?php endif; ?>
                    
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Image</label>
                    <input type="file" 
                           name="image" 
                           accept="image/*" 
                           class="w-full px-3 py-2 border rounded-lg"
                           onchange="previewImage(this)">
                    <div id="imagePreview" class="mt-2"></div>
                    <p class="text-sm text-gray-500 mt-1">Leave empty to keep current image</p>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Machine
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#category_id').change(function() {
            var category_id = $(this).val();
            if(category_id) {
                $.ajax({
                    url: 'get_subcategories.php',
                    type: 'POST',
                    data: {category_id: category_id},
                    success: function(response) {
                        $('#subcategory_id').html(response);
                    }
                });
            } else {
                $('#subcategory_id').html('<option value="">Select Subcategory</option>');
            }
        });
    });

    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'h-32';
                preview.appendChild(img);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html> 