<?php include 'include/head.php'; ?>

<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">Forgot Password</h1></div>
    </div>
</div>
<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain;?>/index" title="Back to the home page">Home</a>
        <span aria-hidden="true">|</span>
        <span class="title-bold" id="breadcrumb-label">Forgot Password</span>
    </div>
</div>

<div class="container" style="padding-top: 60px; padding-bottom: 60px;">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-12 col-md-8 col-lg-6 box">

            <!-- STEP 1: Enter Email -->
            <div id="step1">
                <h3>Reset Your Password</h3>
                <p>Enter your registered email address. We will send you a 6-digit reset code.</p>

                <div id="msg1"></div>

                <div class="customer-form">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" id="fp-email" placeholder="Enter your registered email" autofocus />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button class="btn mb-3" onclick="sendOTP()" id="btn1">SEND RESET CODE</button>
                            <a href="<?php echo $url_domain; ?>/login" style="display:block; font-size:13px; color:#1a1a2e; margin-top:4px;">← Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Enter OTP + New Password -->
            <div id="step2" style="display:none;">
                <h3>Enter Reset Code</h3>
                <p>A 6-digit code has been sent to your email. Enter it below along with your new password.</p>

                <div id="msg2"></div>

                <div class="customer-form">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>6-Digit Reset Code <span class="required">*</span></label>
                                <input type="text" id="fp-otp" placeholder="Enter code" maxlength="6"
                                    style="letter-spacing:8px; font-size:20px; text-align:center; font-weight:700;" />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>New Password <span class="required">*</span></label>
                                <input type="password" id="fp-newpw" placeholder="Minimum 6 characters" />
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Confirm New Password <span class="required">*</span></label>
                                <input type="password" id="fp-confirmpw" placeholder="Repeat new password" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button class="btn mb-3" onclick="resetPassword()" id="btn2">RESET PASSWORD</button>
                            <a href="#" onclick="goBack(); return false;" style="display:block; font-size:13px; color:#1a1a2e; margin-top:4px;">← Use different email</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Success -->
            <div id="step3" style="display:none; text-align:center; padding: 40px 0;">
                <div style="width:70px;height:70px;background:#d4edda;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <i class="fa fa-check" style="color:#155724;font-size:30px;"></i>
                </div>
                <h3>Password Reset Successfully!</h3>
                <p>Your password has been changed. You can now login with your new password.</p>
                <a href="<?php echo $url_domain; ?>/login" class="btn mt-3">GO TO LOGIN</a>
            </div>

        </div>
    </div>
</div>

<script>
var userEmail = '';

function showMsg(id, type, msg) {
    document.getElementById(id).innerHTML =
        '<div class="alert alert-' + type + '" style="margin-bottom:16px;"><p>' + msg + '</p></div>';
}
function clearMsg(id) { document.getElementById(id).innerHTML = ''; }

function sendOTP() {
    var email = document.getElementById('fp-email').value.trim();
    if (!email) { showMsg('msg1', 'danger', 'Please enter your email address.'); return; }

    var btn = document.getElementById('btn1');
    btn.value = 'Sending...';
    btn.disabled = true;
    clearMsg('msg1');

    var form = new FormData();
    form.append('action', 'forgot_password');
    form.append('email', email);

    fetch('<?php echo $url_domain; ?>/api/auth.php', { method: 'POST', body: form })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.textContent = 'SEND RESET CODE';
        btn.disabled = false;

        if (data.success) {
            userEmail = email;
            if (data.email_sent) {
                // OTP sent to inbox — move to step 2 directly
                showMsg('msg1', 'success', 'Reset code sent! Please check your email inbox.');
            } else {
                // Email failed — show OTP on screen as fallback
                showMsg('msg1', 'success', 'Reset code: <strong>' + data.otp + '</strong> (email sending failed, use this code)');
            }
            setTimeout(function() {
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                document.getElementById('breadcrumb-label').textContent = 'Reset Password';
            }, 1800);
        } else {
            // Not registered or other error
            if (data.registered === false) {
                showMsg('msg1', 'danger', 'This email is not registered. Please <a href="<?php echo $url_domain; ?>/register">create an account</a> first.');
            } else {
                showMsg('msg1', 'danger', data.message || 'Something went wrong. Please try again.');
            }
        }
    })
    .catch(function() {
        btn.textContent = 'SEND RESET CODE';
        btn.disabled = false;
        showMsg('msg1', 'danger', 'Connection error. Please check your internet and try again.');
    });
}

function resetPassword() {
    var otp       = document.getElementById('fp-otp').value.trim();
    var newpw     = document.getElementById('fp-newpw').value;
    var confirmpw = document.getElementById('fp-confirmpw').value;
    clearMsg('msg2');

    if (!otp || otp.length < 6) { showMsg('msg2', 'danger', 'Please enter the 6-digit reset code.'); return; }
    if (!newpw || newpw.length < 6) { showMsg('msg2', 'danger', 'Password must be at least 6 characters.'); return; }
    if (newpw !== confirmpw) { showMsg('msg2', 'danger', 'Passwords do not match.'); return; }

    var btn = document.getElementById('btn2');
    btn.textContent = 'Resetting...';
    btn.disabled = true;

    var form = new FormData();
    form.append('action',   'reset_password');
    form.append('email',    userEmail);
    form.append('otp',      otp);
    form.append('password', newpw);

    fetch('<?php echo $url_domain; ?>/api/auth.php', { method: 'POST', body: form })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.textContent = 'RESET PASSWORD';
        btn.disabled = false;
        if (data.success) {
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'block';
            document.getElementById('breadcrumb-label').textContent = 'Done';
        } else {
            showMsg('msg2', 'danger', data.message || 'Reset failed. The code may be expired. Please try again.');
        }
    })
    .catch(function() {
        btn.textContent = 'RESET PASSWORD';
        btn.disabled = false;
        showMsg('msg2', 'danger', 'Connection error. Please try again.');
    });
}

function goBack() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    document.getElementById('breadcrumb-label').textContent = 'Forgot Password';
    clearMsg('msg2');
}
</script>

<?php include 'include/footer.php'; ?>
