<?php
  include 'include/head.php';
  include 'include/navbar.php';
?>
<style>
    .accordion-button:focus{
        border-color: white !important;
        box-shadow : none !important
    }
</style>
<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">FAQ's</h1></div>
    </div>
</div>
<!-- End Page Title -->
<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain;?>/index" title="Back to the home page">Home</a><span aria-hidden="true">|</span><span class="title-bold">FAQ's</span>
    </div>
</div>
<!-- End Breadcrumbs -->

<div class="container">
    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">What shipping methods are available?</button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Kapraa offers reliable nationwide delivery through trusted courier partners. All orders are shipped using standard delivery service and typically arrive within 2–4 working days, depending on your location. We ensure secure packaging so your order reaches you safely and on time.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">How do I place an Order?</button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Placing an order at Kapraa is quick and easy. Simply browse our collection, select your desired product, choose the size and quantity, and add it to your cart. Proceed to checkout, enter your delivery details, and confirm your order. Once placed, you’ll receive an order confirmation, and we’ll take care of the rest.</p>
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-3 mb-3 mb-sm-0">
                                    <img class="blur-up lazyload" data-src="assets/images/about/about-us-image2.jpg" src="assets/images/about/about-us-image2.jpg" alt="About Us" title="" />
                                </div>
                                <div class="col-12 col-sm-6 col-md-9">
                                    <h2>About Kapraa</h2>
                                    <p class="mb-2">At Kapraa, our mission is to deliver premium-quality clothing that combines style, comfort, and durability. We focus on offering well-crafted designs made from high-quality fabrics, ensuring every outfit meets our strict quality standards.</p>
                                    <p class="mb-2">We aim to provide a smooth and user-friendly online shopping experience, making it easy for customers to find, order, and receive their favorite styles with confidence.</p>
                                    <p>Our dedicated support team is always ready to assist you at every step—from product selection to order delivery—ensuring a reliable and satisfying shopping journey with Kapraa.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">What Payment Methods Are Accepted?</button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Kapraa accepts payments through bank transfer and Cash on Delivery (COD) for your convenience. You can choose the payment method that suits you best at checkout for a smooth and secure shopping experience.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">Do You Ship Internationally?</button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Yes, Kapraa offers international shipping. Delivery time and shipping charges may vary depending on the destination country. For more details or assistance with international orders, please contact our support team.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">What Payment Methods Are Accepted? </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Kapraa accepts Bank Transfer and Cash on Delivery (COD) for your convenience. Simply choose your preferred payment option at checkout to enjoy a smooth and secure shopping experience.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingSix">
                        <button class="accordion-button collapsed text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">Is Buying On-Line Safe?</button>
                    </h2>
                    <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>Yes, shopping at Kapraa is completely safe. We ensure that all online transactions are secure, and your personal information is protected. Our website uses trusted payment methods and reliable processes to provide a safe and worry-free shopping experience.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingSeven">
                        <button class="accordion-button collapsed text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">Do I have to pay anything to return my items? </button>
                    </h2>
                    <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p>If you are returning an item due to damage, defect, or wrong delivery, Kapraa covers the return shipping cost. For all other exchanges or returns, the customer is responsible for the shipping charges. Items must be returned in their original condition with tags intact.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingEight">
                        <button class="accordion-button collapsed text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">How do I return an item? </button>
                    </h2>
                    <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <p> To return an item from Kapraa, please follow these steps:Contact our customer support team with your order details and reason for return.
                                Pack the item securely in its original condition with tags intact.
                                Ship the item back to us using a reliable courier service.
                                Once we receive and inspect the returned item, we will process your exchange or refund as per our policy.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
  include 'include/footer.php';
?>