
<?php
session_start();

$otpError = '';

// ============================================================
// HANDLE OTP VERIFICATION
// ============================================================

if (isset($_POST['verify_otp'])) {

    $entered = trim($_POST['otp_code'] ?? '');
    $stored  = $_SESSION['otp_code'] ?? '';
    $expires = $_SESSION['otp_expires'] ?? 0;

    // Check expiry / missing OTP
    if ($stored === '' || time() > $expires) {

        $otpError = 'Code has expired. Please request a new one.';

    }

    // Check entered OTP
    elseif ($entered !== $stored) {

        $otpError = 'Incorrect code. Please check and try again.';

    }

    // Correct OTP
    else {

        $_SESSION['otp_verified'] = true;

        unset(
            $_SESSION['otp_code'],
            $_SESSION['otp_expires'],
            $_SESSION['otp_method']
        );

        header('Location: dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Kapraa — Verification</title>

    <link
        rel="shortcut icon"
        type="image/png"
        href="images/favicon.ico"
    >

    <link
        href="vendor/bootstrap-select/dist/css/bootstrap-select.min.css"
        rel="stylesheet"
    >

    <link
        href="css/style.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

    /* ========================================================
       METHOD BUTTONS
       ======================================================== */

    .method-btns {
        display:flex;
        gap:12px;
        margin-bottom:18px;
    }

    .method-btn {
        flex:1;
        border:2px solid #e0e0e0;
        border-radius:10px;
        background:#fff;
        padding:14px 8px;
        cursor:pointer;
        text-align:center;
        font-size:13px;
        font-weight:700;
        color:#666;
        transition:all .2s;
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:5px;
    }

    .method-btn i {
        font-size:22px;
    }

    .method-btn small {
        font-size:10px;
        font-weight:400;
        opacity:.75;
    }

    .method-btn:hover {
        border-color:#6610f2;
        color:#6610f2;
        background:#f5f0ff;
    }

    .method-btn.active {
        border-color:#6610f2;
        background:#6610f2;
        color:#fff;
    }

    .method-btn.active i {
        color:#f9ca24;
    }


    /* ========================================================
       OTP INPUTS
       ======================================================== */

    .otp-inputs {
        display:flex;
        gap:8px;
        justify-content:center;
        margin:16px 0;
    }

    .otp-digit {
        width:44px;
        height:52px;
        border:2px solid #e0e0e0;
        border-radius:8px;
        text-align:center;
        font-size:22px;
        font-weight:800;
        color:#1a1a2e;
        outline:none;
        transition:border-color .2s;
        background:#fff;
    }

    .otp-digit:focus {
        border-color:#6610f2;
        box-shadow:0 0 0 3px rgba(102,16,242,.1);
    }

    .otp-digit.filled {
        border-color:#6610f2;
        background:#f5f0ff;
    }


    /* ========================================================
       STATUS
       ======================================================== */

    .status-line {
        min-height:20px;
        font-size:12.5px;
        text-align:center;
        margin:6px 0;
    }

    .status-line.ok {
        color:#16a34a;
    }

    .status-line.err {
        color:#dc2626;
    }

    .status-line.spin {
        color:#6610f2;
    }


    /* ========================================================
       OTP SECTION
       ======================================================== */

    .otp-code-section {
        display:none;
        animation:fadeUp .3s ease;
    }

    .otp-code-section.show {
        display:block;
    }


    /* ========================================================
       FALLBACK OTP BOX
       ======================================================== */

    .fallback-code-box {
        display:none;
        margin:14px 0 18px;
        padding:15px;
        background:#fff3cd;
        border:1px solid #f9ca24;
        border-radius:10px;
        text-align:center;
    }

    .fallback-code-box.show {
        display:block;
        animation:fadeUp .3s ease;
    }

    .fallback-code-label {
        font-size:11px;
        font-weight:700;
        color:#856404;
        margin-bottom:6px;
    }

    .fallback-code {
        font-size:29px;
        font-weight:900;
        letter-spacing:7px;
        color:#1a1a2e;
        line-height:1.2;
    }

    .fallback-code-note {
        font-size:10px;
        color:#856404;
        margin-top:6px;
    }


    /* ========================================================
       ANIMATION
       ======================================================== */

    @keyframes fadeUp {

        from {
            opacity:0;
            transform:translateY(8px);
        }

        to {
            opacity:1;
            transform:translateY(0);
        }

    }


    /* ========================================================
       DIVIDER
       ======================================================== */

    .divider-line {
        display:flex;
        align-items:center;
        gap:10px;
        margin:14px 0;
        color:#bbb;
        font-size:11px;
    }

    .divider-line::before,
    .divider-line::after {
        content:'';
        flex:1;
        height:1px;
        background:#e8e8e8;
    }


    /* ========================================================
       LINK BUTTON
       ======================================================== */

    .link-btn {
        background:none;
        border:none;
        color:#6610f2;
        font-weight:700;
        cursor:pointer;
        font-size:12.5px;
        padding:0;
    }

    .link-btn:hover {
        text-decoration:underline;
    }


    /* ========================================================
       STEP BADGE
       ======================================================== */

    .step-badge {
        background:#6610f2;
        color:#fff;
        border-radius:50%;
        width:20px;
        height:20px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:11px;
        margin-right:6px;
    }


    /* ========================================================
       MOBILE
       ======================================================== */

    @media (max-width:480px) {

        .otp-inputs {
            gap:5px;
        }

        .otp-digit {
            width:40px;
            height:50px;
        }

        .fallback-code {
            font-size:25px;
            letter-spacing:5px;
        }

    }

    </style>

</head>


<body>


<div id="preloader">

    <div class="lds-ripple">
        <div></div>
        <div></div>
    </div>

</div>


<div id="main-wrapper" class="show">


<div class="browse-job login-style3">

<div
    class="bg-img-fix overflow-hidden"
    style="
        background-image:url(images/background/bg6.jpg);
        height:100vh;
    "
>

<div class="row gx-0">


<!-- ========================================================
     LEFT WHITE CARD
     ======================================================== -->

<div
    class="col-xl-4 col-lg-5 col-md-6 col-sm-12 vh-100 bg-white"
    style="overflow-y:auto;"
>

<div class="login-form style-2">

<div class="card-body">


<!-- LOGO -->

<div class="logo-header d-flex align-items-center mb-3">

    <img
        src="images/Kapraa-logo.png"
        alt="Kapraa"
        style="
            height:60px;
            object-fit:contain;
        "
        onerror="this.style.display='none'"
    >

    <div class="ms-3">

        <h2
            class="mb-0 form-title"
            style="font-size:20px;"
        >
            KAPRAA
        </h2>

        <small
            class="text-muted"
            style="
                font-size:11px;
                letter-spacing:.5px;
            "
        >
            ADMIN PANEL
        </small>

    </div>

</div>


<div class="dez-separator-outer m-b5">

    <div class="dez-separator bg-primary style-liner"></div>

</div>


<!-- TITLE -->

<h3 class="form-title m-t0">
    Two-Step Verification
</h3>

<p
    class="text-muted"
    style="
        font-size:13px;
        margin-bottom:20px;
    "
>
    Verify your identity to access the admin panel.
</p>


<!-- PHP ERROR -->

<?php if ($otpError): ?>

<div
    class="alert alert-danger py-2 px-3"
    style="
        font-size:13px;
        border-radius:8px;
        margin-bottom:16px;
    "
>

    <i class="fa fa-circle-exclamation me-2"></i>

    <?= htmlspecialchars($otpError, ENT_QUOTES, 'UTF-8') ?>

</div>

<?php endif; ?>


<!-- ========================================================
     STEP 1
     ======================================================== -->

<p
    class="fw-bold mb-2"
    style="
        font-size:12.5px;
        color:#444;
    "
>

    <span class="step-badge">
        1
    </span>

    We'll email your code to

    <strong>
        kapraaofficial@gmail.com
    </strong>

</p>


<button
    type="button"
    class="btn btn-primary dz-xs-flex w-100 mb-2"
    id="sendBtn"
    onclick="sendCode()"
>

    <i class="fa fa-paper-plane me-2"></i>

    Send Verification Code

</button>


<!-- STATUS -->

<div
    class="status-line"
    id="statusLine"
></div>


<!-- ========================================================
     FALLBACK CODE
     ======================================================== -->

<div
    class="fallback-code-box"
    id="fallbackCodeBox"
>

    <div class="fallback-code-label">

        EMAIL COULD NOT BE SENT — USE THIS CODE

    </div>

    <div
        class="fallback-code"
        id="fallbackCode"
    ></div>

    <div class="fallback-code-note">

        This code is valid for 10 minutes.

    </div>

</div>


<!-- ========================================================
     STEP 2
     ======================================================== -->

<div
    class="otp-code-section"
    id="otpSection"
>

    <div class="divider-line">
        Enter the 6-digit code
    </div>


    <p
        class="fw-bold mb-1"
        style="
            font-size:12.5px;
            color:#444;
        "
    >

        <span class="step-badge">
            2
        </span>

        Enter the code you received:

    </p>


    <form
        method="POST"
        id="otpForm"
        onsubmit="return buildCode()"
    >

        <input
            type="hidden"
            name="verify_otp"
            value="1"
        >

        <input
            type="hidden"
            name="otp_code"
            id="otpHidden"
        >


        <!-- SIX DIGIT BOXES -->

        <div class="otp-inputs">

            <input
                type="text"
                class="otp-digit"
                maxlength="1"
                inputmode="numeric"
                autocomplete="one-time-code"
            >

            <input
                type="text"
                class="otp-digit"
                maxlength="1"
                inputmode="numeric"
                autocomplete="off"
            >

            <input
                type="text"
                class="otp-digit"
                maxlength="1"
                inputmode="numeric"
                autocomplete="off"
            >

            <input
                type="text"
                class="otp-digit"
                maxlength="1"
                inputmode="numeric"
                autocomplete="off"
            >

            <input
                type="text"
                class="otp-digit"
                maxlength="1"
                inputmode="numeric"
                autocomplete="off"
            >

            <input
                type="text"
                class="otp-digit"
                maxlength="1"
                inputmode="numeric"
                autocomplete="off"
            >

        </div>


        <!-- VERIFY BUTTON -->

        <button
            type="submit"
            class="btn btn-primary dz-xs-flex w-100 mt-2"
            id="verifyBtn"
            disabled
        >

            <i class="fa fa-check-circle me-2"></i>

            Verify & Enter Admin Panel

        </button>

    </form>


    <!-- RESEND -->

    <div
        class="text-center mt-3"
        style="
            font-size:12.5px;
            color:#888;
        "
    >

        Didn't get it?

        <button
            type="button"
            class="link-btn"
            onclick="sendCode()"
        >
            Resend code
        </button>

    </div>

</div>


<!-- ========================================================
     FOOTER
     ======================================================== -->

<div
    class="mt-4 pt-3"
    style="
        border-top:1px solid #f0f0f0;
        text-align:center;
    "
>

    <small
        class="text-muted"
        style="font-size:11px;"
    >

        <i class="fa fa-lock me-1"></i>

        This session is secured with 2-step verification

    </small>

</div>


</div>
</div>
</div>


<!-- ========================================================
     RIGHT SIDE
     ======================================================== -->

<div
    class="col-xl-8 col-lg-7 col-md-6 d-md-flex d-none align-items-center justify-content-center"
    style="position:relative;"
>

<div
    style="
        text-align:center;
        padding:40px;
        color:#fff;
        position:relative;
        z-index:2;
    "
>

    <div
        style="
            width:90px;
            height:90px;
            background:rgba(249,202,36,.15);
            border:2px solid rgba(249,202,36,.4);
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 24px;
        "
    >

        <i
            class="fa fa-shield-halved"
            style="
                font-size:40px;
                color:#f9ca24;
            "
        ></i>

    </div>


    <h2
        style="
            font-size:28px;
            font-weight:800;
            margin-bottom:12px;
            text-shadow:0 2px 8px rgba(0,0,0,.4);
        "
    >
        Secure Admin Access
    </h2>


    <p
        style="
            font-size:14px;
            opacity:.8;
            max-width:340px;
            line-height:1.7;
            margin:0 auto;
        "
    >
        A verification code will be sent to your registered email
        to confirm your identity before granting access.
    </p>


    <div
        style="
            display:flex;
            gap:20px;
            justify-content:center;
            margin-top:30px;
            flex-wrap:wrap;
        "
    >

        <div
            style="
                background:rgba(255,255,255,.1);
                border-radius:10px;
                padding:14px 20px;
                font-size:12px;
            "
        >

            <i
                class="fa fa-clock me-1"
                style="color:#f9ca24;"
            ></i>

            Code valid

            <strong>
                10 min
            </strong>

        </div>


        <div
            style="
                background:rgba(255,255,255,.1);
                border-radius:10px;
                padding:14px 20px;
                font-size:12px;
            "
        >

            <i
                class="fa fa-rotate me-1"
                style="color:#f9ca24;"
            ></i>

            New session =

            <strong>
                new verify
            </strong>

        </div>

    </div>

</div>

</div>


</div>
</div>
</div>


</div>


<!-- ========================================================
     JAVASCRIPT
     ======================================================== -->

<script src="vendor/global/global.min.js"></script>

<script src="vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

<script src="js/custom.min.js"></script>


<script>

/* ============================================================
   REMOVE PRELOADER
   ============================================================ */

(function(){

    var p = document.getElementById('preloader');

    if (p) {

        p.style.cssText = 'display:none!important';

        try {
            p.remove();
        } catch(e) {}

    }

    var m = document.getElementById('main-wrapper');

    if (m) {
        m.classList.add('show');
    }

})();


/* ============================================================
   SEND OTP
   ============================================================ */

function sendCode() {

    var btn = document.getElementById('sendBtn');

    var status = document.getElementById('statusLine');

    var otpSection = document.getElementById('otpSection');

    var fallbackBox = document.getElementById('fallbackCodeBox');

    var fallbackCode = document.getElementById('fallbackCode');


    /* Disable button while sending */

    btn.disabled = true;

    btn.innerHTML =
        '<i class="fa fa-spinner fa-spin me-2"></i>Sending…';


    setStatus(
        'Sending your verification code…',
        'spin'
    );


    /* Hide previous fallback code */

    fallbackBox.classList.remove('show');

    fallbackCode.textContent = '';


    /* Clear previous OTP boxes */

    clearDigits();


    /* ========================================================
       SEND REQUEST
       ======================================================== */

    fetch('send_otp.php', {

        method:'POST',

        headers:{
            'Content-Type':
                'application/x-www-form-urlencoded; charset=UTF-8'
        },

        body:'method=email',

        credentials:'same-origin'

    })

    .then(function(response){

        if (!response.ok) {
            throw new Error(
                'Server returned HTTP ' + response.status
            );
        }

        return response.text();

    })

    .then(function(text){

        var data;

        try {

            data = JSON.parse(text);

        } catch(e) {

            console.error('Invalid server response:', text);

            throw new Error(
                'Server returned an invalid response.'
            );

        }


        btn.disabled = false;

        btn.innerHTML =
            '<i class="fa fa-paper-plane me-2"></i>Resend Code';


        /* ====================================================
           EMAIL SUCCESS
           ==================================================== */

        if (
            data.success === true &&
            data.email_sent === true
        ) {

            setStatus(
                '<i class="fa fa-check-circle me-1"></i>' +
                'Code sent to kapraaofficial@gmail.com — ' +
                'check your email!',
                'ok'
            );


            otpSection.classList.add('show');


            setTimeout(function(){

                var first =
                    document.querySelector('.otp-digit');

                if (first) {
                    first.focus();
                }

            },150);


            return;
        }


        /* ====================================================
           EMAIL FAILED
           BUT FALLBACK OTP EXISTS
           ==================================================== */

        if (
            data.success === true &&
            data.fallback_code
        ) {

            var code =
                String(data.fallback_code);


            setStatus(
                '<i class="fa fa-triangle-exclamation me-1"></i>' +
                'Email could not be sent. ' +
                'Use the code shown below.',
                'err'
            );


            /* Show fallback code */

            fallbackCode.textContent = code;

            fallbackBox.classList.add('show');


            /* Show OTP section */

            otpSection.classList.add('show');


            /* =================================================
               AUTO-FILL SIX DIGITS
               ================================================= */

            var digits =
                document.querySelectorAll('.otp-digit');


            digits.forEach(function(input,index){

                input.value =
                    code.charAt(index) || '';

                input.classList.toggle(
                    'filled',
                    input.value !== ''
                );

            });


            /* Enable verification */

            document.getElementById(
                'verifyBtn'
            ).disabled = false;


            return;
        }


        /* ====================================================
           NORMAL ERROR
           ==================================================== */

        setStatus(
            '<i class="fa fa-times-circle me-1"></i>' +
            (data.error ||
             'Unable to send verification code.'),
            'err'
        );

    })

    .catch(function(error){

        console.error('OTP request error:', error);


        btn.disabled = false;

        btn.innerHTML =
            '<i class="fa fa-paper-plane me-2"></i>Retry';


        setStatus(
            '<i class="fa fa-times-circle me-1"></i>' +
            'Connection error. Please try again.',
            'err'
        );

    });

}


/* ============================================================
   STATUS MESSAGE
   ============================================================ */

function setStatus(message, type) {

    var element =
        document.getElementById('statusLine');

    element.innerHTML = message;

    element.className =
        'status-line ' + type;

}


/* ============================================================
   OTP INPUT HANDLING
   ============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function(){

        var digits =
            Array.from(
                document.querySelectorAll('.otp-digit')
            );


        digits.forEach(function(input,index){


            /* ================================================
               INPUT
               ================================================ */

            input.addEventListener(
                'input',
                function(){

                    this.value =
                        this.value
                        .replace(/[^0-9]/g,'')
                        .slice(-1);


                    this.classList.toggle(
                        'filled',
                        this.value !== ''
                    );


                    if (
                        this.value &&
                        index < digits.length - 1
                    ) {

                        digits[index + 1].focus();

                    }


                    checkComplete(digits);

                }
            );


            /* ================================================
               BACKSPACE
               ================================================ */

            input.addEventListener(
                'keydown',
                function(e){

                    if (
                        e.key === 'Backspace' &&
                        !this.value &&
                        index > 0
                    ) {

                        digits[index - 1].value = '';

                        digits[index - 1]
                            .classList
                            .remove('filled');

                        digits[index - 1].focus();

                        checkComplete(digits);

                    }

                }
            );


            /* ================================================
               PASTE
               ================================================ */

            input.addEventListener(
                'paste',
                function(e){

                    e.preventDefault();


                    var text =
                        (
                            e.clipboardData ||
                            window.clipboardData
                        )
                        .getData('text')
                        .replace(/\D/g,'');


                    digits.forEach(
                        function(digit,idx){

                            digit.value =
                                text.charAt(idx) || '';

                            digit.classList.toggle(
                                'filled',
                                digit.value !== ''
                            );

                        }
                    );


                    var last =
                        Math.min(
                            text.length,
                            digits.length
                        ) - 1;


                    if (last >= 0) {
                        digits[last].focus();
                    }


                    checkComplete(digits);

                }
            );

        });

    }
);


/* ============================================================
   CHECK SIX DIGITS
   ============================================================ */

function checkComplete(digits) {

    var complete =
        digits.every(function(digit){

            return digit.value !== '';

        });


    document.getElementById(
        'verifyBtn'
    ).disabled = !complete;

}


/* ============================================================
   CLEAR OTP
   ============================================================ */

function clearDigits() {

    var digits =
        document.querySelectorAll('.otp-digit');


    digits.forEach(function(digit){

        digit.value = '';

        digit.classList.remove('filled');

    });


    document.getElementById(
        'verifyBtn'
    ).disabled = true;

}


/* ============================================================
   BUILD OTP BEFORE PHP FORM SUBMISSION
   ============================================================ */

function buildCode() {

    var digits =
        Array.from(
            document.querySelectorAll('.otp-digit')
        );


    var code =
        digits
        .map(function(digit){

            return digit.value;

        })
        .join('');


    if (code.length !== 6) {

        setStatus(
            'Please enter all 6 digits.',
            'err'
        );

        return false;

    }


    document.getElementById(
        'otpHidden'
    ).value = code;


    return true;

}

</script>


</body>
</html>

