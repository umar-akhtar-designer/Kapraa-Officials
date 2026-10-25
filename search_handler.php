<?php
    // Include database connection
    include 'admin/include/config.php';

    // Get search term
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';

    // If search term is empty, return empty array
    if (empty($searchTerm)) {
        echo json_encode([]);
        exit;
    }

    // Sanitize search term before using in SQL — prevents SQL injection
    $searchTerm = $connection_database->real_escape_string($searchTerm);

    // Query products matching the search term
    $query = "SELECT id, name, image, slug FROM products WHERE (name LIKE '%$searchTerm%' OR description LIKE '%$searchTerm%') AND is_active != 0 LIMIT 10";

    $result = $connection_database->query($query);

    $products = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Extract the first image from the comma-separated list
            $images = explode(',', $row['image']);
            $firstImage = trim($images[0]);
            
            // Build the image URL
            $imageUrl = !empty($firstImage) ? $url_domain . '/admin/images/product/' . $firstImage : $url_domain . '/images/waiting images.jpeg';
            
            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'image' => $imageUrl,
                'url' => $url_domain . '/product/' . $row['slug']
            ];
        }
    }

    // Return as JSON
    header('Content-Type: application/json');
    header('ngrok-skip-browser-warning: true');
    echo json_encode($products);
?>