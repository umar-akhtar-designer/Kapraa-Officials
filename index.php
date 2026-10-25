<?php
  include 'include/head.php';
  include 'include/navbar.php';
?>
<?php
   include 'include/main_banner.php';
?>
<!-- End Home Banner slider -->
<section class="pt-4 pb-2">
    <div class="container-fluid">
        <h2 class="font-weight-bold text-center mb-3">New Arrivals</h2>
        <?php include 'include/new_arrivals.php'; ?>
    </div>
</section>
<?php
   include 'include/home_banner_1.php';
?>
<section>
    <!--<div class="container-fluid">-->
    <!--    <h2 class="text-center mb-2">Shop By Category</h2>-->
        <!-- category Slider -->
    <!--    <div class="row mt-4">-->
    <!--        <?php-->
    <!--            include 'include/shop_by_category.php';-->
    <!--        ?>-->
    <!--    </div>-->
        <!-- End category Slider -->
    <!--</div>-->
</section>
<section class="mt-5 d-none">
    <div class="container-fluid">
        <h2 class="text-center mb-3">Best Sellers</h2>
        <div class="row mt-4">
            <?php
                include 'include/best_sellers.php';
            ?>
        </div>
    </div>
</section>
<section class="mt-5">
    <div class="container-fluid">
        <h2 class="text-center mb-3">Festive Wear</h2>
        <div class="instagram-slider mt-4">
            <?php
                include 'include/festive_wear.php';
            ?>
        </div>
    </div>
</section>
<?php
   include 'include/home_banner_2.php';
?>
<section class="mt-4">
    <div class="container-fluid">
        <h2 class="text-center mb-3">Unstitched Dresses</h2>
        <div class="instagram-slider mt-4">
            <?php
                include 'include/unstitched_dresses.php';
            ?>
        </div>
    </div>
</section>
<!--<section class="mt-4">-->
<!--    <div class="container-fluid">-->
<!--        <h2 class="text-center mb-3">Men's Wear</h2>-->
<!--        <div class="row mt-4">-->
<!--            <?php-->
<!--                include 'include/mens_wear.php';-->
<!--            ?>-->
<!--        </div>-->
<!--    </div>-->
<!--</section>-->
<!--<section class="mt-4">-->
<!--    <div class="container-fluid">-->
<!--        <h2 class="text-center mb-3">Kid's Wear</h2>-->
<!--        <div class="row mt-4">-->
<!--            <?php-->
<!--                include 'include/kids_wear.php';-->
<!--            ?>-->
<!--        </div>-->
<!--    </div>-->
<!--</section>-->
<?php
   include 'include/shop_banner.php';
?>
<section class="mt-4 d-none">
    <div class="container-fluid">
        <h2 class="text-center mb-3">Accessories & Footwear</h2>
        <div class="instagram-slider mt-4">
            <?php
                include 'include/accessories_footwear.php';
            ?>
        </div>
    </div>
</section>
<section class="collection-box tle-bold section mt-5 d-none" style="background-color:#FFF4F9">
    <div class="container-fluid">
       <h2 class="text-center mb-0">STYLED BY YOU</h2>
       <p class="text-center mt-0">#Kapraa</p>
       <div class="container">
         <div class="row">
            <?php
                include 'include/styled_by_you.php';
            ?>
         </div>
       </div>
    </div>
</section>
<?php
   include 'include/product_home.php';
?>
<?php
  include 'include/footer.php';
?>
