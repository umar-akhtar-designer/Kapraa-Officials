<?php
  include 'include/head.php';
  include 'include/navbar.php';
?>
<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">About Us</h1></div>
    </div>
</div>
<!-- End Page Title -->

<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center mb-0">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain;?>/index" title="Back to the home page">Home</a><span aria-hidden="true">|</span><span class="title-bold">About Us</span>
    </div>
</div>
<!-- End Breadcrumbs -->

<!-- Main Content -->
<div class="ab-full-section-bg mb-5">
    <div class="container">
        <div class="col-12 col-md-12 col-lg-7 col-xl-7 font15">
            <h2>ABOUT US</h2>
            <p>
                Kapraa is a trusted and well-known clothing store in Faisalabad with more than 10 years of experience
                in delivering original, high-quality fashion for men, women, and kids. Since our beginning, we have
                focused on providing genuine branded apparel, premium fabrics, and stylish seasonal collections at
                both retail and wholesale prices. Over the years, we have earned the confidence of thousands of
                customers by offering authentic products, exceptional service, and a shopping experience built on trust.
                From everyday essentials to premium outfits, Kapraa continues to serve families across Faisalabad
                with a commitment to quality and originality, ensuring every customer finds something that matches
                their taste, comfort, and budget.
            </p>
            <p>
                <b>Our store is open from 10:00 AM to 10:00 PM, seven days a week </b> and we are always ready to assist
                you. Whether you are looking for casual wear, formal wear, or wholesale stock, Kapraa stands as a dependable fashion destination where quality meets affordability.
            </p>
        </div>
    </div>
</div>
<div class="container mb-5">
    <div class="row">
        <div class="col-12 col-md-12 col-lg-6 col-xl-6 d-flex align-items-center flex-wrap">
            <img class="blur-up lazyload w-100 mb-3 mb-sm-3 mb-md-3 mb-lg-0 mb-xl-0" data-src="assets/images/about/about-us-image5.jpg" src="assets/images/about/about-us-image5.jpg" alt="" />
        </div>
        <div class="col-12 col-md-12 col-lg-6 col-xl-6 font15 d-flex align-items-center flex-wrap">
            <div>
                <p class="text-uppercase text-grey mb-0">Who we are</p>
                <h5 class="lh-1 ls-m mt-2">Has long and glorious history</h5>
            </div>
            <p class="mt-3">
                Kapraa has built a long and respected presence in the fashion retail market. With deep roots in
                Lahore’s clothing industry, we have grown steadily by staying committed to originality, quality, and
                customer satisfaction. Our journey started with a small vision—to bring genuine and affordable branded clothing to families and retailers. Today, we proudly offer a wide collection of men’s, women’s, and kids’wear, sourced carefully to maintain premium standards. Our history reflects dedication, consistency,
                and a passion for providing reliable fashion choices. As we continue to expand, we remain focused on
                delivering trusted brands, honest pricing, and a shopping experience shaped by over a decade of hard work and customer loyalty.
            </p>
            <div class="row pl-xl-6 pr-xl-8 mb-6">
                <div class="counter text-secondary text-center col-4 col-sm-4 col-md-4 col-lg-4">
                    <span class="count-to complete">5+</span>
                    <h5 class="count-title">Business Year</h5>
                </div>
                <div class="counter text-secondary text-center col-4 col-sm-4 col-md-4 col-lg-4">
                    <span class="count-to complete">50+</span>
                    <h5 class="count-title">Design Brands</h5>
                </div>
                <div class="counter text-secondary text-center col-4 col-sm-4 col-md-4 col-lg-4">
                    <span class="count-to complete">50+</span>
                    <h5 class="count-title">Team Members</h5>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container mb-5 mt-5 d-none">
    <div class="section-header text-center">
        <h2 class="h2">Meet our support team</h2>
    </div>
    <div class="row team-items team-style2">
        <?php
           include 'include/team_card.php';
        ?>
    </div>
</div>
<!-- End Main Content -->
<?php
  include 'include/footer.php';
?>