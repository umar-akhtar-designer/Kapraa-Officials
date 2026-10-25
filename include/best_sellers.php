<?php
    $brandQuery = selectQuery("SELECT * FROM brand WHERE brand_main ='Best Sellers' order by id desc");
    if($brandQuery['status'] == true){
        foreach($brandQuery['data'] as $sr => $data){
            $brandId = $data['id'];
            $name = $data['name'];
            $slug = $data['slug'];
            $image = $data['image'];            if (empty($image) || !file_exists(__DIR__ . '/../admin/images/brand/' . $image)) continue;
                                            
        echo '            
            <a href="' . $url_domain . '/products/brand/' . $slug . '" class="col-md-2 col-6 text-center">
                <div class="card border-0 position-relative">
                    <img src="admin/images/brand/'.$image.'" alt="'.$name.'" class="rounded w-100 h-100">
                    <div class="position-absolute bottom-0 start-0 w-100 text-center  bg-dark rounded-bottom">
                        <p class="text-white mb-0">'.$name.'</p>
                        <p style="font-size:6px;color:white" class="mb-1">SHOP NOW</p>
                    </div>
                </div>
            </a>
        ';
        }                
    }
?>