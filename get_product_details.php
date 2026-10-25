<?php
    require_once 'admin/include/config.php'; // Adjust path as needed

    // Function to get color details by IDs
    function getColorDetailsByIds($colorIds) {
        if (empty($colorIds)) return [];
        
        $colorArray = explode(',', $colorIds);
        $colorDetails = [];
        
        foreach ($colorArray as $colorId) {
            $colorId = trim($colorId);
            if (!empty($colorId)) {
                $query = selectQuery("SELECT name, color_code FROM color WHERE id = '$colorId' AND status = 1");
                if ($query['status'] && !empty($query['data'])) {
                    $colorDetails[] = $query['data'][0];
                }
            }
        }
        
        return $colorDetails;
    }

    // Function to get size names by IDs
    function getSizeNamesByIds($sizeIds) {
        if (empty($sizeIds)) return [];
        
        $sizeArray = explode(',', $sizeIds);
        $sizeNames = [];
        
        foreach ($sizeArray as $sizeId) {
            $sizeId = trim($sizeId);
            if (!empty($sizeId)) {
                $query = selectQuery("SELECT name FROM size WHERE id = '$sizeId'");
                if ($query['status'] && !empty($query['data'])) {
                    $sizeNames[] = $query['data'][0]['name'];
                }
            }
        }
        
        return $sizeNames;
    }

    // Function to get product details for quick view
    function getProductDetails($productId) {
        global $url_domain;
        $imagePath = "/admin/images/product/";
        
        $query = selectQuery("SELECT 
            p.*,
            c.name as category_name,
            sc.name as subcategory_name,
            b.name as brand_name
            FROM products p
            LEFT JOIN category c ON p.category = c.id
            LEFT JOIN subcategory sc ON p.sub_category = sc.id
            LEFT JOIN brand b ON p.brand = b.id
            WHERE p.id = '$productId'");
        
        if ($query['status'] && !empty($query['data'])) {
            $product = $query['data'][0];
            
            // Process images
            $images = [];
            if (!empty($product['image'])) {
                $imgs = explode(',', $product['image']);
                foreach ($imgs as $img) {
                    $img = trim($img);
                    if (!empty($img)) {
                        $images[] = $url_domain . $imagePath . $img;
                    }
                }
            }
            
            // If no images, add default
            if (empty($images)) {
                $images[] = $url_domain . "/images/waiting images.jpeg";
            }
            
            // Get size names
            $sizeNames = getSizeNamesByIds($product['size']);
            
            // Get color details
            $colorDetails = getColorDetailsByIds($product['color']);
            
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'description' => $product['description'],
                'category_name' => $product['category_name'],
                'subcategory_name' => $product['subcategory_name'],
                'brand_name' => $product['brand_name'],
                'price' => $product['price'],
                'dis_amount' => $product['dis_amount'],
                'price_after_discount' => $product['price_after_discount'],
                'sale' => $product['sale'],
                'stock' => $product['stock'],
                'sku' => $product['sku'],
                'images' => $images,
                'sizes' => $sizeNames, // Added sizes
                'colors' => $colorDetails, // Added colors with details
                'stitched_price' => $product['stitched_price'],
                'setsuite_price' => $product['setsuite_price'],
                'setsuite_discount' => $product['setsuite_discount'],
                'setsuite_quantity' => $product['setsuite_quantity']
            ];
        }
        
        return null;
    }

    if (isset($_GET['product_id'])) {
        $productId = $_GET['product_id'];
        $productDetails = getProductDetails($productId);

        header('Content-Type: application/json');
    header('ngrok-skip-browser-warning: true');
        echo json_encode($productDetails);
    }
?>