<?php
  include 'include/head.php';
  include 'include/navbar.php';

  if (isset($_POST['save'])) {
       
        $name    = cleanStr($_POST['name']);
        $email   = cleanStr($_POST['email']);
        $phone   = cleanStr($_POST['phone']);
        $subject = cleanStr($_POST['subject']);
        $message = cleanStr($_POST['message']);

        if (empty($error)) {
            $insert = insert_and_update_data('insert', 'contact', array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message
            ));

            if ($insert['status'] == true) {
                $success_message = "Message sent successfully!";
            }
        }
    }
?>
<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">Contact Us</h1></div>
    </div>
</div>
<!-- End Page Title -->
<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center mb-0">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain;?>/index" title="Back to the home page">Home</a><span aria-hidden="true">|</span><span class="title-bold">Contact Us</span>
    </div>
</div>
<!-- End Breadcrumbs -->

<div class="container-fluid px-0">
    <div class="map-section map mb-4">
       <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3405.0007361282583!2d73.1026404!3d31.414105799999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39226904d0739b3d%3A0xbeb0f708b77f2cbc!2sFuture%20Career%20Hub!5e0!3m2!1sen!2s!4v1783318069799!5m2!1sen!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
</div>

<div class="container">
    <div class="row m-0">
        <div class="col-12 col-sm-12 col-md-12 col-lg-6 p-0 mb-lg-0 mb-sm-4 mb-md-4 mb-4">
            <h2>DROP US A LINE</h2>
            <p>Lorem Ipsum é um texto modelo da indústria tipográfica e de impressão. O Lorem Ipsum tem vindo a ser o texto padrão usado por estas indústrias desde o ano de 1500 </p>
            
            <!-- Success Message Display -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Contact Form -->
            <div class="formFeilds contact-form form-vertical">
                <form  method="post">	
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <input type="text" name="name" class="form-control" placeholder="Name" required />
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">                               
                            <div class="form-group">
                                <input type="email" name="email" class="form-control" placeholder="Email" required />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <input class="form-control" type="tel" id="ContactFormPhone" name="phone"  placeholder="Phone Number" required />
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <input type="text"  name="subject" class="form-control" placeholder="Subject" required />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                            <div class="form-group">
                                <textarea  name="message" class="form-control" rows="4" placeholder="Your Message..." required></textarea>
                            </div>
                        </div>  
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                            <div class="form-group mailsendbtn mb-0">	
                                <input class="btn btn-primary" type="submit" name="save" value="Send Message" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- End Contact Form -->
        </div>

        <div class="col-12 col-sm-12 col-md-12 col-lg-6 d-flex justify-content-center align-items-center flex-wrap px-2 px-sm-2 px-md-2 px-lg-5 pt-2 pb-2 mb-md-5 mb-lg-0 mb-sm-5 mb-5">
            <div class="contact-details">
                <ul class="addressFooter">
                    <li><i class="icon an an-map-marker"></i><p>Faisalabad, Pakistan</p></li>
                    <li class="phone"><i class="icon an an-phone"></i><p>+92 320 9815213</p></li>
                    <li class="email"><i class="icon an an-envelope"></i><p>kapraaofficial@gmail.com</p></li>
                </ul>
                <hr />
                <div class="open-hours">
                    <strong class="pb-1 d-inline-block">Opening Hours</strong><br>
                    Mon - Sun : 10:00 AM - 10:00 PM<br>
                </div>
                <hr />
                <ul class="list--inline site-footer__social-icons social-icons">
                    <li><a class="social-icons__link d-inline-block" href="https://www.facebook.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Facebook"><i class="icon an an-facebook"></i></a></li>
                    <li><a class="social-icons__link d-inline-block" href="https://www.instagram.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Instagram"><i class="icon an an-instagram"></i> <span class="icon__fallback-text">Instagram</span></a></li>
                    <li>
                        <a href="https://www.tiktok.com/@kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Tik Tok">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                            </svg> 
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
  include 'include/footer.php';
?>