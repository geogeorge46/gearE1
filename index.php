<?php
// Include database connection
include 'config.php';

// Start the session for user management
session_start();

// Include navigation
include 'nav.php';
?>

<!-- Add more spacing after navigation -->
<div style="margin-top: 120px;"></div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEAR EQUIP - Machinery Rental Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="./styles.css" rel="stylesheet">
    <link href="video1style.css" rel="stylesheet">
    <link href="video2style.css" rel="stylesheet"> 
    
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Add new style tag -->
    <style>
        .section-title-large {
            font-size: 3.5rem !important;
            font-weight: 800 !important;
            text-align: center !important;
            color: #2c3e50 !important;
            margin-bottom: 3rem !important;
            text-transform: uppercase !important;
        }
        .machine-image {
            width: 100%;
            height: 48px;
            object-fit: cover;
        }
        .machine-image.error {
            /* Style for when image fails to load */
            background-color: #f3f4f6;
        }
        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .machine-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .machine-card:hover {
            transform: translateY(-5px);
        }

        .machine-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .machine-card h3 {
            margin: 10px 0;
            color: #333;
        }

        .machine-card .price {
            color: #27ae60;
            font-weight: bold;
            font-size: 1.1em;
            margin: 10px 0;
        }

        .machine-card button {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .machine-card button:hover {
            background: #219a52;
        }
    </style>
</head>
<body>
    <!-- Hero Section with Video Background -->
    <section class="hero" id="home" style="margin-top: 120px;">
        <div class="video-container">
            <video autoplay loop muted playsinline id="myVideo">
                <source src="images/bgfront.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="overlay"></div>
        </div>
        <div class="hero-content">
            <h1>Find the Perfect Equipment for Your Project</h1>
            <!-- Simplified Search Box without Location -->
            <div style="width: 70%; max-width: 800px; margin: 25px auto; padding: 20px; background: linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.85)); border-radius: 15px; box-shadow: 0 6px 25px rgba(0,0,0,0.2); backdrop-filter: blur(8px);">
                <div style="display: flex; gap: 12px; padding: 3px;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" placeholder="Search for machinery..." 
                            style="width: 100%; padding: 14px 20px; background: rgba(255,255,255,0.9); 
                            border: 2px solid #e1e1e1; border-radius: 10px; font-size: 14px; 
                            transition: all 0.3s ease; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);">
                    </div>
                    <button style="padding: 14px 30px; background: linear-gradient(145deg, #1a5f89, #134b6d); 
                        color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; 
                        cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(19,75,109,0.3);">
                        Search
                    </button>
                </div>
            </div>
            <p class="hero-text">Rent construction, agricultural, and industrial equipment with ease</p>
        </div>
    </section>

    <!-- Top Machines Section -->
    <section class="top-machines" id="machines">
        <h2 class="text-4xl font-extrabold text-center text-[#2c3e50] mb-12 uppercase tracking-wide">Top Machines on Rent</h2>
        <div class="machine-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 px-4 max-w-7xl mx-auto">
            <?php
            // Fetch machines from database
            $sql = "SELECT *, daily_rate as price FROM machines WHERE status = 'available' LIMIT 3";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    // First, get the average rating for each machine
                    $rating_query = "SELECT machine_id, AVG(COALESCE(rating, 0)) as avg_rating, COUNT(rating) as rating_count 
                                     FROM reviews 
                                     GROUP BY machine_id";
                    $rating_result = mysqli_query($conn, $rating_query);
                    $ratings = [];
                    while($rating_row = mysqli_fetch_assoc($rating_result)) {
                        $ratings[$rating_row['machine_id']] = [
                            'avg_rating' => round($rating_row['avg_rating'], 1),
                            'count' => $rating_row['rating_count']
                        ];
                    }
                    ?>
                    <div class="machine-card bg-white rounded-lg overflow-hidden shadow-lg transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                        <span class="badge absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Popular</span>
                        <img src="<?php 
                            if (!empty($row['image_url']) && file_exists($row['image_url'])) {
                                echo htmlspecialchars($row['image_url']);
                            } else {
                                echo 'images/default-machine.jpg';
                            }
                            ?>" 
                             alt="<?php echo htmlspecialchars($row['name']); ?>" 
                             class="w-full h-48 object-cover"
                             onerror="this.src='images/default-machine.jpg'"
                        >
                        <div class="p-6">
                            <h3 class="text-2xl font-bold text-[#2c3e50] mb-3"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <div class="flex items-center mb-2">
                                <div class="flex text-yellow-400">
                                    <?php
                                    $machine_id = $row['machine_id'];
                                    $rating = isset($ratings[$machine_id]) ? $ratings[$machine_id]['avg_rating'] : 0;
                                    $rating_count = isset($ratings[$machine_id]) ? $ratings[$machine_id]['count'] : 0;
                                    
                                    // Display stars
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif($i - 0.5 <= $rating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="text-gray-600 ml-2">
                                    (<?php echo number_format($rating, 1); ?>) 
                                    <span class="text-sm">
                                        <?php echo $rating_count; ?> reviews
                                    </span>
                                </span>
                            </div>
                            <p class="text-xl font-bold text-green-600 mb-4">₹<?php echo number_format($row['price']); ?>/day</p>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button onclick="window.location.href='rent.php?id=<?php echo $row['machine_id']; ?>'" 
                                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                                    Rent Now
                                </button>
                            <?php else: ?>
                                <button onclick="window.location.href='login.php'" 
                                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                                    Login to Rent
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col-span-3 text-center text-gray-600">
                    <p>No machines available at the moment.</p>
                </div>
                <?php
            }
            ?>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories" id="categories">
        <h2 class="text-4xl font-extrabold text-center text-[#2c3e50] mb-12 uppercase tracking-wide">Browse by Category</h2>
        <div class="category-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 px-4 max-w-7xl mx-auto">
            <div class="category-card transform transition-all duration-300 hover:scale-105 hover:shadow-xl bg-white rounded-lg overflow-hidden">
                <img src="./images/agricultural.jpg" alt="Agricultural Equipment" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#2c3e50] mb-2">Agricultural</h3>
                    <p class="text-gray-600">Tractors, Harvesters, etc.</p>
                </div>
            </div>

            <div class="category-card transform transition-all duration-300 hover:scale-105 hover:shadow-xl bg-white rounded-lg overflow-hidden">
                <img src="./images/commercial.jpg" alt="Commercial Equipment" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#2c3e50] mb-2">Commercial</h3>
                    <p class="text-gray-600">Cranes, Forklifts, etc.</p>
                </div>
            </div>

            <div class="category-card transform transition-all duration-300 hover:scale-105 hover:shadow-xl bg-white rounded-lg overflow-hidden">
                <img src="./images/manufacturing.jpg" alt="Manufacturing Equipment" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#2c3e50] mb-2">Manufacturing</h3>
                    <p class="text-gray-600">Chainsaw, Trillers etc.</p>
                </div>
            </div>

            <div class="category-card transform transition-all duration-300 hover:scale-105 hover:shadow-xl bg-white rounded-lg overflow-hidden">
                <img src="./images/household.jpg" alt="Household Equipment" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-[#2c3e50] mb-2">Household</h3>
                    <p class="text-gray-600">Power Tools, Axe etc.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <!-- Add video background -->
        <div class="video-background">
            <video autoplay muted loop playsinline id="howitworksVideo">
                <source src="./images/bghowitworks.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="overlay"></div>
        </div>

        <h2>How It Works</h2>
        <div class="steps">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Search & Select</h3>
                <p>Browse our collection and choose what you need</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Login/Register</h3>
                <p>Create an account or login to proceed</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Make Payment</h3>
                <p>Secure payment with refundable deposit</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3>Receive & Return</h3>
                <p>Get delivery and return after use</p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <h2 class="text-4xl font-extrabold text-center text-[#2c3e50] mb-12 uppercase tracking-wide">What Our Customers Say</h2>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <div class="quote">★★★★★</div>
                <p>"Excellent service and quality machinery. The rental process was smooth and hassle-free."</p>
                <div class="author">- Rajesh Kumar, Construction Manager</div>
            </div>
            <div class="testimonial-card">
                <div class="quote">★★★★★</div>
                <p>"GEAR EQUIP helped us meet our tight project deadlines by providing reliable equipment on time."</p>
                <div class="author">- Priya Sharma, Project Director</div>
            </div>
            <div class="testimonial-card">
                <div class="quote">★★★★½</div>
                <p>"Great selection of agricultural machinery. The customer support team was very helpful throughout."</p>
                <div class="author">- Amit Patel, Farm Owner</div>
            </div>
            <div class="testimonial-card">
                <div class="quote">★★★★★</div>
                <p>"The quality of equipment and maintenance standards are exceptional. Will definitely use again!"</p>
                <div class="author">- Sarah Khan, Site Engineer</div>
            </div>
            <div class="testimonial-card">
                <div class="quote">★★★★★</div>
                <p>"Their excavator rental service saved us significant upfront costs. The machines were well-maintained and performed flawlessly."</p>
                <div class="author">- Vikram Singh, Infrastructure Developer</div>
            </div>
            <div class="testimonial-card">
                <div class="quote">★★★★★</div>
                <p>"As a small business owner, finding reliable machinery was always a challenge until I discovered GEAR EQUIP. Their service is unmatched!"</p>
                <div class="author">- Meera Desai, Business Owner</div>
            </div>
        </div>
    </section>

    <!-- Add this section where you want the machines to appear -->
    <section id="machines-section" style="display: none;">
        <div class="container">
            <h2>Our Machines</h2>
            <div class="machines-grid">
                <?php
                // Fetch machines from database
                $query = "SELECT * FROM machines WHERE status = 'available'";
                $result = mysqli_query($conn, $query);

                while($machine = mysqli_fetch_assoc($result)) {
                    // First, get the average rating for each machine
                    $rating_query = "SELECT machine_id, AVG(COALESCE(rating, 0)) as avg_rating, COUNT(rating) as rating_count 
                                     FROM reviews 
                                     GROUP BY machine_id";
                    $rating_result = mysqli_query($conn, $rating_query);
                    $ratings = [];
                    while($rating_row = mysqli_fetch_assoc($rating_result)) {
                        $ratings[$rating_row['machine_id']] = [
                            'avg_rating' => round($rating_row['avg_rating'], 1),
                            'count' => $rating_row['rating_count']
                        ];
                    }
                    ?>
                    <div class="machine-card">
                        <?php if($machine['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" alt="<?php echo htmlspecialchars($machine['name']); ?>">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($machine['name']); ?></h3>
                        <div class="flex items-center mb-2">
                            <div class="flex text-yellow-400">
                                <?php
                                $machine_id = $machine['machine_id'];
                                $rating = isset($ratings[$machine_id]) ? $ratings[$machine_id]['avg_rating'] : 0;
                                $rating_count = isset($ratings[$machine_id]) ? $ratings[$machine_id]['count'] : 0;
                                
                                // Display stars
                                for($i = 1; $i <= 5; $i++) {
                                    if($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif($i - 0.5 <= $rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="text-gray-600 ml-2">
                                (<?php echo number_format($rating, 1); ?>) 
                                <span class="text-sm">
                                    <?php echo $rating_count; ?> reviews
                                </span>
                            </span>
                        </div>
                        <p class="price">₹<?php echo number_format($machine['daily_rate'], 2); ?> / day</p>
                        <p class="status">Status: <?php echo htmlspecialchars($machine['status']); ?></p>
                        <button onclick="viewMachineDetails(<?php echo $machine['machine_id']; ?>)">View Details</button>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Add this JavaScript to handle the showing/hiding -->
    <script>
   

    function viewMachineDetails(machineId) {
        // Handle viewing machine details
        // You could show a modal or redirect to a details page
    }
    </script>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>