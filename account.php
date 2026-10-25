<?php
// Start session early for login check (head.php will also do this safely)
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if not logged in
if (!isset($_SESSION['WEBSITE-LOGINSS'])) {
    header("Location: login"); exit();
}

$userId = $_SESSION['WEBSITE-LOGINSS'];

// Include head (sets up $connection_database, outputs HTML head + opens body)
include 'include/head.php';
// Include navbar
include 'include/navbar.php';

// ── Handle form submission ────────────────────────────────────────
$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $number   = trim($_POST['number']   ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($password)) {
        $stmt = $connection_database->prepare("UPDATE customers SET name=?,number=?,username=?,password=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $number, $username, md5($password), $userId);
    } else {
        $stmt = $connection_database->prepare("UPDATE customers SET name=?,number=?,username=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $number, $username, $userId);
    }
    if ($stmt->execute()) {
        $successMessage = "Your account has been updated successfully!";
    } else {
        $errorMessage = "Update failed. Please try again.";
    }
    $stmt->close();
}

// ── Fetch latest user data ────────────────────────────────────────
$name = $email = $number = $username = $image = '';
$stmt = $connection_database->prepare("SELECT name, email, number, username, image FROM customers WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row) {
    $name     = $row['name'];
    $email    = $row['email'];
    $number   = $row['number'];
    $username = $row['username'];
    $image    = $row['image'];
}
?>

<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">My Account</h1></div>
    </div>
</div>
<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain; ?>/index" title="Back to the home page">Home</a>
        <span aria-hidden="true">|</span>
        <span class="title-bold">My Account</span>
    </div>
</div>

<div class="container" style="padding-top:50px; padding-bottom:60px;">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-12 col-md-10 col-lg-8 box">

            <?php if ($successMessage): ?>
            <div class="alert alert-success"><p><?= htmlspecialchars($successMessage) ?></p></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><p><?= htmlspecialchars($errorMessage) ?></p></div>
            <?php endif; ?>

            <!-- Profile Banner -->
            <div style="background:linear-gradient(135deg,#1a1a2e 0%,#0f3460 100%);border-radius:10px;padding:24px 28px;display:flex;align-items:center;gap:18px;margin-bottom:8px;">
                <div style="width:68px;height:68px;border-radius:50%;border:3px solid rgba(249,202,36,.55);overflow:hidden;flex-shrink:0;background:#2a2a4e;display:flex;align-items:center;justify-content:center;">
                    <?php if (!empty($image)): ?>
                        <img src="<?= $url_domain ?>/admin/<?= htmlspecialchars($image) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fa fa-user" style="font-size:28px;color:rgba(255,255,255,.4);"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h4 style="color:#fff;font-size:17px;font-weight:700;margin:0 0 4px;"><?= htmlspecialchars($name ?: 'My Account') ?></h4>
                    <p style="color:rgba(255,255,255,.65);font-size:13px;margin:0;">
                        <i class="fa fa-envelope" style="color:#f9ca24;margin-right:5px;"></i><?= htmlspecialchars($email) ?>
                    </p>
                </div>
            </div>
            <div style="height:3px;background:linear-gradient(90deg,#f9ca24,#f0932b);border-radius:2px;margin-bottom:28px;"></div>

            <!-- Form -->
            <form method="POST" action="<?php echo $url_domain; ?>/account" class="customer-form">

                <!-- Personal Information -->
                <h5 style="font-size:12px;font-weight:700;color:#0f3460;text-transform:uppercase;letter-spacing:.8px;border-bottom:2px solid #f0f0f0;padding-bottom:10px;margin-bottom:18px;">
                    <i class="fa fa-user" style="margin-right:6px;"></i> Personal Information
                </h5>
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Your full name" required />
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <label>Username <span class="required">*</span></label>
                            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="Your username" required />
                        </div>
                    </div>
                </div>

                <!-- Contact Details -->
                <h5 style="font-size:12px;font-weight:700;color:#0f3460;text-transform:uppercase;letter-spacing:.8px;border-bottom:2px solid #f0f0f0;padding-bottom:10px;margin-bottom:18px;margin-top:6px;">
                    <i class="fa fa-address-book" style="margin-right:6px;"></i> Contact Details
                </h5>
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?= htmlspecialchars($email) ?>" readonly style="background:#f8f8f8;color:#999;cursor:not-allowed;" />
                            <span style="font-size:11px;color:#bbb;display:block;margin-top:3px;"><i class="fa fa-lock"></i> Email cannot be changed</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="number" value="<?= htmlspecialchars($number) ?>" placeholder="e.g. 03001234567" />
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <h5 style="font-size:12px;font-weight:700;color:#0f3460;text-transform:uppercase;letter-spacing:.8px;border-bottom:2px solid #f0f0f0;padding-bottom:10px;margin-bottom:18px;margin-top:6px;">
                    <i class="fa fa-lock" style="margin-right:6px;"></i> Change Password
                </h5>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current password" />
                            <span style="font-size:11px;color:#bbb;display:block;margin-top:3px;">Minimum 8 characters recommended</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row" style="margin-top:10px;">
                    <div class="col-12" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <button type="submit" class="btn mb-3">
                            <i class="fa fa-save" style="margin-right:6px;"></i>Save Changes
                        </button>
                        <a href="<?php echo $url_domain; ?>/account" class="btn mb-3"
                           style="background:#f5f5f5;color:#333;border:1.5px solid #ddd;">Cancel</a>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>
