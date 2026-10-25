<?php
include 'include/head.php';
session('WEBSITE-LOGIN');
global $connection_database;
?>
<script>
(function(){
    function kill(){
        var p=document.getElementById('preloader');
        if(p){p.style.cssText='display:none!important';try{p.remove();}catch(e){}}
        var m=document.getElementById('main-wrapper');
        if(m)m.classList.add('show');
    }
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',function(){setTimeout(kill,100);});}
    else{setTimeout(kill,100);}
    setTimeout(kill,800);
})();
</script>
<?php

// Ensure tables
mysqli_query($connection_database, "CREATE TABLE IF NOT EXISTS email_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    headline VARCHAR(255) DEFAULT '',
    sub_headline VARCHAR(255) DEFAULT '',
    body_text TEXT,
    button_text VARCHAR(100) DEFAULT 'Shop Now',
    button_url VARCHAR(500) DEFAULT '',
    badge_text VARCHAR(100) DEFAULT '',
    footer_note VARCHAR(255) DEFAULT '',
    sent_count INT DEFAULT 0,
    status ENUM('draft','sent') DEFAULT 'draft',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($connection_database, "CREATE TABLE IF NOT EXISTS email_campaign_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    email VARCHAR(255),
    status ENUM('sent','failed') DEFAULT 'sent',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$success = $error = '';
$editCampaign = null;

// Edit mode
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $eRes = mysqli_query($connection_database, "SELECT * FROM email_campaigns WHERE id=$eid LIMIT 1");
    if ($eRes) $editCampaign = mysqli_fetch_assoc($eRes);
}

// Save campaign
if (isset($_POST['save_campaign'])) {
    $subject     = mysqli_real_escape_string($connection_database, trim($_POST['subject']));
    $headline    = mysqli_real_escape_string($connection_database, trim($_POST['headline']));
    $sub_hl      = mysqli_real_escape_string($connection_database, trim($_POST['sub_headline']));
    $body        = mysqli_real_escape_string($connection_database, trim($_POST['body_text']));
    $btn_txt     = mysqli_real_escape_string($connection_database, trim($_POST['button_text']));
    $btn_url     = mysqli_real_escape_string($connection_database, trim($_POST['button_url']));
    $badge       = mysqli_real_escape_string($connection_database, trim($_POST['badge_text']));
    $footer_note = mysqli_real_escape_string($connection_database, trim($_POST['footer_note']));

    if (empty($subject)) { $error = 'Subject is required.'; }
    else {
        $cid = isset($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : 0;
        if ($cid > 0) {
            mysqli_query($connection_database, "UPDATE email_campaigns SET subject='$subject',headline='$headline',sub_headline='$sub_hl',body_text='$body',button_text='$btn_txt',button_url='$btn_url',badge_text='$badge',footer_note='$footer_note' WHERE id=$cid");
            $success = 'Campaign updated.';
            $eid = $cid;
            $eRes = mysqli_query($connection_database, "SELECT * FROM email_campaigns WHERE id=$cid LIMIT 1");
            if ($eRes) $editCampaign = mysqli_fetch_assoc($eRes);
        } else {
            mysqli_query($connection_database, "INSERT INTO email_campaigns (subject,headline,sub_headline,body_text,button_text,button_url,badge_text,footer_note) VALUES ('$subject','$headline','$sub_hl','$body','$btn_txt','$btn_url','$badge','$footer_note')");
            $newId = mysqli_insert_id($connection_database);
            $success = 'Campaign saved. <a href="email_campaigns.php?edit='.$newId.'" class="alert-link">Edit it</a> or send below.';
        }
    }
}

// Delete
if (isset($_POST['delete_id'])) {
    $did = (int)$_POST['delete_id'];
    mysqli_query($connection_database, "DELETE FROM email_campaigns WHERE id=$did");
    mysqli_query($connection_database, "DELETE FROM email_campaign_log WHERE campaign_id=$did");
    $success = 'Campaign deleted.';
}

// Fetch all unique customers from orders
$allCustomers = [];
$emailRes = mysqli_query($connection_database,
    "SELECT DISTINCT email, customer_name,
            COUNT(*) AS total_orders,
            MAX(created_at) AS last_order,
            SUM(total_amount) AS total_spent
     FROM orders
     WHERE email IS NOT NULL AND email != '' AND email LIKE '%@%'
     GROUP BY email
     ORDER BY last_order DESC");
if ($emailRes) while ($row = mysqli_fetch_assoc($emailRes)) $allCustomers[] = $row;
$totalEmails = count($allCustomers);

// Fetch all campaigns
$campaigns = [];
$cRes = mysqli_query($connection_database, "SELECT * FROM email_campaigns ORDER BY created_at DESC");
if ($cRes) while ($c = mysqli_fetch_assoc($cRes)) $campaigns[] = $c;

// Site URL for email links
$siteUrl = $isLocal ? "http://localhost/kapraa" : "https://kapraa.pk";
$logoUrl  = $siteUrl . "/assets/images/logo2.png";
?>

<div class="content-body">
<div class="container-fluid">

<div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
    <div>
        <h4 class="mb-0 font-w700"><i class="fa fa-envelope me-2 text-primary"></i>Email Campaigns</h4>
        <small class="text-muted">Send promotional emails to all your customers</small>
    </div>
    <span class="badge bg-info ms-auto" style="font-size:.85rem;"><i class="fa fa-users me-1"></i><?= $totalEmails ?> customer emails</span>
</div>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show py-2"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger  alert-dismissible fade show py-2"><?= $error   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-4">

<!-- ── LEFT: Template Editor ── -->
<div class="col-lg-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 font-w700"><i class="fa fa-pencil me-2 text-success"></i><?= $editCampaign ? 'Edit Campaign' : 'Create New Campaign' ?></h6>
        </div>
        <div class="card-body" style="max-height:80vh;overflow-y:auto;">
        <form method="POST" id="campaignForm">
            <?php if ($editCampaign): ?>
            <input type="hidden" name="campaign_id" value="<?= $editCampaign['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Email Subject <span class="text-danger">*</span></label>
                <input type="text" name="subject" class="form-control form-control-sm" placeholder="e.g. Exclusive 50% OFF — Just for You! 🎉" value="<?= htmlspecialchars($editCampaign['subject'] ?? '') ?>" oninput="livePreview()">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Badge Text <small class="text-muted fw-normal">(above headline)</small></label>
                <input type="text" name="badge_text" class="form-control form-control-sm" placeholder="e.g. LIMITED TIME OFFER" value="<?= htmlspecialchars($editCampaign['badge_text'] ?? 'LIMITED TIME OFFER') ?>" oninput="livePreview()">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Main Headline <small class="text-muted fw-normal">(big bold offer)</small></label>
                <input type="text" name="headline" class="form-control form-control-sm" placeholder="e.g. FLAT 50% OFF" value="<?= htmlspecialchars($editCampaign['headline'] ?? 'FLAT 50% OFF') ?>" oninput="livePreview()">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Sub Headline</label>
                <input type="text" name="sub_headline" class="form-control form-control-sm" placeholder="e.g. On all Summer Lawn Collection" value="<?= htmlspecialchars($editCampaign['sub_headline'] ?? '') ?>" oninput="livePreview()">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Body Message</label>
                <textarea name="body_text" class="form-control form-control-sm" rows="4" placeholder="Write a short message to your customers..." oninput="livePreview()"><?= htmlspecialchars($editCampaign['body_text'] ?? "Don't miss our biggest sale of the season! Shop premium Pakistani lawn suits, embroidered collections and more — all at unbeatable prices. Offer valid for a limited time only.") ?></textarea>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Button Text</label>
                    <input type="text" name="button_text" class="form-control form-control-sm" placeholder="Shop Now" value="<?= htmlspecialchars($editCampaign['button_text'] ?? 'Shop Now') ?>" oninput="livePreview()">
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Button URL</label>
                    <input type="text" name="button_url" class="form-control form-control-sm" placeholder="https://..." value="<?= htmlspecialchars($editCampaign['button_url'] ?? $siteUrl) ?>" oninput="livePreview()">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold" style="font-size:.8rem;color:#555;">Footer Note <small class="text-muted fw-normal">(small text at bottom)</small></label>
                <input type="text" name="footer_note" class="form-control form-control-sm" placeholder="e.g. Offer valid while stocks last." value="<?= htmlspecialchars($editCampaign['footer_note'] ?? 'Offer valid while stocks last. T&Cs apply.') ?>" oninput="livePreview()">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" name="save_campaign" class="btn btn-success btn-sm flex-fill"><i class="fa fa-save me-1"></i>Save Campaign</button>
                <?php if ($editCampaign): ?>
                <a href="email_campaigns.php" class="btn btn-outline-secondary btn-sm">+ New</a>
                <?php endif; ?>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- ── RIGHT: Live Preview ── -->
<div class="col-lg-7">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 font-w700"><i class="fa fa-eye me-2 text-info"></i>Live Email Preview</h6>
            <?php if ($editCampaign): ?>
            <button class="btn btn-primary btn-sm" onclick="openSendModal(<?= $editCampaign['id'] ?>,'<?= addslashes($editCampaign['subject']) ?>')">
                <i class="fa fa-paper-plane me-1"></i>Send to <?= $totalEmails ?> Customers
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0" style="background:#f5f5f5;">
            <div style="padding:16px;max-height:78vh;overflow-y:auto;">
                <div id="emailPreviewWrap" style="max-width:520px;margin:0 auto;">
                    <!-- Preview injected by JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Campaign History ── -->
<div class="col-12">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 font-w700"><i class="fa fa-history me-2"></i>Campaign History (<?= count($campaigns) ?>)</h6>
        </div>
        <div class="card-body p-0">
        <?php if (empty($campaigns)): ?>
            <div class="text-center py-5 text-muted"><i class="fa fa-envelope-o" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>No campaigns yet.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
            <thead class="table-light">
                <tr><th>Subject</th><th>Headline</th><th>Sent To</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($campaigns as $c): ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($c['subject']) ?></td>
                <td><?= htmlspecialchars($c['headline']) ?></td>
                <td><?= $c['sent_count'] > 0 ? '<span class="badge bg-success">'.$c['sent_count'].' sent</span>' : '<span class="badge bg-secondary">Draft</span>' ?></td>
                <td><?= $c['status'] === 'sent' ? '<span class="badge bg-primary">Sent</span>' : '<span class="badge bg-warning text-dark">Draft</span>' ?></td>
                <td style="color:#888;"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="email_campaigns.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa fa-pencil"></i></a>
                        <?php if ($c['status'] !== 'sent'): ?>
                        <button class="btn btn-sm btn-primary" onclick="openSendModal(<?= $c['id'] ?>,'<?= addslashes($c['subject']) ?>')" title="Send"><i class="fa fa-paper-plane"></i></button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-success" onclick="openSendModal(<?= $c['id'] ?>,'<?= addslashes($c['subject']) ?>')" title="Resend"><i class="fa fa-refresh"></i></button>
                        <?php endif; ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?')">
                            <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- row -->
</div>
</div>

<!-- ── Send Modal ── -->
<div class="modal fade" id="sendModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-bottom py-3">
        <div>
          <h5 class="modal-title font-w700 mb-0"><i class="fa fa-paper-plane me-2 text-primary"></i>Send Campaign</h5>
          <small class="text-muted" id="sendModalSubjectWrap">Subject: <strong id="sendModalSubject"></strong></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">

        <!-- Recipient Selector -->
        <div id="recipientPanel" style="padding:16px 20px;">

          <!-- Quick filter bar -->
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
            <button type="button" class="btn btn-sm btn-dark" onclick="selectAll(true)"><i class="fa fa-check-square-o me-1"></i>Select All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll(false)"><i class="fa fa-square-o me-1"></i>Deselect All</button>
            <input type="text" id="custSearch" placeholder="Search name or email…" class="form-control form-control-sm" style="max-width:220px;margin-left:auto;" oninput="filterCustomers()">
            <span id="selectedCount" class="badge bg-primary" style="font-size:.82rem;">0 selected</span>
          </div>

          <!-- Customer list -->
          <div style="max-height:320px;overflow-y:auto;border:1px solid #eee;border-radius:10px;">
          <table class="table table-hover table-sm align-middle mb-0" style="font-size:.82rem;">
            <thead class="table-dark" style="position:sticky;top:0;">
              <tr>
                <th style="width:36px;"><input type="checkbox" id="masterChk" onchange="selectAll(this.checked)" title="Select All"></th>
                <th>Customer</th>
                <th>Email</th>
                <th>Orders</th>
                <th>Spent</th>
                <th>Last Order</th>
              </tr>
            </thead>
            <tbody id="custTableBody">
            <?php foreach ($allCustomers as $i => $cust): ?>
            <tr class="cust-row" data-name="<?= strtolower(htmlspecialchars($cust['customer_name'])) ?>" data-email="<?= strtolower(htmlspecialchars($cust['email'])) ?>">
              <td><input type="checkbox" class="cust-chk" value="<?= htmlspecialchars($cust['email']) ?>" data-name="<?= htmlspecialchars($cust['customer_name']) ?>" onchange="updateCount()"></td>
              <td class="fw-bold"><?= htmlspecialchars($cust['customer_name']) ?></td>
              <td style="color:#666;"><?= htmlspecialchars($cust['email']) ?></td>
              <td><span class="badge bg-light text-dark"><?= (int)$cust['total_orders'] ?></span></td>
              <td>Rs <?= number_format((float)$cust['total_spent']) ?></td>
              <td style="color:#aaa;"><?= date('d M Y', strtotime($cust['last_order'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          </div>

        </div>

        <!-- Progress Panel (hidden until sending) -->
        <div id="sendProgress" style="display:none;padding:20px;">
          <div style="text-align:center;margin-bottom:16px;">
            <div id="sendSpinner" style="font-size:2.5rem;color:#0f3460;"><i class="fa fa-paper-plane"></i></div>
            <div id="sendStatus" style="font-size:.88rem;color:#555;margin-top:10px;">Sending…</div>
          </div>
          <div class="progress" style="height:14px;border-radius:8px;">
            <div id="sendProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:0%;transition:width .4s;"></div>
          </div>
          <div id="sendProgressText" style="font-size:.75rem;color:#aaa;text-align:center;margin-top:6px;"></div>
        </div>

      </div>
      <div class="modal-footer border-top py-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" id="cancelBtn">Cancel</button>
        <button type="button" class="btn btn-sm btn-primary" id="sendNowBtn" onclick="startSend()">
            <i class="fa fa-paper-plane me-1"></i>Send to <span id="sendBtnCount">0</span> Customers
        </button>
      </div>
    </div>
  </div>
</div>

<script>
var _sendCampaignId = null;

function openSendModal(id, subject) {
    _sendCampaignId = id;
    document.getElementById('sendModalSubject').textContent = subject;
    // Reset UI
    document.getElementById('recipientPanel').style.display = 'block';
    document.getElementById('sendProgress').style.display   = 'none';
    document.getElementById('sendNowBtn').disabled = false;
    document.getElementById('sendNowBtn').innerHTML = '<i class="fa fa-paper-plane me-1"></i>Send to <span id="sendBtnCount">0</span> Customers';
    document.getElementById('cancelBtn').style.display = '';
    // Select all by default
    selectAll(true);
    var modal = new bootstrap.Modal(document.getElementById('sendModal'));
    modal.show();
}

function selectAll(checked) {
    document.querySelectorAll('.cust-chk').forEach(function(chk) {
        var row = chk.closest('tr');
        if (row.style.display !== 'none') chk.checked = checked;
    });
    var master = document.getElementById('masterChk');
    if (master) master.checked = checked;
    updateCount();
}

function filterCustomers() {
    var q = document.getElementById('custSearch').value.toLowerCase().trim();
    document.querySelectorAll('.cust-row').forEach(function(row) {
        var name  = row.dataset.name  || '';
        var email = row.dataset.email || '';
        row.style.display = (!q || name.includes(q) || email.includes(q)) ? '' : 'none';
    });
    updateCount();
}

function updateCount() {
    var checked = document.querySelectorAll('.cust-chk:checked').length;
    document.getElementById('selectedCount').textContent = checked + ' selected';
    var btn = document.getElementById('sendBtnCount');
    if (btn) btn.textContent = checked;
    document.getElementById('sendNowBtn').disabled = (checked === 0);
}

function getSelectedEmails() {
    var emails = [];
    document.querySelectorAll('.cust-chk:checked').forEach(function(chk) {
        emails.push(chk.value);
    });
    return emails;
}

function startSend() {
    var emails = getSelectedEmails();
    if (emails.length === 0) { alert('Please select at least one customer.'); return; }
    if (!_sendCampaignId) return;

    // Switch to progress view
    document.getElementById('recipientPanel').style.display = 'none';
    document.getElementById('sendProgress').style.display   = 'block';
    document.getElementById('sendNowBtn').disabled = true;
    document.getElementById('sendNowBtn').innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Sending…';
    document.getElementById('cancelBtn').style.display = 'none';
    document.getElementById('sendStatus').textContent = 'Sending to ' + emails.length + ' customers…';

    var bar = document.getElementById('sendProgressBar');
    bar.style.width = '30%';

    var body = 'campaign_id=' + _sendCampaignId;
    emails.forEach(function(e) { body += '&emails[]=' + encodeURIComponent(e); });

    fetch('send_campaign.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        bar.style.width = '100%';
        bar.classList.remove('progress-bar-animated', 'bg-primary');
        if (data.success) {
            bar.classList.add('bg-success');
            document.getElementById('sendSpinner').innerHTML = '<i class="fa fa-check-circle" style="color:#15803d;"></i>';
            document.getElementById('sendStatus').innerHTML  = '<strong style="color:#15803d;font-size:1rem;">✓ Sent to ' + data.sent + ' customers!</strong>' + (data.failed > 0 ? '<br><small class="text-muted">' + data.failed + ' failed</small>' : '');
            document.getElementById('sendProgressText').textContent = '';
            document.getElementById('sendNowBtn').innerHTML = '<i class="fa fa-check me-1"></i>Done';
            setTimeout(function(){ location.reload(); }, 2200);
        } else {
            bar.classList.add('bg-danger');
            document.getElementById('sendSpinner').innerHTML = '<i class="fa fa-times-circle" style="color:#dc2626;"></i>';
            document.getElementById('sendStatus').textContent = 'Error: ' + (data.error || 'Unknown error');
            document.getElementById('sendNowBtn').disabled = false;
            document.getElementById('sendNowBtn').innerHTML = '<i class="fa fa-refresh me-1"></i>Retry';
        }
    })
    .catch(function() {
        document.getElementById('sendStatus').textContent = 'Connection error. Please try again.';
        document.getElementById('sendNowBtn').disabled = false;
        document.getElementById('sendNowBtn').innerHTML = '<i class="fa fa-refresh me-1"></i>Retry';
    });
}

// ── Live Preview ──────────────────────────────────────────────
function val(name) {
    var el = document.querySelector('[name="'+name+'"]');
    return el ? el.value : '';
}

function livePreview() {
    var badge    = val('badge_text')   || '';
    var headline = val('headline')     || 'FLAT 50% OFF';
    var subhl    = val('sub_headline') || '';
    var body     = val('body_text')    || '';
    var btnTxt   = val('button_text')  || 'Shop Now';
    var btnUrl   = val('button_url')   || '#';
    var footer   = val('footer_note')  || '';
    var logoUrl  = '<?= $logoUrl ?>';

    var html = `
    <div style="font-family:'Segoe UI',Arial,sans-serif;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);">

      <!-- Header -->
      <div style="background:linear-gradient(135deg,#1a1a2e 0%,#0f3460 100%);padding:28px 32px;text-align:center;">
        <img src="${logoUrl}" alt="Kapraa" style="height:48px;max-width:180px;object-fit:contain;" onerror="this.style.display='none'">
      </div>

      <!-- Banner strip -->
      <div style="background:linear-gradient(90deg,#f9ca24,#f0932b);height:6px;"></div>

      <!-- Body -->
      <div style="padding:36px 32px;text-align:center;background:#fff;">
        ${badge ? `<div style="display:inline-block;background:#fff3cd;color:#856404;border-radius:20px;padding:5px 16px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:16px;">${badge}</div>` : ''}

        <div style="font-size:48px;font-weight:900;color:#1a1a2e;letter-spacing:-1px;line-height:1;margin-bottom:10px;">${headline}</div>

        ${subhl ? `<div style="font-size:18px;color:#555;font-weight:600;margin-bottom:20px;">${subhl}</div>` : ''}

        <div style="width:50px;height:3px;background:linear-gradient(90deg,#f9ca24,#f0932b);margin:0 auto 24px;border-radius:2px;"></div>

        ${body ? `<div style="font-size:14px;color:#666;line-height:1.7;margin-bottom:28px;max-width:400px;margin-left:auto;margin-right:auto;">${body.replace(/\n/g,'<br>')}</div>` : ''}

        <a href="${btnUrl}" style="display:inline-block;background:linear-gradient(135deg,#1a1a2e,#0f3460);color:#fff;text-decoration:none;padding:14px 40px;border-radius:30px;font-size:15px;font-weight:700;letter-spacing:.5px;margin-bottom:8px;">${btnTxt} &rarr;</a>
      </div>

      <!-- Gold divider -->
      <div style="background:linear-gradient(90deg,#f9ca24,#f0932b);height:4px;"></div>

      <!-- Footer -->
      <div style="background:#f8f8f8;padding:20px 32px;text-align:center;">
        <p style="margin:0 0 6px;font-size:12px;color:#888;">You're receiving this because you shopped with <strong>Kapraa</strong>.</p>
        ${footer ? `<p style="margin:0;font-size:11px;color:#aaa;">${footer}</p>` : ''}
        <p style="margin:8px 0 0;font-size:12px;color:#888;">© 2025 Kapraa · <a href="<?= $siteUrl ?>" style="color:#f0932b;text-decoration:none;">kapraa.pk</a></p>
      </div>

    </div>`;

    document.getElementById('emailPreviewWrap').innerHTML = html;
}

// Init preview on load
document.addEventListener('DOMContentLoaded', livePreview);
</script>

<?php include 'include/footer.php'; ?>
