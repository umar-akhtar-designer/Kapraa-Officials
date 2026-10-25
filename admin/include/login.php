<?php
    session_start();
    if(isset($_POST['LOGIN'])){
        $username		= cleanStr($_POST['username']);
        $password		= cleanStr(md5($_POST['password']));
        $WebsiteLogin = selectQuery("SELECT * FROM web_settings where username = '$username' and password = '$password'");
        if($WebsiteLogin['status'] == true){
            $_SESSION['WEBSITE-LOGIN'] = $WebsiteLogin['data'][0]['id'];
            unset($_SESSION['otp_verified']);
            header("Location: dashboard.php");
            exit;
        } else {
            $error[] = "Your username and password are not correct";
        }
    }
    
?>
<!-- Content -->

<div class="browse-job login-style3">
    <!-- Coming Soon -->
    <div class="bg-img-fix overflow-hidden" style="background-image:url(images/background/bg6.jpg); height: 100vh;">
        <div class="row gx-0">
            <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12 vh-100 bg-white ">
                <div id="mCSB_1" class="mCustomScrollBox mCS-light mCSB_vertical mCSB_inside" style="max-height: 653px;" tabindex="0">
                    <div id="mCSB_1_container" class="mCSB_container" style="position:relative; top:0; left:0;" dir="ltr">
                        <div class="login-form style-2">
                            <div class="card-body">
                                <div class="logo-header d-flex align-items-center">
                                   <img src="images/Kapraa-logo.png" alt="" class="mCS_img_loaded" height="70px">
                                   <h2 class="ms-3 mb-0 form-title">KAPRAA</h2>
                                </div>
                                <nav>
                                    <div class="nav nav-tabs border-bottom-0" id="nav-tab" role="tablist">   
                                        <div class="tab-content w-100" id="nav-tabContent">
                                            <div class="tab-pane fade show active" id="nav-personal" role="tabpanel" aria-labelledby="nav-personal-tab">
                                            <form class="dez-form pb-3" method="post">
                                                    <h3 class="form-title m-t0">Personal Information</h3>
                                                    <div class="dez-separator-outer m-b5">
                                                        <div class="dez-separator bg-primary style-liner"></div>
                                                    </div>
                                                    <p>Enter your User Name address and your password.</p>

                                                <?php if (!empty($error)) {
                                                    echo "<div class='alert alert-danger'>" . implode("<br>", $error) . "</div>";
                                                } ?>

                                                <div class="form-group mb-3">
                                                    <input name="username" required class="form-control" placeholder="User Name" type="text">
                                                </div>
                                                <div class="form-group position-relative mb-3">
                                                    <input id="passwordField" name="password" class="form-control" placeholder="Type Password" type="password">
                                                    <button type="button" id="togglePassword" class="btn btn-light position-absolute bg-primary" style="right: 2px; top: 50%; transform: translateY(-50%);color: #fff;">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <div class="form-group text-left mb-5">
                                                    <div class="text-end mt-3">
                                                        <button class="btn btn-primary dz-xs-flex me-3 w-100" name="LOGIN" type="submit">Login</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </nav>
                        </div>      
                    </div>
                </div>
                <div id="mCSB_1_scrollbar_vertical" class="mCSB_scrollTools mCSB_1_scrollbar mCS-light mCSB_scrollTools_vertical" style="display: block;">
                    <div class="mCSB_draggerContainer">
                    <div id="mCSB_1_dragger_vertical" class="mCSB_dragger" style="position: absolute; min-height: 0px; display: block; height: 652px; max-height: 643px; top: 0px;">
                    <div class="mCSB_dragger_bar" style="line-height: 0px;"></div><div class="mCSB_draggerRail"></div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Full Blog Page Contant -->
<script>
	document.getElementById('togglePassword').addEventListener('click', function() {
		const passwordField = document.getElementById('passwordField');
		const passwordIcon = this.querySelector('i');

		// Toggle the password visibility
		if (passwordField.type === 'password') {
			passwordField.type = 'text';
			passwordIcon.classList.remove('fa-eye');
			passwordIcon.classList.add('fa-eye-slash');
		} else {
			passwordField.type = 'password';
			passwordIcon.classList.remove('fa-eye-slash');
			passwordIcon.classList.add('fa-eye');
		}
	});
</script>