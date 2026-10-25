<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
    
    // Existing queries for dashboard cards
    $blogQuery = selectQuery("SELECT count(id) as today FROM blog");
    $Totalblog = number_format($blogQuery['data'][0]['today']);
    
    $teamQuery = selectQuery("SELECT count(id) as today FROM team");
    $Totalteam = number_format($teamQuery['data'][0]['today']);
    
    $contactQuery = selectQuery("SELECT count(id) as today FROM contact");
    $Totalcontact = number_format($contactQuery['data'][0]['today']);
    
    $brandQuery = selectQuery("SELECT count(id) as today FROM brand");
    $Totalbrand = number_format($brandQuery['data'][0]['today']);
    
    $customerQuery = selectQuery("SELECT count(id) as today FROM customers");
    $Totalcustomer = number_format($customerQuery['data'][0]['today']);
    
    $productQuery = selectQuery("SELECT count(id) as today FROM products");
    $Totalproduct = number_format($productQuery['data'][0]['today']);
    
    // Get all counts in a single query
    global $connection_database;
    
    $query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
    $result = mysqli_query($connection_database, $query);
    
    // Initialize counts
    $totalCount = 0;
    $pendingCount = 0;
    $confirmedCount = 0;
    $packagingCount = 0;
    $deliveryCount = 0;
    $returnCount = 0;
    $failedCount = 0;
    $cancelCount = 0;
    
    // Process results
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['order_status'];
        $count = $row['count'];
        
        // Add to total
        $totalCount += $count;
        
        // Update specific status count
        switch ($status) {
            case 'pending':
                $pendingCount = $count;
                break;
            case 'confirmed':
                $confirmedCount = $count;
                break;
            case 'packaging':
                $packagingCount = $count;
                break;
            case 'out of delivery':
                $deliveryCount = $count;
                break;
            case 'return':
                $returnCount = $count;
                break;
            case 'failed deliver':
                $failedCount = $count;
                break;
            case 'cancel':
                $cancelCount = $count;
                break;
        }
    }
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <!-- Dashboard Cards -->
            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="#">
                    <div class="widget-stat card bg-danger rounded-2">
                        <div class="card-body  p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-list"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Orders</p>
                                    <h5 class="text-white"><?php echo $totalCount; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="product.php" target="_blank">
                    <div class="widget-stat card bg-orange rounded-2">
                        <div class="card-body  p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-arrow-up"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Products </p>
                                    <h5 class="text-white"><?php echo $Totalproduct?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="customers.php" target="_blank">
                    <div class="widget-stat card bg-blue rounded-2">
                        <div class="card-body  p-4">
                            <div class="media">
                                <span class="me-3">
                                <i class="fa-solid fa-chart-column"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Customers</p>
                                    <h5 class="text-white"><?php echo $Totalcustomer?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="pending_order.php">
                    <div class="widget-stat card bg-secondary rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Pending Orders</p>
                                    <h5 class="text-white"><?php echo $pendingCount; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="cancel_order.php">
                    <div class="widget-stat card bg-blue rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-times-circle"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Cancel Orders</p>
                                    <h5 class="text-white"><?php echo $cancelCount; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="confirmed_order.php">
                    <div class="widget-stat card bg-orange rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-check-circle"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Confirmed Orders</p>
                                    <h5 class="text-white"><?php echo $confirmedCount; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="packaging_order.php">
                    <div class="widget-stat card bg-danger rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-cube"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Packaging Orders</p>
                                    <h5 class="text-white"><?php echo $packagingCount; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="dlivery_order.php">
                    <div class="widget-stat card bg-secondary rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-truck"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Out for Delivery</p>
                                    <h5 class="text-white"><?php echo $deliveryCount; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="blog.php" target="_blank">
                    <div class="widget-stat card bg-danger rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fas fa-newspaper"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Blogs</p>
                                    <h5 class="text-white"><?php echo $Totalblog; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="team.php" target="_blank">
                    <div class="widget-stat card bg-success rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-users"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Team</p>
                                    <h5 class="text-white"><?php echo $Totalteam; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="contact.php" target="_blank">
                    <div class="widget-stat card bg-warning rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-envelope"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Contacts</p>
                                    <h5 class="text-white"><?php echo $Totalcontact; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-xxl-3 col-lg-3 col-12">
                <a href="add_brand.php" target="_blank">
                    <div class="widget-stat card bg-info rounded-2">
                        <div class="card-body p-4">
                            <div class="media">
                                <span class="me-3">
                                    <i class="fa fa-tag"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1">Total Brands</p>
                                    <h5 class="text-white"><?php echo $Totalbrand; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- ═══════════════════════════════════════════════════
                 REVENUE / AI CHAT SUMMARY CARDS
            ═══════════════════════════════════════════════════ -->
            <?php
            // Revenue summary stats
            $revRow = mysqli_fetch_assoc(mysqli_query($connection_database,
                "SELECT COALESCE(SUM(total_amount),0) AS total_rev,
                        COALESCE(AVG(total_amount),0) AS avg_val,
                        COUNT(*) AS total_orders
                 FROM orders"));
            $totalRevenue = (float)($revRow['total_rev'] ?? 0);
            $avgOrderVal  = (float)($revRow['avg_val']  ?? 0);
            $totalOrders  = (int)($revRow['total_orders'] ?? 0);

            $aiChatRow = mysqli_fetch_assoc(mysqli_query($connection_database,
                "SELECT COUNT(*) AS cnt FROM orders WHERE notes LIKE '%AI Chat%'"));
            $aiChatOrders = (int)($aiChatRow['cnt'] ?? 0);
            ?>
            <div class="col-xl-4 col-lg-4 col-md-4 col-12">
                <div class="widget-stat card rounded-2" style="background:#1a7f5a;">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-rupee-sign" style="color:#fff;font-size:22px;"></i></span>
                            <div class="media-body text-white">
                                <p class="mb-1">Total Revenue</p>
                                <h5 class="text-white">Rs <?php echo number_format($totalRevenue, 0); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-4 col-md-4 col-12">
                <div class="widget-stat card rounded-2" style="background:#6f42c1;">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-chart-line" style="color:#fff;font-size:22px;"></i></span>
                            <div class="media-body text-white">
                                <p class="mb-1">Avg Order Value</p>
                                <h5 class="text-white">Rs <?php echo number_format($avgOrderVal, 0); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-4 col-md-4 col-12">
                <div class="widget-stat card rounded-2" style="background:#0d6efd;">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-robot" style="color:#fff;font-size:22px;"></i></span>
                            <div class="media-body text-white">
                                <p class="mb-1">AI Chat Orders</p>
                                <h5 class="text-white"><?php echo $aiChatOrders; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════
                 CHARTS SECTION
            ═══════════════════════════════════════════════════ -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="text-primary fw-bold mb-0">Sales Analytics</h4>
                        <div class="btn-group" role="group" id="periodBtnGroup">
                            <button type="button" class="btn btn-sm btn-primary period-btn active" data-period="7d">7 Days</button>
                            <button type="button" class="btn btn-sm btn-outline-primary period-btn" data-period="30d">30 Days</button>
                            <button type="button" class="btn btn-sm btn-outline-primary period-btn" data-period="12m">12 Months</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-xl-6 col-lg-6">
                                <h6 class="text-muted fw-semibold mb-2">Orders Over Time</h6>
                                <canvas id="ordersLineChart" height="200"></canvas>
                            </div>
                            <div class="col-xl-6 col-lg-6">
                                <h6 class="text-muted fw-semibold mb-2">Revenue Over Time</h6>
                                <canvas id="revenueBarChart" height="200"></canvas>
                            </div>
                            <div class="col-xl-6 col-lg-6">
                                <h6 class="text-muted fw-semibold mb-2">Order Status Breakdown</h6>
                                <div style="max-width:340px;margin:0 auto;">
                                    <canvas id="statusDoughnutChart" height="220"></canvas>
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 d-flex align-items-center">
                                <div id="statusLegend" class="w-100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            /* Charts init — runs after footer loads local Chart.js v3.8 */
            window.addEventListener('load', function () {
            (function () {
                if (typeof Chart === 'undefined') return;
                // ── Status doughnut (static data from PHP) ──────────────
                const statusLabels = ['Pending','Confirmed','Packaging','Out of Delivery','Return','Failed','Cancelled'];
                const statusData   = [
                    <?php echo $pendingCount; ?>,
                    <?php echo $confirmedCount; ?>,
                    <?php echo $packagingCount; ?>,
                    <?php echo $deliveryCount; ?>,
                    <?php echo $returnCount; ?>,
                    <?php echo $failedCount; ?>,
                    <?php echo $cancelCount; ?>
                ];
                const statusColors = ['#ffc107','#198754','#0dcaf0','#6f42c1','#fd7e14','#dc3545','#6c757d'];

                const dCtx = document.getElementById('statusDoughnutChart').getContext('2d');
                const doughnutChart = new Chart(dCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusData,
                            backgroundColor: statusColors,
                            borderWidth: 2,
                            borderColor: '#fff',
                        }]
                    },
                    options: {
                        cutout: '65%',
                        plugins: { legend: { display: false } },
                        animation: { animateScale: true }
                    }
                });

                // Build legend
                const legendEl = document.getElementById('statusLegend');
                statusLabels.forEach((lbl, i) => {
                    if (statusData[i] === 0) return;
                    legendEl.innerHTML += `<div class="d-flex align-items-center mb-2">
                        <span style="width:14px;height:14px;border-radius:3px;background:${statusColors[i]};display:inline-block;margin-right:8px;flex-shrink:0;"></span>
                        <span style="font-size:13px;">${lbl}: <strong>${statusData[i]}</strong></span>
                    </div>`;
                });

                // ── Line + Bar charts (dynamic via AJAX) ────────────────
                let lineChart = null, barChart = null;

                const lineCtx = document.getElementById('ordersLineChart').getContext('2d');
                const barCtx  = document.getElementById('revenueBarChart').getContext('2d');

                function buildLineChart(labels, data) {
                    if (lineChart) lineChart.destroy();
                    lineChart = new Chart(lineCtx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Orders',
                                data,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13,110,253,0.1)',
                                borderWidth: 2,
                                pointRadius: 4,
                                pointBackgroundColor: '#0d6efd',
                                fill: true,
                                tension: 0.35,
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                            plugins: { legend: { display: false } }
                        }
                    });
                }

                function buildBarChart(labels, data) {
                    if (barChart) barChart.destroy();
                    barChart = new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Revenue (Rs)',
                                data,
                                backgroundColor: 'rgba(26,127,90,0.75)',
                                borderColor: '#1a7f5a',
                                borderWidth: 1,
                                borderRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: { y: { beginAtZero: true, ticks: {
                                callback: v => 'Rs ' + Number(v).toLocaleString()
                            }}},
                            plugins: { legend: { display: false } }
                        }
                    });
                }

                function loadPeriod(period) {
                    fetch('dashboard_stats.php?period=' + period)
                        .then(r => r.json())
                        .then(d => {
                            buildLineChart(d.labels, d.orders_count);
                            buildBarChart(d.labels, d.revenue);
                        })
                        .catch(() => {
                            buildLineChart(['No data'], [0]);
                            buildBarChart(['No data'], [0]);
                        });
                }

                // Period buttons
                document.querySelectorAll('.period-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        document.querySelectorAll('.period-btn').forEach(b => {
                            b.classList.remove('btn-primary', 'active');
                            b.classList.add('btn-outline-primary');
                        });
                        this.classList.add('btn-primary', 'active');
                        this.classList.remove('btn-outline-primary');
                        loadPeriod(this.dataset.period);
                    });
                });

                // Load default (7d) on page load
                loadPeriod('7d');
            })();
            }); // end window load
            </script>

            <!-- ═══════════════════════════════════════════════════
                 TOP PRODUCTS + PAYMENT METHOD CHARTS
            ═══════════════════════════════════════════════════ -->
            <?php
            // Top 5 selling products by quantity
            $topProdsRes = mysqli_query($connection_database,
                "SELECT product_name, SUM(quantity) AS total_qty, SUM(total_price) AS total_rev
                 FROM order_items
                 GROUP BY product_name
                 ORDER BY total_qty DESC
                 LIMIT 5");
            $topProdLabels = []; $topProdQty = []; $topProdRev = [];
            if ($topProdsRes) {
                while ($r = mysqli_fetch_assoc($topProdsRes)) {
                    $n = $r['product_name']; $topProdLabels[] = strlen($n) > 22 ? substr($n, 0, 22) . '…' : $n;
                    $topProdQty[]    = (int)$r['total_qty'];
                    $topProdRev[]    = round((float)$r['total_rev'], 0);
                }
            }

            // Payment method breakdown
            $pmRes = mysqli_query($connection_database,
                "SELECT payment_method, COUNT(*) AS cnt FROM orders GROUP BY payment_method ORDER BY cnt DESC");
            $pmLabels = []; $pmCounts = [];
            $pmNames  = ['cod'=>'Cash on Delivery','jazzcash'=>'JazzCash','easypaisa'=>'EasyPaisa','stripe'=>'Card/Stripe','qr_transfer'=>'Bank Transfer'];
            if ($pmRes) {
                while ($r = mysqli_fetch_assoc($pmRes)) {
                    $pmLabels[] = $pmNames[$r['payment_method']] ?? ucfirst($r['payment_method']);
                    $pmCounts[] = (int)$r['cnt'];
                }
            }

            // Today's revenue & orders
            $todayRow = mysqli_fetch_assoc(mysqli_query($connection_database,
                "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev
                 FROM orders WHERE DATE(created_at) = CURDATE()"));
            $todayOrders  = (int)($todayRow['cnt'] ?? 0);
            $todayRevenue = (float)($todayRow['rev'] ?? 0);

            // This month
            $monthRow = mysqli_fetch_assoc(mysqli_query($connection_database,
                "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev
                 FROM orders WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"));
            $monthOrders  = (int)($monthRow['cnt'] ?? 0);
            $monthRevenue = (float)($monthRow['rev'] ?? 0);
            ?>

            <!-- Today / Month quick stats -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="widget-stat card rounded-2" style="background:#e74c3c;">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-shopping-cart" style="color:#fff;font-size:22px;"></i></span>
                            <div class="media-body text-white">
                                <p class="mb-1">Today's Orders</p>
                                <h5 class="text-white"><?= $todayOrders ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="widget-stat card rounded-2" style="background:#27ae60;">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-inr" style="color:#fff;font-size:22px;"></i></span>
                            <div class="media-body text-white">
                                <p class="mb-1">Today's Revenue</p>
                                <h5 class="text-white">Rs <?= number_format($todayRevenue, 0) ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="widget-stat card rounded-2" style="background:#2980b9;">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-list-alt" style="color:#fff;font-size:22px;"></i></span>
                            <div class="media-body text-white">
                                <p class="mb-1">This Month Orders</p>
                                <h5 class="text-white"><?= $monthOrders ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="widget-stat card rounded-2" style="background:#8e44ad;">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-bar-chart" style="color:#fff;font-size:22px;"></i></span>
                            <div class="media-body text-white">
                                <p class="mb-1">This Month Revenue</p>
                                <h5 class="text-white">Rs <?= number_format($monthRevenue, 0) ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products + Payment Method -->
            <div class="col-xl-7 col-lg-7 col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="text-primary fw-bold mb-0"><i class="fa fa-trophy me-2" style="color:#f39c12;"></i>Top 5 Selling Products</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topProdLabels)): ?>
                            <p class="text-muted text-center mt-4">No sales data yet</p>
                        <?php else: ?>
                        <canvas id="topProductsChart" height="210"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-5 col-lg-5 col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="text-primary fw-bold mb-0"><i class="fa fa-credit-card me-2" style="color:#3498db;"></i>Payment Methods</h4>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <?php if (empty($pmLabels)): ?>
                            <p class="text-muted text-center mt-4">No data yet</p>
                        <?php else: ?>
                        <canvas id="paymentPieChart" height="200" style="max-width:260px;"></canvas>
                        <div id="pmLegend" class="mt-3 w-100"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <script>
            window.addEventListener('load', function(){
            (function(){
                if (typeof Chart === 'undefined') return;
                // ── Top Products horizontal bar ──────────────────────
                <?php if (!empty($topProdLabels)): ?>
                const tpCtx = document.getElementById('topProductsChart').getContext('2d');
                new Chart(tpCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($topProdLabels) ?>,
                        datasets: [
                            {
                                label: 'Units Sold',
                                data: <?= json_encode($topProdQty) ?>,
                                backgroundColor: ['#3498db','#2ecc71','#e74c3c','#f39c12','#9b59b6'],
                                borderRadius: 5,
                                borderSkipped: false,
                            },
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(ctx) {
                                        const revs = <?= json_encode($topProdRev) ?>;
                                        return 'Revenue: Rs ' + Number(revs[ctx.dataIndex]).toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } },
                            y: { ticks: { font: { size: 12 } } }
                        }
                    }
                });
                <?php endif; ?>

                // ── Payment methods pie ───────────────────────────────
                <?php if (!empty($pmLabels)): ?>
                const pmColors = ['#3498db','#2ecc71','#e74c3c','#f39c12','#9b59b6','#1abc9c'];
                const pmCtx = document.getElementById('paymentPieChart').getContext('2d');
                new Chart(pmCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($pmLabels) ?>,
                        datasets: [{
                            data: <?= json_encode($pmCounts) ?>,
                            backgroundColor: pmColors,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        cutout: '60%',
                        plugins: { legend: { display: false } },
                        animation: { animateScale: true }
                    }
                });
                // Build PM legend
                const pmLegend = document.getElementById('pmLegend');
                const pmLabelsArr = <?= json_encode($pmLabels) ?>;
                const pmCountsArr = <?= json_encode($pmCounts) ?>;
                const pmTotal = pmCountsArr.reduce((a,b)=>a+b,0);
                pmLabelsArr.forEach((lbl,i) => {
                    const pct = pmTotal > 0 ? Math.round(pmCountsArr[i]/pmTotal*100) : 0;
                    pmLegend.innerHTML += `<div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:12px;height:12px;border-radius:3px;background:${pmColors[i]};display:inline-block;flex-shrink:0;"></span>
                            <span style="font-size:12px;">${lbl}</span>
                        </div>
                        <span style="font-size:12px;font-weight:700;">${pmCountsArr[i]} <span class="text-muted">(${pct}%)</span></span>
                    </div>`;
                });
                <?php endif; ?>
            })();
            }); // end window load
            </script>

            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <!-- All Users Section -->
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-4">
                        <div class="card equal-height-card h-100">
                            <div class="card-header">
                                <h4 class="text-primary fw-bold mb-0">
                                    All Customer
                                </h4>
                            </div>
            
                            <!-- Scrollable Section -->
                            <div class="card-scroll py-3 px-2" style="height: 350px; overflow-y: auto;">
                                <div class="row g-2">
                                    <?php
                                    $customerQuery = selectQuery("SELECT * FROM customers ORDER BY id desc limit 50");
                                    if ($customerQuery['status'] == true) {
                                        foreach ($customerQuery['data'] as $sr => $data) {
                                            
                                            $customerID = $data['id'];
                                            $name = $data['name'];
                                            $email = $data['email'];
                                            $number = $data['number'];
                                            $username = $data['username'];
                                            $image = $data['image'];
                                            
                                            // Display image or placeholder
                                            $displayImage = '';
                                            if (!empty($image) && file_exists($image)) {
                                                $displayImage = '<img src="'.$image.'" alt="'.$name.'" class="rounded-circle" width="50" height="50">';
                                            } else {
                                                $displayImage = '<img src="images/placeholder.jpg" alt="No Image" class="rounded-circle" width="50" height="50">';
                                            }
                                            
                                            echo'
                                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-6">
                                                <a href="customers.php" target="_blank" class="text-decoration-none user-card">
                                                    <div class="jobs text-center p-2 d-flex flex-column align-items-center justify-content-between" 
                                                        style=" border: 1px solid #e0e0e0; border-radius: 10px; background: #f9f9f9;">
                                                        <p class="mb-1 mt-3">'.$displayImage.'</p>
                                                        <!-- Name -->
                                                        <h6 class="mb-0 mt-3 text-truncate w-100" style="font-size:11px;">
                                                           '.$name.'
                                                        </h6>
                                                        <!-- Mobile -->
                                                        <span class="text-primary mt-1 text-truncate d-block mb-2 w-100" style="font-size:11px;">
                                                            '.$number.'
                                                        </span>
                                                    </div>
                                                </a>
                                            </div>
                                            ';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- All Brand Section -->
                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-4">
                        <div class="card equal-height-card h-100">
                            <div class="card-header">
                                <h4 class="text-primary fw-bold mb-0">
                                    All Brand
                                </h4>
                            </div>
            
                            <!-- Scrollable Section -->
                            <div class="card-scroll py-3 px-2" style="height: 350px; overflow-y: auto;">
                                <div class="row g-2">
                                    <?php
                                        $brandQuery = selectQuery("SELECT * FROM brand ORDER BY id desc limit 50");
                                        if ($brandQuery['status'] == true) {
                                            foreach ($brandQuery['data'] as $sr => $data) {
                                                
                                                $brandID = $data['id'];
                                                $name = $data['name'];
                                                $image = $data['image'];
                                                
                                                echo'
                                                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-6">
                                                        <a href="add_brand.php" target="_blank" class="text-decoration-none user-card">
                                                            <div class="jobs text-center p-2 d-flex flex-column align-items-center justify-content-between" 
                                                                style=" border: 1px solid #e0e0e0; border-radius: 10px; background: #f9f9f9;">
                                                                <p class="mb-1 mt-3">
                                                                  <img src="images/brand/'.$image.'" class="rounded-circle" width="50" height="50">
                                                                </p>
                                                                <!-- Name -->
                                                                <h6 class="mb-3 mt-3 text-truncate w-100" style="font-size:11px;">
                                                                   '.$name.'
                                                                </h6>
                                                            </div>
                                                        </a>
                                                    </div>
                                                ';
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ═══════════════════════════════════════════════════
                 LOW STOCK ALERTS
            ═══════════════════════════════════════════════════ -->
            <?php
            $lowStockRes = mysqli_query($connection_database,
                "SELECT id, name, sku, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 10");
            $lowStockItems = [];
            if ($lowStockRes) {
                while ($r = mysqli_fetch_assoc($lowStockRes)) {
                    $lowStockItems[] = $r;
                }
            }
            if (!empty($lowStockItems)): ?>
            <div class="col-xl-12">
                <div class="card border-warning">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background:#fff8e1;">
                        <h4 class="fw-bold mb-0 text-warning">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Low Stock Alert
                            <span class="badge bg-warning text-dark ms-2" style="font-size:13px;"><?= count($lowStockItems) ?></span>
                        </h4>
                        <a href="product.php" target="_blank" class="btn btn-sm btn-warning">Manage Stock</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Stock Remaining</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockItems as $ls): ?>
                                    <tr>
                                        <td class="align-middle fw-semibold"><?= htmlspecialchars($ls['name']) ?></td>
                                        <td class="align-middle text-muted"><?= htmlspecialchars($ls['sku'] ?? '—') ?></td>
                                        <td class="align-middle">
                                            <?php if ((int)$ls['stock'] === 0): ?>
                                                <span class="badge bg-danger fs-6">OUT OF STOCK</span>
                                            <?php elseif ((int)$ls['stock'] <= 2): ?>
                                                <span class="badge bg-danger"><?= (int)$ls['stock'] ?> left</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><?= (int)$ls['stock'] ?> left</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <?php if ((int)$ls['stock'] === 0): ?>
                                                <span class="text-danger fw-bold"><i class="fa fa-times-circle"></i> Out of Stock</span>
                                            <?php else: ?>
                                                <span class="text-warning fw-bold"><i class="fa fa-exclamation-circle"></i> Critical Low</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <a href="edit_products.php?id=<?= (int)$ls['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Product Section -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="text-primary fw-bold mb-0">Recent Products</h4>
                        <small><a href="product.php" target="_blank" class="text-danger fw-bold">View all Products <i class="fa fa-external-link"></i></a></small>
                    </div>
                    <div class="card-body">
                        <div style="height:60vh;overflow:auto;">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><strong>Image</strong></th>
                                            <th><strong>Product</strong></th>
                                            <th><strong>Category</strong></th>
                                            <th><strong>Sub Category</strong></th>
                                            <th><strong>Product Type</strong></th>
                                            <th><strong>Action</strong></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $productQuery = selectQuery("SELECT * FROM products ORDER BY id desc limit 10");
                                            if ($productQuery['status'] == true) {
                                            foreach ($productQuery['data'] as $sr => $data) {
            
                                                $productID			= $data['id'];
                                                $serial_no			= $data['serial_no'];
                                                $sku			   	= $data['sku'];
                                                $name			   	= $data['name'];
                                                $category			= $data['category'];
                                                $sub_category	    = $data['sub_category'];
                                                $product_type	    = $data['product_type'];
                                                $image	            = $data['image'];
            
                                
                                                $catId             = intval($category);
                                                $catQuery          = selectQuery("SELECT name FROM category WHERE id = $catId");
                                                $category_name     = $catQuery['data'][0]['name'] ?? '—';

                                                $subCatId          = intval($sub_category);
                                                $subcatQuery       = selectQuery("SELECT name FROM subcategory WHERE id = $subCatId");
                                                $subcat_name       = $subcatQuery['data'][0]['name'] ?? '—';
            
                                                // Smart image resolution: Cloudinary URL takes priority, else local path
                                                $firstImage = '';
                                                if (!empty($image)) {
                                                    $imgs = explode(',', $image);
                                                    $firstImage = trim($imgs[0]);
                                                }
                                                if (!empty($firstImage) && (strpos($firstImage, 'http://') === 0 || strpos($firstImage, 'https://') === 0)) {
                                                    // Already a full Cloudinary/external URL
                                                    $firstImageUrl = $firstImage;
                                                } elseif (!empty($firstImage)) {
                                                    $firstImageUrl = "images/product/" . $firstImage;
                                                } else {
                                                    $firstImageUrl = "images/placeholder.jpg";
                                                }
            
                                            ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center">
                                                        <div style="height:85px; width:85px; border-radius:10px; border:1px solid #7F8CAA; overflow: hidden;">
                                                            <img src="<?php echo $firstImageUrl ?>" alt="Preview" class="img-fluid rounded" style="width:100%; height:100%; object-fit:cover;">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-middle">
                                                    <p class="mb-0"><strong>Name : </strong><?php echo $name ?></p>
                                                    <p class="mb-0 mt-0"><strong>Serial No : </strong><?php echo $serial_no ?></p>
                                                    <p class="mb-0 mt-0"><strong>SKU : </strong><?php echo $sku ?></p>
                                                </td>
                                                <td class="align-middle"><?php echo $category_name ?></td>
                                                <td class="align-middle"><?php echo $subcat_name ?></td>
                                                <td class="align-middle"><?php echo $product_type ?></td>
                                                <td class="align-middle">
                                                    <div class="d-flex">
                                                        <a href="edit_products.php?id=<?php echo $productID?>" class="btn btn-primary btn-sm me-1">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>
                                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal" 
                                                            data-id="<?php echo $productID?>" 
                                                            data-table="products" 
                                                            data-column="id">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                                  }
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
    </div>
</div>
<?php
include 'include/footer.php';
?>