<?php
    $productQuery = selectQuery("SELECT * FROM products WHERE styled_by = 'Kapraa' AND is_active != 0 ORDER BY id desc limit 4");
    if ($productQuery['status'] == true) {
        foreach ($productQuery['data'] as $sr => $data) {
            
            $productID          = $data['id'];
            $serial_no          = $data['serial_no'];
            $sku                = $data['sku'];
            $name               = $data['name'];
            $category           = $data['category'];
            $sub_category       = $data['sub_category'];
            $product_type       = $data['product_type'];
            $slug               = $data['slug'];
            $description        = $data['description'];
            $brand              = $data['brand'];
            $size               = $data['size'];
            $color              = $data['color'];
            $unit               = $data['unit'];
            $order_qty          = $data['order_qty'];
            $price              = $data['price'];
            $dis_amount         = $data['dis_amount'];
            $price_after_discount = $data['price_after_discount'];
            $shipp_cost         = $data['shipp_cost'];
            $link               = $data['link'];
            $seo_title          = $data['seo_title'];
            $seo_des            = $data['seo_des'];
            $stock              = $data['stock'];
            $sale               = $data['sale'];
            $imageString        = $data['image'];

            // Convert images into array
            $images = explode(",", $imageString);
            // Get the first image
            $firstImage = !empty($images) ? trim($images[0]) : '';
            
            // Calculate discount percentage if discount exists
            $hasDiscount = ($dis_amount > 0 && $price_after_discount < $price);
            $discountPercentage = 0;
            if ($hasDiscount) {
                $discountPercentage = round(($dis_amount / $price) * 100);
            }
            echo'
            <a href="' . $url_domain . '/product/' . $slug . '" class="col-md-3">
                <div class="card border-0 border-radius text-center" style="background-color:#FFF4F9">
                    <img src="admin/images/product/'.$firstImage.'" alt="'.$name.'" class="border-radius">
                    <h5 class="mt-3 mb-0">'.$name.'</h5>
                    <h5 class="original-price mt-2">Rs. '.number_format($price, 2).'</h5>
                    <h5 class="discounted-price">Rs. '.number_format($price_after_discount, 2).'</h5>
                </div>
            </a>';
        }
    }
?>