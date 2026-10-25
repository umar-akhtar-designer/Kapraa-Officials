<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title mb-0">All Orders</h4>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group" style="width:200px;">
                            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search orders...">
                        </div>
                        <!-- Export PDF Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fa fa-file-pdf-o me-1"></i> Export PDF
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><h6 class="dropdown-header" style="font-size:10px;">SELECT PERIOD</h6></li>
                                <li><a class="dropdown-item" href="export_orders_pdf.php?period=today" target="_blank">
                                    <i class="fa fa-calendar-o me-2 text-success"></i>Today's Summary
                                </a></li>
                                <li><a class="dropdown-item" href="export_orders_pdf.php?period=week" target="_blank">
                                    <i class="fa fa-calendar me-2 text-primary"></i>This Week's Summary
                                </a></li>
                                <li><a class="dropdown-item" href="export_orders_pdf.php?period=month" target="_blank">
                                    <i class="fa fa-calendar-check-o me-2 text-warning"></i>This Month's Summary
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order Date</th>
                                    <th>Order Number</th>
                                    <th>Customer Info</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
                                    <th>Payment Proof</th>
                                    <th>Order Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    // Fetch orders from database
                                    global $connection_database;
                                    
                                    $query = "SELECT * FROM orders ORDER BY created_at DESC";
                                    $result = mysqli_query($connection_database, $query);
                                    
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($order = mysqli_fetch_assoc($result)) {
                                ?>
                                <?php $isAiOrder = strpos($order['notes'] ?? '', 'AI Chat') !== false; ?>
                                <tr <?= $isAiOrder ? 'style="background:linear-gradient(90deg,#f0f4ff 0%,#fff 100%);"' : '' ?>>
                                    <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <?= $order['order_number'] ?>
                                        <?php if ($isAiOrder): ?>
                                        <br><span style="display:inline-flex;align-items:center;gap:3px;background:#4f46e5;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;margin-top:3px;">
                                            🤖 AI Order
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= $order['customer_name'] ?></div>
                                        <div class="text-muted small"><?= $order['email'] ?></div>
                                        <div class="text-muted small"><?= $order['phone'] ?></div>
                                        <?php if ($isAiOrder && !empty($order['notes'])): ?>
                                        <div style="font-size:11px;color:#4f46e5;margin-top:3px;">
                                            <?= htmlspecialchars($order['notes']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>RS. <?= number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <?php if (!empty($order['payment_proof'])): ?>
                                            <a href="../admin/images/payment_proofs/<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank">
                                                <img src="../admin/images/payment_proofs/<?= htmlspecialchars($order['payment_proof']) ?>"
                                                     style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;cursor:pointer;"
                                                     title="Click to view full image" />
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $pmLabel = match($order['payment_method'] ?? 'cod') {
                                            'stripe'  => ['Card / Stripe',   'bg-primary'],
                                            'manual'  => ['Bank Transfer',   'bg-warning text-dark'],
                                            default   => ['Cash on Delivery','bg-info'],
                                        };
                                        $psLabel = match(strtolower($order['payment_status'] ?? 'pending')) {
                                            'paid'   => ['<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px"><polyline points="20 6 9 17 4 12"/></svg>Paid',   'bg-success'],
                                            'failed' => ['<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Failed', 'bg-danger'],
                                            default  => ['<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Pending','bg-warning text-dark'],
                                        };
                                        ?>
                                        <span class="badge <?= $pmLabel[1] ?>" style="font-size:11px;"><?= $pmLabel[0] ?></span><br>
                                        <span class="badge <?= $psLabel[1] ?>" style="font-size:11px;margin-top:3px;"><?= $psLabel[0] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $statusClass = '';
                                            switch (strtolower($order['order_status'])) {
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'confirmed':
                                                    $statusClass = 'bg-info';
                                                    break;
                                                case 'packaging':
                                                    $statusClass = 'bg-secondary';
                                                    break;
                                                case 'out of delivery':
                                                    $statusClass = 'bg-primary';
                                                    break;
                                                case 'return':
                                                    $statusClass = 'bg-dark';
                                                    break;
                                                case 'failed deliver':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                case 'cancel':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                            }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= ucfirst($order['order_status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group flex-wrap" style="gap:2px;">
                                            <button class="btn btn-primary shadow btn-xs sharp me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewOrderModal"
                                                data-id="<?= $order['id'] ?>"
                                                data-proof="<?= htmlspecialchars($order['payment_proof'] ?? '') ?>"
                                                title="View Order">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <button class="btn btn-info shadow btn-xs sharp me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateStatusModal"
                                                data-id="<?= $order['id'] ?>"
                                                data-status="<?= $order['order_status'] ?>"
                                                title="Update Status">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php if (($order['payment_status'] ?? 'pending') === 'pending' && in_array($order['payment_method'], ['qr_transfer','jazzcash','stripe'])): ?>
                                            <button class="btn btn-success shadow btn-xs sharp me-1 btn-mark-paid"
                                                data-id="<?= $order['id'] ?>"
                                                title="Mark as Paid">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (!empty($order['payment_proof'])): ?>
                                            <button class="btn btn-warning shadow btn-xs sharp me-1 btn-view-proof"
                                                data-proof="<?= htmlspecialchars('../' . $order['payment_proof']) ?>"
                                                title="View Payment Screenshot">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-danger shadow btn-xs sharp me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                data-id="<?= $order['id'] ?>"
                                                title="Delete Order">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php if (!empty($order['payment_proof'])): ?>
                                        <div style="margin-top:6px;cursor:pointer;" class="proof-thumb-wrap" data-proof="<?= htmlspecialchars('../' . $order['payment_proof']) ?>">
                                            <img src="<?= '../' . htmlspecialchars($order['payment_proof']) ?>"
                                                 alt="Payment Proof"
                                                 style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:2px solid #ff6b35;display:block;"
                                                 onerror="this.parentElement.style.display='none'">
                                            <span style="font-size:10px;color:#ff6b35;display:block;text-align:center;margin-top:2px;font-weight:600;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                                View Proof
                                            </span>
                                        </div>
                                        <?php else: ?>
                                        <?php if ($order['payment_method'] === 'qr_transfer' && ($order['payment_status'] ?? '') !== 'paid'): ?>
                                        <div style="margin-top:4px;">
                                            <span style="font-size:10px;color:#dc3545;font-weight:600;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                No proof yet
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                                        }
                                    } else {
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center">No orders found</td>
                                </tr>
                                <?php
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Payment Proof Lightbox ════════════════════════════════ -->
<div id="proofLightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:9999;align-items:center;justify-content:center;flex-direction:column;">
    <div style="position:relative;max-width:92vw;max-height:88vh;background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 40px rgba(0,0,0,.5);">
        <button id="proofLightboxClose" style="position:absolute;top:-14px;right:-14px;width:32px;height:32px;border-radius:50%;background:#fff;border:2px solid #ff6b35;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;line-height:1;z-index:1;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div style="text-align:center;margin-bottom:10px;">
            <span style="font-size:13px;font-weight:700;color:#ff6b35;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Customer Payment Screenshot
            </span>
        </div>
        <img id="proofLightboxImg" src="" alt="Payment Proof"
             style="max-width:80vw;max-height:72vh;object-fit:contain;border-radius:8px;display:block;border:2px solid #ff6b35;">
        <div style="text-align:center;margin-top:10px;display:flex;gap:10px;justify-content:center;">
            <a id="proofLightboxDownload" href="" download target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 18px;background:#ff6b35;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download / Open Full Size
            </a>
            <button id="proofLightboxClose2" style="display:inline-flex;align-items:center;gap:6px;padding:7px 18px;background:#6c757d;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                Close
            </button>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <input type="hidden" id="orderId" name="order_id">
                    <div class="mb-3">
                        <label for="orderStatus" class="form-label">Order Status</label>
                        <select class="form-select" id="orderStatus" name="order_status" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="packaging">Packaging</option>
                            <option value="out of delivery">Out of Delivery</option>
                            <option value="return">Return</option>
                            <option value="failed deliver">Failed Deliver</option>
                            <option value="cancel">Cancel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusNote" class="form-label">Note (Optional)</label>
                        <textarea class="form-control" id="statusNote" name="note" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveStatusBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this order? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
<script>
    // ── Global: Payment Proof Lightbox (needs to be global for onclick in dynamic HTML)
    function openProofLightbox(proofPath) {
        if (!proofPath) return;
        const lb  = document.getElementById('proofLightbox');
        const img = document.getElementById('proofLightboxImg');
        const dl  = document.getElementById('proofLightboxDownload');
        img.src = proofPath;
        dl.href = proofPath;
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeProofLightbox() {
        const lb  = document.getElementById('proofLightbox');
        const img = document.getElementById('proofLightboxImg');
        lb.style.display = 'none';
        document.body.style.overflow = '';
        img.src = '';
    }

    // ── Build proof HTML safely (no nested escaping issues) ──────
    function buildProofHtml(proofPath) {
        if (!proofPath) {
            return '<div style="padding:8px 12px;background:#f8f8f8;border-radius:8px;font-size:12px;color:#999;border:1px solid #eee;">No payment screenshot uploaded</div>';
        }
        var src = '../' + proofPath;
        var div = document.createElement('div');
        div.style.cssText = 'margin:8px 0;padding:12px;background:#fff8f5;border:2px dashed #ff6b35;border-radius:10px;';

        // Title
        var title = document.createElement('p');
        title.style.cssText = 'margin:0 0 8px;font-weight:700;color:#ff6b35;font-size:13px;';
        title.textContent = 'Payment Screenshot (uploaded by customer)';
        div.appendChild(title);

        // Image
        var img = document.createElement('img');
        img.src = src;
        img.alt = 'Payment Proof';
        img.style.cssText = 'max-width:220px;max-height:220px;border-radius:8px;border:2px solid #ff6b35;cursor:zoom-in;display:block;margin-bottom:8px;';
        img.onclick = function() { openProofLightbox(src); };
        div.appendChild(img);

        // Download link
        var link = document.createElement('a');
        link.href = src;
        link.download = '';
        link.target = '_blank';
        link.style.cssText = 'display:inline-flex;align-items:center;gap:5px;padding:5px 14px;background:#ff6b35;color:#fff;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;';
        link.textContent = 'Download / Full Size';
        div.appendChild(link);

        // Return outer HTML — no escaping nightmares
        return div.outerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // View Order Modal
        const viewOrderModal = document.getElementById('viewOrderModal');
        const orderDetails = document.getElementById('orderDetails');
        
        viewOrderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-id');
            
            // Fetch order details via AJAX
            fetch(`include/get-order-details.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    let html = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Order Information</h6>
                                <p><strong>Order Number :</strong> ${data.order.order_number}</p>
                                <p><strong>Order Date :</strong> ${new Date(data.order.created_at).toLocaleDateString()}</p>
                                <p><strong>Payment Method :</strong> ${{cod:'Cash on Delivery',jazzcash:'JazzCash',stripe:'Card/Stripe',qr_transfer:'Bank Transfer / QR',easypaisa:'EasyPaisa'}[data.order.payment_method] || data.order.payment_method}</p>
                                <p><strong>Payment Status :</strong> <span class="badge ${{paid:'bg-success',failed:'bg-danger'}[data.order.payment_status]||'bg-warning text-dark'}">${data.order.payment_status || 'pending'}</span></p>
                                ${buildProofHtml(data.order.payment_proof)}
                                <p><strong>Order Status :</strong> <span class="badge bg-info">${data.order.order_status}</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <p><strong>Name :</strong> ${data.order.customer_name}</p>
                                <p><strong>Email :</strong> ${data.order.email}</p>
                                <p><strong>Phone :</strong> ${data.order.phone}</p>
                                <p><strong>Address :</strong> ${data.order.address}, ${data.order.city}, ${data.order.state}, ${data.order.country} - ${data.order.postal_code}</p>
                            </div>
                        </div>
                        <h6>Order Items</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product Images</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Size</th>
                                        <th>Color</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.items.forEach(item => {
                        // Handle multiple images if they exist (comma-separated)
                        let imageHtml = '';
                        if (item.image_path) {
                            const images = item.image_path.split(',');
                            imageHtml = images.map(img => `<img src="${img.trim()}" alt="${item.product_name}" width="50" height="50" class="me-1">`).join('');
                        } else {
                            imageHtml = `<img src="images/product/default.jpg" alt="${item.product_name}" width="50" height="50">`;
                        }
                        
                        // Handle color display - if colors is a JSON string or comma-separated
                        let colorHtml = '';
                        if (item.colors) {
                            try {
                                // Try to parse as JSON first
                                const colors = JSON.parse(item.colors);
                                if (Array.isArray(colors)) {
                                    colorHtml = colors.join(', ');
                                } else {
                                    colorHtml = item.colors;
                                }
                            } catch (e) {
                                // If not JSON, treat as comma-separated
                                colorHtml = item.colors;
                            }
                        } else {
                            colorHtml = 'N/A';
                        }
                        
                        html += `
                            <tr>
                                <td>${imageHtml}</td>
                                <td>${item.product_name}</td>
                                <td>RS. ${parseFloat(item.price).toFixed(2)}</td>
                                <td>${item.size || 'N/A'}</td>
                                <td>${colorHtml}</td>
                                <td>${item.quantity}</td>
                                <td>RS. ${parseFloat(item.total_price).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong>Subtotal:</strong> RS. ${parseFloat(data.order.subtotal).toFixed(2)}</p>
                            </div>
                            <div class="col-md-6">
                                <h5><strong>Total Amount:</strong> RS. ${parseFloat(data.order.total_amount).toFixed(2)}</h5>
                            </div>
                        </div>
                    `;
                    
                    orderDetails.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    orderDetails.innerHTML = '<p class="text-danger">Error loading order details</p>';
                });
        });
        
        // Update Status Modal
        const updateStatusModal = document.getElementById('updateStatusModal');
        const orderIdInput = document.getElementById('orderId');
        const orderStatusSelect = document.getElementById('orderStatus');
        
        updateStatusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-id');
            const currentStatus = button.getAttribute('data-status');
            
            orderIdInput.value = orderId;
            orderStatusSelect.value = currentStatus;
        });
        
        // Save Status Button
        document.getElementById('saveStatusBtn').addEventListener('click', function() {
            const orderId = document.getElementById('orderId').value;
            const orderStatus = document.getElementById('orderStatus').value;
            const note = document.getElementById('statusNote').value;
            
            // Send AJAX request to update status
            fetch('include/update-order-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&order_status=${orderStatus}&note=${encodeURIComponent(note)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(updateStatusModal);
                    modal.hide();
                    
                    // Show success message
                    showAlert('Order status updated successfully', 'success');
                    
                    // Reload page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('Error updating order status', 'danger');
                }
            })
            .catch(error => {
                console.error('Error updating order status:', error);
                showAlert('Error updating order status', 'danger');
            });
        });
        
        // Delete Modal
        const deleteModal = document.getElementById('deleteModal');
        let deleteOrderId = null;
        
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            deleteOrderId = button.getAttribute('data-id');
        });
        
        // Confirm Delete Button
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteOrderId) {
                // Send AJAX request to delete order
                fetch('include/delete-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${deleteOrderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(deleteModal);
                        modal.hide();
                        
                        // Show success message
                        showAlert('Order deleted successfully', 'success');
                        
                        // Remove row from table
                        const row = document.querySelector(`button[data-id="${deleteOrderId}"]`).closest('tr');
                        row.remove();
                        
                        // Check if there are any orders left
                        const rows = document.querySelectorAll('#ordersTable tbody tr');
                        if (rows.length === 0) {
                            const tbody = document.querySelector('#ordersTable tbody');
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No orders found</td></tr>';
                        }
                    } else {
                        showAlert('Error deleting order', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error deleting order:', error);
                    showAlert('Error deleting order', 'danger');
                });
            }
        });
        
        // ── Mark as Paid buttons ──────────────────────────────────
        document.querySelectorAll('.btn-mark-paid').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-id');
                if (!confirm('Mark this order as PAID?')) return;
                fetch('include/mark-payment-paid.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'order_id=' + orderId
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Payment marked as PAID', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        showAlert('Error: ' + (data.message || 'Could not update'), 'danger');
                    }
                })
                .catch(() => showAlert('Network error', 'danger'));
            });
        });

        // ── Payment Proof Lightbox ────────────────────────────────
        const proofLightbox = document.getElementById('proofLightbox');

        // Open via yellow camera button
        document.querySelectorAll('.btn-view-proof').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openProofLightbox(this.getAttribute('data-proof'));
            });
        });
        // Open via thumbnail click
        document.querySelectorAll('.proof-thumb-wrap').forEach(function(wrap) {
            wrap.addEventListener('click', function() {
                openProofLightbox(this.getAttribute('data-proof'));
            });
        });
        // Close buttons
        document.getElementById('proofLightboxClose').addEventListener('click',  closeProofLightbox);
        document.getElementById('proofLightboxClose2').addEventListener('click', closeProofLightbox);
        proofLightbox.addEventListener('click', function(e) {
            if (e.target === proofLightbox) closeProofLightbox();
        });
        // ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeProofLightbox();
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Status filter (element may not exist on all views)
        const _sf = document.getElementById('statusFilter');
        if (_sf) _sf.addEventListener('change', function() {
            const statusFilter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                if (statusFilter === '') {
                    row.style.display = '';
                } else {
                    const statusBadge = row.querySelector('.badge');
                    const status = statusBadge.textContent.toLowerCase();
                    row.style.display = status.includes(statusFilter) ? '' : 'none';
                }
            });
        });
        
        // Function to show alert
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    });
</script>

<?php
  include 'include/footer.php';
?>