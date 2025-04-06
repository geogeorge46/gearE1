<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle form submission for new machine
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    
    // Validate machine name (must start with a letter)
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
        
        // Handle image upload
        $image_url = '';
        if(isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
            $target_dir = "uploads/";
            $image_url = $target_dir . time() . '_' . basename($_FILES["image_url"]["name"]);
            move_uploaded_file($_FILES["image_url"]["tmp_name"], $image_url);
        }
        
        // Insert the machine
        $query = "INSERT INTO machines (name, description, daily_rate, security_deposit, 
                  category_id, subcategory_id, model_number, manufacturer, manufacturing_year,
                  purchase_date, purchase_price, maintenance_interval, next_maintenance_date,
                  image_url, status) 
                  VALUES ('$name', '$description', '$daily_rate', '$security_deposit', 
                  '$category_id', '$subcategory_id', '$model_number', '$manufacturer', 
                  '$manufacturing_year', '$purchase_date', '$purchase_price', 
                  '$maintenance_interval', '$next_maintenance_date', '$image_url', 'available')";
        
        if (mysqli_query($conn, $query)) {
            $success_message = "Machine added successfully!";
        } else {
            $error_message = "Error adding machine: " . mysqli_error($conn);
        }
    }
}

// Get all categories for the dropdown
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Machines - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Machine Name Validation
        const nameInput = document.querySelector('input[name="name"]');
        const nameError = document.createElement('span');
        nameError.className = 'text-red-500 text-sm mt-1';
        nameInput.parentNode.appendChild(nameError);

        nameInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^[a-zA-Z][a-zA-Z0-9\s-]*$/.test(value);
            
            if (!value) {
                nameError.textContent = 'Machine name is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (!isValid) {
                nameError.textContent = 'Must start with a letter and contain only letters, numbers, spaces and hyphens';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                nameError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Daily Rate Validation
        const rateInput = document.querySelector('input[name="daily_rate"]');
        const rateError = document.createElement('span');
        rateError.className = 'text-red-500 text-sm mt-1';
        rateInput.parentNode.appendChild(rateError);

        rateInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            
            if (!this.value) {
                rateError.textContent = 'Daily rate is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (isNaN(value) || value < 10 || value > 10000) {
                rateError.textContent = 'Daily rate must be between ₹10 and ₹10000';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                rateError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Security Deposit Validation
        const depositInput = document.querySelector('input[name="security_deposit"]');
        const depositError = document.createElement('span');
        depositError.className = 'text-red-500 text-sm mt-1';
        depositInput.parentNode.appendChild(depositError);

        depositInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            
            if (!this.value) {
                depositError.textContent = 'Security deposit is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (isNaN(value) || value <= 0) {
                depositError.textContent = 'Security deposit must be greater than 0';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                depositError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Manufacturer Validation
        const manufacturerInput = document.querySelector('input[name="manufacturer"]');
        const manufacturerError = document.createElement('span');
        manufacturerError.className = 'text-red-500 text-sm mt-1';
        manufacturerInput.parentNode.appendChild(manufacturerError);

        manufacturerInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^[a-zA-Z\s]+$/.test(value);
            
            if (!value) {
                manufacturerError.textContent = 'Manufacturer name is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (!isValid) {
                manufacturerError.textContent = 'Manufacturer name must contain only letters and spaces';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                manufacturerError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Purchase Price Validation
        const priceInput = document.querySelector('input[name="purchase_price"]');
        const priceError = document.createElement('span');
        priceError.className = 'text-red-500 text-sm mt-1';
        priceInput.parentNode.appendChild(priceError);

        priceInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            
            if (!this.value) {
                priceError.textContent = 'Purchase price is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (isNaN(value) || value <= 0) {
                priceError.textContent = 'Purchase price must be greater than 0';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                priceError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Maintenance Interval and Next Maintenance Date
        const intervalInput = document.querySelector('input[name="maintenance_interval"]');
        const nextMaintenanceInput = document.querySelector('input[name="next_maintenance_date"]');
        const purchaseDateInput = document.querySelector('input[name="purchase_date"]');
        const intervalError = document.createElement('span');
        intervalError.className = 'text-red-500 text-sm mt-1';
        intervalInput.parentNode.appendChild(intervalError);

        function updateNextMaintenanceDate() {
            const purchaseDate = new Date(purchaseDateInput.value);
            const interval = parseInt(intervalInput.value);
            
            if (purchaseDate && !isNaN(interval) && interval > 0) {
                const nextDate = new Date(purchaseDate);
                nextDate.setDate(nextDate.getDate() + interval);
                nextMaintenanceInput.value = nextDate.toISOString().split('T')[0];
            }
        }

        intervalInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            
            if (!this.value) {
                intervalError.textContent = 'Maintenance interval is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (isNaN(value) || value <= 0) {
                intervalError.textContent = 'Maintenance interval must be greater than 0';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                intervalError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
                updateNextMaintenanceDate();
            }
        });

        purchaseDateInput.addEventListener('change', updateNextMaintenanceDate);

        // Form Validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                    input.classList.add('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <a href="index.php" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to Store
        </a>

        <!-- Add Machine Form -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6 border-b">
                <h1 class="text-2xl font-semibold">Add New Machine</h1>
            </div>
            <div class="p-6">
                <?php if(isset($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="addMachineForm">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category_id" id="category_id" required 
                                   aria-label="Category"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Category</option>
                                <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label for="subcategory_id" class="block text-sm font-medium text-gray-700 mb-2">Subcategory</label>
                            <select name="subcategory_id" id="subcategory_id" required 
                                   aria-label="Subcategory"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Category First</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Machine Name</label>
                            <input type="text" id="name" name="name" required 
                                   aria-label="Machine Name"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="model_number" class="block text-sm font-medium text-gray-700 mb-2">Model Number</label>
                            <input type="text" id="model_number" name="model_number" required 
                                   aria-label="Model Number"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label for="daily_rate" class="block text-sm font-medium text-gray-700 mb-2">Daily Rate (₹)</label>
                            <input type="number" id="daily_rate" name="daily_rate" required step="0.01" min="10" max="10000"
                                   aria-label="Daily Rate"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="security_deposit" class="block text-sm font-medium text-gray-700 mb-2">Security Deposit (₹)</label>
                            <input type="number" id="security_deposit" name="security_deposit" required step="0.01" min="0"
                                   aria-label="Security Deposit"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                            <input type="text" id="manufacturer" name="manufacturer" required 
                                   aria-label="Manufacturer"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="manufacturing_year" class="block text-sm font-medium text-gray-700 mb-2">Manufacturing Year</label>
                            <input type="number" id="manufacturing_year" name="manufacturing_year" required 
                                   min="1900" max="<?php echo date('Y'); ?>"
                                   aria-label="Manufacturing Year"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                            <input type="date" id="purchase_date" name="purchase_date" required 
                                   max="<?php echo date('Y-m-d'); ?>"
                                   aria-label="Purchase Date"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">Purchase Price (₹)</label>
                            <input type="number" id="purchase_price" name="purchase_price" required step="0.01" min="0"
                                   aria-label="Purchase Price"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label for="maintenance_interval" class="block text-sm font-medium text-gray-700 mb-2">Maintenance Interval (Days)</label>
                            <input type="number" id="maintenance_interval" name="maintenance_interval" required min="1"
                                   aria-label="Maintenance Interval"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="next_maintenance_date" class="block text-sm font-medium text-gray-700 mb-2">Next Maintenance Date</label>
                            <input type="date" id="next_maintenance_date" name="next_maintenance_date" required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   aria-label="Next Maintenance Date"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="3" required 
                                  aria-label="Description"
                                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="mt-4">
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-2">Machine Image</label>
                        <input type="file" id="image_url" name="image_url" accept="image/*" required 
                               aria-label="Machine Image"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mt-6">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Add Machine
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Machines List -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">All Machines</h2>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="pb-4 font-medium">Machine</th>
                            <th class="pb-4 font-medium">Category</th>
                            <th class="pb-4 font-medium">Daily Rate</th>
                            <th class="pb-4 font-medium">Status</th>
                            <th class="pb-4 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT m.*, c.category_name, s.subcategory_name
                                  FROM machines m 
                                  LEFT JOIN categories c ON m.category_id = c.category_id 
                                  LEFT JOIN subcategories s ON m.subcategory_id = s.subcategory_id 
                                  ORDER BY m.name";
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo '<tr class="border-b">';
                                echo '<td class="py-4">' . htmlspecialchars($row['name']) . '</td>';
                                echo '<td class="py-4">' . htmlspecialchars($row['category_name'] ?? 'N/A') . 
                                     ' / ' . htmlspecialchars($row['subcategory_name'] ?? 'N/A') . '</td>';
                                echo '<td class="py-4">₹' . htmlspecialchars($row['daily_rate']) . '</td>';
                                echo '<td class="py-4">' . htmlspecialchars($row['status']) . '</td>';
                                echo '<td class="py-4">';
                                echo '<a href="edit_machine.php?id=' . $row['machine_id'] . '" 
                                        class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="py-4 text-center">No machines found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Function to load subcategories
        function loadSubcategories(categoryId) {
            console.log('Loading subcategories for category ID:', categoryId);
            
            if (!categoryId) {
                $('#subcategory_id').html('<option value="">Select Category First</option>');
                return;
            }
            
            // Show loading indicator
            $('#subcategory_id').html('<option value="">Loading...</option>');
            
            $.ajax({
                url: 'get_subcategories.php',
                type: 'GET',
                data: { category_id: categoryId },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    console.log('AJAX Success. Response:', response);
                    
                    if (response.status === 'success') {
                        var options = '<option value="">Select Subcategory</option>';
                        $.each(response.subcategories, function(index, subcategory) {
                            options += '<option value="' + subcategory.id + '">' + 
                                       subcategory.name + '</option>';
                        });
                        $('#subcategory_id').html(options);
                    } else if (response.status === 'empty') {
                        $('#subcategory_id').html('<option value="">No subcategories available</option>');
                    } else {
                        $('#subcategory_id').html('<option value="">Error: ' + response.message + '</option>');
                        console.error('Error loading subcategories:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response Text:', xhr.responseText);
                    $('#subcategory_id').html('<option value="">Error loading subcategories</option>');
                }
            });
        }
        
        // Bind change event to category dropdown
        $('#category_id').change(function() {
            var categoryId = $(this).val();
            console.log('Category changed to:', categoryId);
            loadSubcategories(categoryId);
        });
        
        // If category is pre-selected, load subcategories
        var initialCategoryId = $('#category_id').val();
        if (initialCategoryId) {
            console.log('Initial category ID:', initialCategoryId);
            loadSubcategories(initialCategoryId);
        }
    });
    </script>
</body>
</html> 