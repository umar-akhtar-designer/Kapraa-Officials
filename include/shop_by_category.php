<style>
    .sale-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 1;
        transition: opacity 0.5s ease;
        z-index: 10;
    }

    .sale-overlay.hidden {
        opacity: 0;
    }

    .sale-content {
        text-align: center;
        color: white;
        font-weight: bold;
        text-transform: uppercase;
    }

    .sale-upto {
        font-size: 18px;
    }

    .sale-value {
        font-size: 42px;
        font-weight: 900;
        line-height: 1;
    }
</style>
<?php
    $subcategoryQuery = selectQuery("SELECT * FROM subcategory WHERE name = 'Unstitched Fabric Suits' 
    OR name = 'Winter Collections' 
    OR name = 'Summer Collection';");

    if($subcategoryQuery['status'] == true){
        foreach($subcategoryQuery['data'] as $sr => $data){
            $categoryId = $data['id'];
            $name = $data['name'];
            $slug = $data['slug'];
            $sale = $data['sale'];
            $imageJson = $data['image'];

            if (empty($imageJson) || !file_exists(__DIR__ . '/../admin/images/subcategory/' . $imageJson)) continue;

            // Check if sale exists and is not empty
            $hasSale = !empty($sale);
            
            echo '
                <a href="' . $url_domain . '/products/subcategory/' . $slug . '" class="col-md-4 mb-4">
                    <div class="card border-0 category-card h-100 position-relative">
                        <div class="card-img-container position-relative overflow-hidden">
                            <img src="admin/images/subcategory/'.$imageJson.'" class="card-img-top rounded" alt="'.$name.'">';
                            
            // Add sale overlay if sale value exists
            if ($hasSale) {
                echo '
                        <div class="sale-overlay rounded" data-sale-id="'.$categoryId.'">
                            <div class="sale-content">
                                <div class="sale-upto">UPTO</div>
                                <div class="sale-value">' . htmlspecialchars($sale) . '%</div>
                                <div class="sale-upto">OFF</div>
                            </div>
                        </div>';
            }
                            
            echo '
                        </div>
                    </div>
                </a>
            ';
        }                
    }
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set the time in milliseconds
        const showDuration = 1500; // How long the overlay shows (3 seconds)
        const hideDuration = 1500; // How long the overlay stays hidden (2 seconds)
        
        // Find all sale overlays
        const saleOverlays = document.querySelectorAll('.sale-overlay');
        
        // Create a show/hide cycle for each overlay
        saleOverlays.forEach(function(overlay) {
            // Function to handle the show/hide cycle
            function cycleOverlay() {
                // Show the overlay
                overlay.classList.remove('hidden');
                
                // Set a timeout to hide the overlay after showDuration
                setTimeout(function() {
                    overlay.classList.add('hidden');
                }, showDuration);
            }
            
            // Start the cycle immediately
            cycleOverlay();
            
            // Set an interval to repeat the cycle
            setInterval(cycleOverlay, showDuration + hideDuration);
        });
    });
</script>