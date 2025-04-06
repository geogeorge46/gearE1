<?php
include 'config.php';
session_start();

// Corrected query without machine_units table
$query = "SELECT 
    m.machine_id,
    m.name AS machine_name,
    m.description,
    m.daily_rate,
    m.security_deposit,
    m.model_number,
    m.manufacturer,
    m.manufacturing_year,
    m.image_url,
    m.status,
    m.category_id,
    m.subcategory_id,
    c.category_name,
    s.subcategory_name
FROM 
    machines m
    LEFT JOIN categories c ON m.category_id = c.category_id
    LEFT JOIN subcategories s ON m.subcategory_id = s.subcategory_id
ORDER BY 
    m.name";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Machines - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="p-8">
        <!-- Header with Back Button -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center space-x-4">
                <a href="manager_dashboard.php" class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                    <i class="fas fa-arrow-left text-lg"></i>
                    <span class="ml-2">Back to Dashboard</span>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Available Machines</h1>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="relative">
                    <input type="text" id="search" placeholder="Search machines..." 
                           class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                    <span class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                
                <select id="categoryFilter" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">All Categories</option>
                    <?php
                    $cat_query = "SELECT * FROM categories ORDER BY category_name";
                    $cat_result = mysqli_query($conn, $cat_query);
                    while($cat = mysqli_fetch_assoc($cat_result)) {
                        echo "<option value='" . $cat['category_id'] . "'>" . 
                             htmlspecialchars($cat['category_name']) . "</option>";
                    }
                    ?>
                </select>
                
                <select id="subcategoryFilter" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500" disabled>
                    <option value="">Select Subcategory</option>
                </select>
                
                <select id="statusFilter" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="rented">Rented</option>
                    <option value="maintenance">Under Maintenance</option>
                </select>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <div class="text-gray-600">
                Showing <span id="machineCount" class="font-semibold"><?php echo mysqli_num_rows($result); ?></span> machines
            </div>
            <select id="sortOrder" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="price_asc">Price (Low to High)</option>
                <option value="price_desc">Price (High to Low)</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php while($machine = mysqli_fetch_assoc($result)): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all duration-300 card-hover machine-card"
                     data-category="<?php echo htmlspecialchars($machine['category_id']); ?>"
                     data-subcategory="<?php echo htmlspecialchars($machine['subcategory_id'] ?? ''); ?>"
                     data-status="<?php echo htmlspecialchars($machine['status']); ?>"
                     data-name="<?php echo htmlspecialchars(strtolower($machine['machine_name'])); ?>"
                     data-price="<?php echo htmlspecialchars($machine['daily_rate']); ?>">
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($machine['machine_name']); ?>"
                             class="w-full h-64 object-cover">
                        <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-sm font-semibold
                            <?php echo $machine['status'] == 'available' ? 
                                  'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
                            <?php echo ucfirst($machine['status']); ?>
                        </span>
                    </div>

                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-2 text-gray-800">
                            <?php echo htmlspecialchars($machine['machine_name']); ?>
                        </h3>
                        <p class="text-indigo-600 font-medium mb-4">
                            <?php echo htmlspecialchars($machine['category_name']); ?> / 
                            <?php echo htmlspecialchars($machine['subcategory_name']); ?>
                        </p>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-cog w-6 text-indigo-500"></i>
                                <span class="ml-2"><?php echo htmlspecialchars($machine['model_number']); ?></span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-industry w-6 text-indigo-500"></i>
                                <span class="ml-2"><?php echo htmlspecialchars($machine['manufacturer']); ?> 
                                    (<?php echo htmlspecialchars($machine['manufacturing_year']); ?>)</span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-indian-rupee-sign w-6 text-indigo-500"></i>
                                <span class="ml-2 font-medium">₹<?php echo number_format($machine['daily_rate'], 2); ?>/day</span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-shield w-6 text-indigo-500"></i>
                                <span class="ml-2">₹<?php echo number_format($machine['security_deposit'], 2); ?> deposit</span>
                            </div>
                        </div>

                        <p class="text-gray-600 text-sm mb-6">
                            <?php echo htmlspecialchars($machine['description']); ?>
                        </p>

                        <!-- Status badge only -->
                        <div class="flex items-center">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                <?php 
                                switch($machine['status']) {
                                    case 'available':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'rented':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'maintenance':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <i class="fas fa-circle text-xs mr-1"></i>
                                <?php echo ucfirst($machine['status']); ?>
                            </span>
                        </div>

                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="quickViewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="container mx-auto px-4 h-full flex items-center justify-center">
            <div class="bg-white rounded-xl p-6 max-w-2xl w-full">
                <div class="modal-content">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('search').addEventListener('input', filterMachines);
        document.getElementById('categoryFilter').addEventListener('change', function() {
            loadSubcategories();
            filterMachines();
        });
        document.getElementById('subcategoryFilter').addEventListener('change', filterMachines);
        document.getElementById('statusFilter').addEventListener('change', filterMachines);
        document.getElementById('sortOrder').addEventListener('change', sortMachines);

        function filterMachines() {
            const search = document.getElementById('search').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const subcategory = document.getElementById('subcategoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            const cards = document.querySelectorAll('.machine-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const machineName = card.getAttribute('data-name');
                const machineCategory = card.getAttribute('data-category');
                const machineSubcategory = card.getAttribute('data-subcategory');
                const machineStatus = card.getAttribute('data-status');
                
                const matchesSearch = machineName.includes(search);
                const matchesCategory = category === '' || machineCategory === category;
                const matchesSubcategory = subcategory === '' || machineSubcategory === subcategory;
                const matchesStatus = status === '' || machineStatus === status;
                
                const shouldShow = matchesSearch && matchesCategory && 
                                 matchesSubcategory && matchesStatus;
                
                card.style.display = shouldShow ? 'block' : 'none';
                if (shouldShow) visibleCount++;
            });

            document.getElementById('machineCount').textContent = visibleCount;
        }

        function sortMachines() {
            const sortBy = document.getElementById('sortOrder').value;
            const container = document.querySelector('.grid');
            const cards = Array.from(container.children);
            
            cards.sort((a, b) => {
                if (sortBy === 'name_asc' || sortBy === 'name_desc') {
                    const nameA = a.getAttribute('data-name');
                    const nameB = b.getAttribute('data-name');
                    return sortBy === 'name_asc' ? 
                        nameA.localeCompare(nameB) : 
                        nameB.localeCompare(nameA);
                }
                
                if (sortBy === 'price_asc' || sortBy === 'price_desc') {
                    const priceA = parseFloat(a.getAttribute('data-price'));
                    const priceB = parseFloat(b.getAttribute('data-price'));
                    return sortBy === 'price_asc' ? 
                        priceA - priceB : 
                        priceB - priceA;
                }
            });
            
            cards.forEach(card => container.appendChild(card));
        }

        function loadSubcategories() {
            const categoryId = document.getElementById('categoryFilter').value;
            const subcategorySelect = document.getElementById('subcategoryFilter');
            
            if(categoryId) {
                subcategorySelect.disabled = true;
                subcategorySelect.innerHTML = '<option value="">Loading...</option>';
                
                const xhr = new XMLHttpRequest();
                xhr.open('GET', `get_subcategories.php?category_id=${categoryId}`, true);
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';
                            data.forEach(subcategory => {
                                subcategorySelect.innerHTML += `
                                    <option value="${subcategory.subcategory_id}">
                                        ${subcategory.subcategory_name}
                                    </option>`;
                            });
                            subcategorySelect.disabled = false;
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                            subcategorySelect.disabled = true;
                        }
                    } else {
                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                        subcategorySelect.disabled = true;
                    }
                };
                
                xhr.onerror = function() {
                    subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    subcategorySelect.disabled = true;
                };
                
                xhr.send();
            } else {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                subcategorySelect.disabled = true;
            }
        }

        if (document.getElementById('categoryFilter').value) {
            loadSubcategories();
        }

        filterMachines();
    });
    </script>

    <style>
    .loading {
        position: relative;
    }
    .loading:after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.8) url('loading.gif') center no-repeat;
        z-index: 1;
    }
    </style>
</body>
</html> 