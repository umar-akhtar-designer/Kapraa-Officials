<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Pending Orders</h4>
                    <div class="d-flex">
                       <div class="input-group me-2">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search orders...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="pendingOrdersTable">
                            <thead>
                                <tr>
                                    <th>Order Date</th>
                                    <th>Order Number</th>
                                    <th>Customer Info</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
                                    <th>Order Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    // Fetch only pending orders from database
                                    global $connection_database;
                                    
                                    $query = "SELECT * FROM orders WHERE order_status = 'confirmed' ORDER BY created_at DESC";
                                    $result = mysqli_query($connection_database, $query);
                                    
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($order = mysqli_fetch_assoc($result)) {
                                ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                    <td><?= $order['order_number'] ?></td>
                                    <td>
                                        <div><?= $order['customer_name'] ?></div>
                                        <div class="text-muted small"><?= $order['email'] ?></div>
                                        <div class="text-muted small"><?= $order['phone'] ?></div>
                                    </td>
                                    <td>RS. <?= number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <?php if ($order['payment_method'] == 'cod'): ?>
                                            <span class="badge bg-info">Cash on Delivery</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Online Payment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= ucfirst($order['order_status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-primary shadow btn-xs sharp me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewOrderModal"
                                                data-id="<?= $order['id'] ?>"
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
                                            <button class="btn btn-danger shadow btn-xs sharp me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                data-id="<?= $order['id'] ?>"
                                                title="Delete Order">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                        }
                                    } else {
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center">No pending orders found</td>
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
                                <p><strong>Payment Method :</strong> ${data.order.payment_method === 'cod' ? 'Cash on Delivery' : 'Online Payment'}</p>
                                <p><strong>Order Status :</strong> <span class="badge bg-warning">${data.order.order_status}</span></p>
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
                    const rows = document.querySelectorAll('#pendingOrdersTable tbody tr');
                    if (rows.length === 0) {
                        const tbody = document.querySelector('#pendingOrdersTable tbody');
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No pending orders found</td></tr>';
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
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#pendingOrdersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
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