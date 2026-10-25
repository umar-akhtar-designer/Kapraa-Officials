<?php
include 'include/head.php';
include 'include/navbar.php';

// Wholesale discount tiers
$tiers = [
    ['min' => 5,  'max' => 9,  'discount' => 5,  'label' => '5–9 items'],
    ['min' => 10, 'max' => 19, 'discount' => 10, 'label' => '10–19 items'],
    ['min' => 20, 'max' => 49, 'discount' => 15, 'label' => '20–49 items'],
    ['min' => 50, 'max' => null,'discount' => 20, 'label' => '50+ items'],
];

// Handle inquiry form
$ws_success = '';
$ws_error   = '';
if (isset($_POST['ws_submit'])) {
    $ws_name    = cleanStr($_POST['ws_name']    ?? '');
    $ws_phone   = cleanStr($_POST['ws_phone']   ?? '');
    $ws_email   = cleanStr($_POST['ws_email']   ?? '');
    $ws_qty     = (int)($_POST['ws_qty']        ?? 0);
    $ws_message = cleanStr($_POST['ws_message'] ?? '');

    if (empty($ws_name) || empty($ws_phone) || $ws_qty < 1) {
        $ws_error = 'Please fill name, phone, and quantity.';
    } else {
        $disc = 0;
        foreach ($tiers as $t) {
            if ($ws_qty >= $t['min'] && ($t['max'] === null || $ws_qty <= $t['max'])) {
                $disc = $t['discount'];
                break;
            }
        }
        $subject  = "Wholesale Inquiry – {$ws_qty} items (Discount: {$disc}%)";
        $fullMsg  = "Name: {$ws_name}\nPhone: {$ws_phone}\nEmail: {$ws_email}\nQty: {$ws_qty}\nDiscount Eligible: {$disc}%\n\nMessage:\n{$ws_message}";
        $insert = insert_and_update_data('insert', 'contact', [
            'name'    => $ws_name,
            'email'   => $ws_email,
            'phone'   => $ws_phone,
            'subject' => $subject,
            'message' => $fullMsg,
        ]);
        if ($insert['status']) {
            $ws_success = "Your wholesale inquiry has been submitted! We'll contact you within 24 hours.";
        } else {
            $ws_error = 'Something went wrong. Please try again or WhatsApp us directly.';
        }
    }
}
?>

<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="<?php echo $url_domain; ?>/index">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Wholesale / Bulk Orders</li>
                </ol>
            </nav>
            <h1 class="h2">Wholesale &amp; Bulk Orders</h1>
            <p class="text-muted">Special pricing for shop owners, wedding planners & businesses</p>
        </div>
    </div>
</div>
<!-- End Page Title -->

