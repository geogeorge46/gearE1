//we are testing the ajax request to get the subcategories




















<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX for Subcategories</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test AJAX for Subcategories</h1>
    
    <div>
        <label for="category">Select Category:</label>
        <select id="category">
            <option value="">Select a category</option>
            <?php
            include 'config.php';
            $query = "SELECT category_id, category_name FROM categories ORDER BY category_name";
            $result = mysqli_query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<option value="' . $row['category_id'] . '">' . htmlspecialchars($row['category_name']) . '</option>';
            }
            ?>
        </select>
    </div>
    
    <div>
        <label for="subcategory">Subcategory:</label>
        <select id="subcategory">
            <option value="">Select a category first</option>
        </select>
    </div>
    
    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>
    
    <script>
    $(document).ready(function() {
        $('#category').change(function() {
            var categoryId = $(this).val();
            $('#result').html('Loading subcategories for category ID: ' + categoryId + '...');
            
            if (!categoryId) {
                $('#subcategory').html('<option value="">Select a category first</option>');
                $('#result').html('No category selected');
                return;
            }
            
            // Make AJAX request
            $.ajax({
                url: 'get_subcategories.php',
                type: 'GET',
                data: { category_id: categoryId },
                dataType: 'json',
                success: function(response) {
                    $('#result').html('AJAX Success. Response: ' + JSON.stringify(response));
                    
                    if (response.status === 'success') {
                        var options = '<option value="">Select a subcategory</option>';
                        $.each(response.subcategories, function(index, subcategory) {
                            options += '<option value="' + subcategory.id + '">' + subcategory.name + '</option>';
                        });
                        $('#subcategory').html(options);
                    } else {
                        $('#subcategory').html('<option value="">Error loading subcategories</option>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#result').html('AJAX Error: ' + status + ', ' + error + '<br>Response Text: ' + xhr.responseText);
                    $('#subcategory').html('<option value="">Error loading subcategories</option>');
                }
            });
        });
    });
    </script>
</body>
</html> 