<div class="container my-5">

    <!-- AI Discount Calculator -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-10">
            <div class="ws-ai-box p-4 rounded-4 mb-4" style="background:linear-gradient(135deg,#fdf0f5,#fce4ec);border:2px solid #f8bbd0;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span style="display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:50%;background:rgba(212,83,126,.12);flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D4537E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M9 11V7a3 3 0 0 1 6 0v4"/><circle cx="9" cy="16" r="1" fill="#D4537E"/><circle cx="15" cy="16" r="1" fill="#D4537E"/><path d="M12 3v2"/></svg></span>
                    <div>
                        <h4 class="mb-0">AI Bulk Discount Calculator</h4>
                        <small class="text-muted">Enter your quantity — our AI calculates your discount instantly</small>
                    </div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Number of Items</label>
                        <input type="number" id="ws-qty-calc" class="form-control form-control-lg" min="1" placeholder="e.g. 15" oninput="calcDiscount(this.value)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Price Per Item (RS.)</label>
                        <input type="number" id="ws-price-calc" class="form-control form-control-lg" min="0" placeholder="e.g. 5000" oninput="calcDiscount(document.getElementById('ws-qty-calc').value)">
                    </div>
                    <div class="col-md-4">
                        <div id="ws-calc-result" class="ws-calc-result p-3 rounded-3 text-center" style="background:#fff;border:2px dashed #D4537E;min-height:66px;display:flex;align-items:center;justify-content:center;">
                            <span class="text-muted">Enter quantity to see discount</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Discount Tiers -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-10">
            <h3 class="text-center mb-4">Bulk Discount Tiers</h3>
            <div class="row g-3">
                <?php
                $tierIcons  = [
                    '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
                    '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>',
                    '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
                    '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="1"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="17"/><line x1="9" y1="14.5" x2="15" y2="14.5"/></svg>',
                ];
                $tierColors = ['#fff3e0','#e8f5e9','#e3f2fd','#fce4ec'];
                $tierBorder = ['#ffcc02','#66bb6a','#42a5f5','#D4537E'];
                foreach ($tiers as $i => $t):
                    $label = $t['max'] ? "{$t['min']}–{$t['max']} items" : "{$t['min']}+ items";
                ?>
                <div class="col-md-3 col-6">
                    <div class="text-center p-3 rounded-3 h-100" style="background:<?php echo $tierColors[$i]; ?>;border:2px solid <?php echo $tierBorder[$i]; ?>;">
                        <div style="display:flex;align-items:center;justify-content:center;height:40px;"><?php echo $tierIcons[$i]; ?></div>
                        <h5 class="mt-2 mb-1"><?php echo $label; ?></h5>
                        <div style="font-size:2rem;font-weight:800;color:<?php echo $tierBorder[$i]; ?>"><?php echo $t['discount']; ?>% OFF</div>
                        <small class="text-muted">Automatic discount</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Benefits -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-10">
            <h3 class="text-center mb-4">Why Order Wholesale From Us?</h3>
            <div class="row g-3">
                <?php
                $svgRobot   = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D4537E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M9 11V7a3 3 0 0 1 6 0v4"/><circle cx="9" cy="16" r="1" fill="#D4537E"/><circle cx="15" cy="16" r="1" fill="#D4537E"/><path d="M12 3v2"/></svg>';
                $svgMoney   = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D4537E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6"/></svg>';
                $svgTruck   = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D4537E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
                $svgPhone   = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D4537E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.7 10.17 19.79 19.79 0 01.67 1.52 2 2 0 012.65.5h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 8.14a16 16 0 006.95 6.95l1-1a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>';
                $svgTag     = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D4537E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
                $svgRefresh = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D4537E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>';
                $benefits = [
                    ['icon'=>$svgRobot,   'title'=>'AI-Matched Sets',    'desc'=>'Our AI suggests matching outfits for your set suit orders'],
                    ['icon'=>$svgMoney,   'title'=>'Auto Bulk Discount', 'desc'=>'Discounts applied automatically based on your total quantity'],
                    ['icon'=>$svgTruck,   'title'=>'Priority Delivery',  'desc'=>'Wholesale orders get priority processing and dispatch'],
                    ['icon'=>$svgPhone,   'title'=>'Dedicated Support',  'desc'=>'Direct WhatsApp support for all wholesale inquiries'],
                    ['icon'=>$svgTag,     'title'=>'Branded Packaging',  'desc'=>'Custom packaging available for resellers'],
                    ['icon'=>$svgRefresh, 'title'=>'Easy Returns',       'desc'=>'Hassle-free returns for quality issues on bulk orders'],
                ];
                foreach ($benefits as $b):
                ?>
                <div class="col-md-4 col-6">
                    <div class="d-flex gap-3 align-items-start p-3 rounded-3 h-100" style="background:#f9f9f9;border:1px solid #eee;">
                        <span style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:50%;background:rgba(212,83,126,.08);"><?php echo $b['icon']; ?></span>
                        <div>
                            <h6 class="mb-1"><?php echo $b['title']; ?></h6>
                            <small class="text-muted"><?php echo $b['desc']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Inquiry Form -->
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h3 class="mb-1">Place a Wholesale Inquiry</h3>
                    <p class="text-muted mb-4">Fill the form and we'll get back to you within 24 hours.</p>

                    <?php if ($ws_success): ?>
                        <div class="alert alert-success rounded-3"><?php echo htmlspecialchars($ws_success); ?></div>
                    <?php endif; ?>
                    <?php if ($ws_error): ?>
                        <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($ws_error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="ws_name" class="form-control" placeholder="Your name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone / WhatsApp *</label>
                                <input type="text" name="ws_phone" class="form-control" placeholder="+92 3xx xxxxxxx" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="ws_email" class="form-control" placeholder="your@email.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Quantity Needed *</label>
                                <input type="number" name="ws_qty" id="ws-form-qty" class="form-control" min="1" placeholder="e.g. 20" required oninput="updateFormDiscount(this.value)">
                                <small id="ws-form-discount" class="text-success fw-bold mt-1 d-block"></small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Details / Special Requirements</label>
                                <textarea name="ws_message" class="form-control" rows="4" placeholder="Tell us about the brands, categories, occasions you need, budget, or any special requests..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="ws_submit" class="btn w-100 py-3 fw-bold text-white" style="background:linear-gradient(135deg,#D4537E,#b8375f);border:none;border-radius:12px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="white" stroke="none"/></svg> Submit Wholesale Inquiry
                                </button>
                            </div>
                            <div class="col-12 text-center">
                                <span class="text-muted">Or contact us directly on WhatsApp:</span><br>
                                <a href="https://wa.me/923209815213?text=Hello%2C%20I%20want%20to%20place%20a%20wholesale%20order!" target="_blank" class="btn btn-success mt-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="white" style="vertical-align:middle;margin-right:5px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> WhatsApp: +92 320 9815213
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
var tiers = <?php echo json_encode($tiers); ?>;

function getDiscount(qty) {
    qty = parseInt(qty) || 0;
    for (var i = 0; i < tiers.length; i++) {
        var t = tiers[i];
        if (qty >= t.min && (t.max === null || qty <= t.max)) return t.discount;
    }
    return 0;
}

function calcDiscount(qty) {
    qty = parseInt(qty) || 0;
    var price = parseFloat(document.getElementById('ws-price-calc').value) || 0;
    var disc  = getDiscount(qty);
    var el    = document.getElementById('ws-calc-result');

    if (qty < 1) {
        el.innerHTML = '<span class="text-muted">Enter quantity to see discount</span>';
        return;
    }
    if (disc === 0) {
        el.innerHTML = '<span class="text-muted">Minimum 5 items for bulk discount</span>';
        return;
    }
    var total    = qty * price;
    var saved    = total * disc / 100;
    var payable  = total - saved;
    var html = '<div><span style="font-size:1.6rem;font-weight:800;color:#D4537E;">' + disc + '% OFF</span><br>';
    if (price > 0) {
        html += '<small>Total: RS. ' + total.toLocaleString() + '</small><br>';
        html += '<small>You save: RS. ' + saved.toLocaleString() + '</small><br>';
        html += '<strong style="color:#2e7d32;">Pay: RS. ' + payable.toLocaleString() + '</strong>';
    }
    html += '</div>';
    el.innerHTML = html;
}

function updateFormDiscount(qty) {
    var disc = getDiscount(qty);
    var el   = document.getElementById('ws-form-discount');
    if (disc > 0) {
        el.textContent = 'You qualify for ' + disc + '% bulk discount!';
    } else if (parseInt(qty) > 0) {
        el.textContent = 'Order 5+ items to unlock bulk discounts';
        el.className = 'text-muted fw-normal mt-1 d-block';
    } else {
        el.textContent = '';
    }
}
</script>

<?php include 'include/footer.php'; ?>
