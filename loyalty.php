<?php
include 'include/head.php';
include 'include/navbar.php';

global $connection_database;

// Create tables if not exist
$connection_database->query("CREATE TABLE IF NOT EXISTS loyalty_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    score DECIMAL(6,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    is_winner TINYINT(1) DEFAULT 0,
    winner_notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer (customer_id)
)");
$connection_database->query("CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_id INT NOT NULL,
    points_earned DECIMAL(5,2) DEFAULT 0,
    score_before DECIMAL(5,2) DEFAULT 0,
    score_after DECIMAL(5,2) DEFAULT 0,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Require login
if (!isset($_SESSION['WEBSITE-LOGINSS'])) {
    header('Location: ' . $url_domain . '/login?redirect=loyalty');
    exit;
}
$userId = (int)$_SESSION['WEBSITE-LOGINSS'];

// Fetch customer
$custRow = mysqli_fetch_assoc(mysqli_query($connection_database,
    "SELECT * FROM customers WHERE id=$userId LIMIT 1"));
if (!$custRow) {
    header('Location: ' . $url_domain . '/index');
    exit;
}

// Fetch loyalty score
$scoreRow = mysqli_fetch_assoc(mysqli_query($connection_database,
    "SELECT * FROM loyalty_scores WHERE customer_id=$userId LIMIT 1"));
$score       = $scoreRow ? (float)$scoreRow['score'] : 0;
$totalOrders = $scoreRow ? (int)$scoreRow['total_orders'] : 0;
$isWinner    = $scoreRow ? (int)$scoreRow['is_winner'] : 0;

// Fetch transaction history
$txRes = mysqli_query($connection_database,
    "SELECT lt.*, o.order_number FROM loyalty_transactions lt
     LEFT JOIN orders o ON o.id = lt.order_id
     WHERE lt.customer_id = $userId ORDER BY lt.created_at DESC LIMIT 20");
$transactions = [];
if ($txRes) while ($r = mysqli_fetch_assoc($txRes)) $transactions[] = $r;

// Leaderboard rank
$rankRow = mysqli_fetch_assoc(mysqli_query($connection_database,
    "SELECT COUNT(*)+1 AS my_rank FROM loyalty_scores WHERE score > $score"));
$myRank = (int)($rankRow['my_rank'] ?? 1);
?>
<style>
.loy-page { padding: 50px 0 80px; background: #f5f5f5; min-height: 80vh; }

/* Hero score card */
.loy-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 20px; padding: 36px 32px; color: #fff; position: relative; overflow: hidden; margin-bottom: 28px;
}
.loy-hero::before {
    content: ''; position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px; background: rgba(255,215,0,.08); border-radius: 50%;
}
.loy-hero::after {
    content: ''; position: absolute; bottom: -40px; left: -40px;
    width: 160px; height: 160px; background: rgba(255,255,255,.04); border-radius: 50%;
}
.loy-user-name { font-size: 1.5rem; font-weight: 800; margin-bottom: 4px; }
.loy-user-sub  { font-size: .85rem; color: rgba(255,255,255,.6); margin-bottom: 24px; }
.loy-rank-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px; padding: 5px 14px; font-size: .8rem; font-weight: 700; margin-bottom: 20px;
}

/* Progress bar */
.loy-progress-wrap { margin-bottom: 16px; }
.loy-progress-labels { display: flex; justify-content: space-between; font-size: .75rem; color: rgba(255,255,255,.5); margin-bottom: 6px; }
.loy-progress-track {
    height: 18px; background: rgba(255,255,255,.12); border-radius: 20px; overflow: hidden; position: relative;
}
.loy-progress-fill {
    height: 100%; border-radius: 20px; transition: width 1.8s cubic-bezier(.25,.8,.25,1);
    background: linear-gradient(90deg, #f9ca24, #f0932b, #eb4d4b);
    position: relative; min-width: 4px;
}
.loy-progress-fill::after {
    content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.3) 50%, transparent 100%);
    animation: shimmer 2s infinite;
}
@keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }

.loy-score-val { font-size: 3rem; font-weight: 900; line-height: 1; }
.loy-score-sub { font-size: .85rem; color: rgba(255,255,255,.6); margin-top: 4px; }

.loy-milestones { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }
.loy-milestone {
    flex: 1; min-width: 80px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    border-radius: 10px; padding: 8px 10px; text-align: center; font-size: .72rem;
}
.loy-milestone.reached { background: rgba(249,202,36,.15); border-color: #f9ca24; }
.loy-milestone i { display: block; font-size: 1.2rem; margin-bottom: 3px; }

/* Winner banner */
.winner-banner {
    background: linear-gradient(135deg, #f9ca24, #f0932b);
    border-radius: 16px; padding: 28px 24px; text-align: center; margin-bottom: 28px;
    animation: winPulse 2s ease-in-out infinite alternate;
}
@keyframes winPulse { 0%{box-shadow:0 0 20px rgba(249,202,36,.4)} 100%{box-shadow:0 0 50px rgba(249,202,36,.8)} }
.winner-banner h3 { font-size: 1.5rem; font-weight: 900; color: #1a1a1a; margin-bottom: 8px; }
.winner-banner p  { color: #333; margin: 0; font-size: .9rem; }

/* Cards */
.loy-card { background: #fff; border-radius: 14px; border: 1.5px solid #eee; overflow: hidden; margin-bottom: 20px; }
.loy-card-head { padding: 14px 20px; border-bottom: 1px solid #f0f0f0; font-weight: 800; font-size: .88rem; color: #111; }
.loy-card-body { padding: 16px 20px; }

/* How to earn */
.earn-row { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
.earn-row:last-child { border-bottom: none; }
.earn-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.earn-info { flex: 1; }
.earn-info strong { display: block; font-size: .88rem; color: #111; }
.earn-info span   { font-size: .78rem; color: #999; }
.earn-pts { font-size: 1.1rem; font-weight: 900; color: #f0932b; white-space: nowrap; }

/* Transaction history */
.tx-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f5f5f5; }
.tx-row:last-child { border-bottom: none; }
.tx-desc { font-size: .83rem; color: #444; }
.tx-date { font-size: .73rem; color: #aaa; margin-top: 2px; }
.tx-pts  { font-size: .95rem; font-weight: 800; color: #27ae60; white-space: nowrap; }

.no-tx { text-align: center; padding: 30px; color: #bbb; font-size: .88rem; }
</style>

<div class="loy-page">
<div class="container">
<div class="row">
<div class="col-lg-8">

    <!-- Winner Banner -->
    <?php if ($isWinner): ?>
    <div class="winner-banner">
        <div style="font-size:3rem;"><i class="fa fa-trophy" style="color:#1a1a1a;"></i></div>
        <h3>Congratulations <?= htmlspecialchars($custRow['name']) ?>!</h3>
        <p>You've reached <strong>100 points</strong>! Kapraa will contact you within 2–3 days to deliver your prize dress.<br>
        No reply? <a href="https://wa.me/923209815213" target="_blank" style="color:#1a1a1a;font-weight:800;">WhatsApp Us</a></p>
    </div>
    <?php endif; ?>

    <!-- Hero Score Card -->
    <div class="loy-hero">
        <div style="position:relative;z-index:2;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
                <div>
                    <div class="loy-user-name"><?= htmlspecialchars($custRow['name']) ?></div>
                    <div class="loy-user-sub"><?= htmlspecialchars($custRow['email']) ?></div>
                    <div class="loy-rank-badge">
                        <?php if ($myRank === 1): ?>
                            <i class="fa fa-diamond" style="color:#b9f2ff;"></i> #1 Diamond Champion
                        <?php elseif ($myRank === 2): ?>
                            <i class="fa fa-star" style="color:#ffd700;"></i> #2 Gold Member
                        <?php elseif ($myRank === 3): ?>
                            <i class="fa fa-star" style="color:#c0c0c0;"></i> #3 Silver Member
                        <?php else: ?>
                            <i class="fa fa-user"></i> Rank #<?= $myRank ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div class="loy-score-val"><?= number_format($score, 0) ?><span style="font-size:1.2rem;color:rgba(255,255,255,.5);">/100</span></div>
                    <div class="loy-score-sub"><?= $totalOrders ?> order<?= $totalOrders != 1 ? 's' : '' ?> placed</div>
                </div>
            </div>

            <div class="loy-progress-wrap" style="margin-top:20px;">
                <div class="loy-progress-labels">
                    <span>0</span><span>25</span><span>50</span><span>75</span><span>100 <i class="fa fa-trophy"></i></span>
                </div>
                <div class="loy-progress-track">
                    <div class="loy-progress-fill" id="loyFill" style="width:0%"></div>
                </div>
            </div>

            <div class="loy-milestones">
                <div class="loy-milestone <?= $score >= 25 ? 'reached' : '' ?>">
                    <i class="fa fa-star"></i>25 pts<br><span style="font-size:.65rem;color:rgba(255,255,255,.5);">Bronze</span>
                </div>
                <div class="loy-milestone <?= $score >= 50 ? 'reached' : '' ?>">
                    <i class="fa fa-star"></i>50 pts<br><span style="font-size:.65rem;color:rgba(255,255,255,.5);">Silver</span>
                </div>
                <div class="loy-milestone <?= $score >= 75 ? 'reached' : '' ?>">
                    <i class="fa fa-star"></i>75 pts<br><span style="font-size:.65rem;color:rgba(255,255,255,.5);">Gold</span>
                </div>
                <div class="loy-milestone <?= $score >= 100 ? 'reached' : '' ?>">
                    <i class="fa fa-trophy"></i>100 pts<br><span style="font-size:.65rem;color:rgba(255,255,255,.5);">PRIZE!</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="loy-card">
        <div class="loy-card-head"><i class="fa fa-history me-2"></i>Points History</div>
        <div class="loy-card-body">
            <?php if (empty($transactions)): ?>
            <div class="no-tx"><i class="fa fa-shopping-bag" style="font-size:2rem;display:block;margin-bottom:10px;"></i>No points yet — place your first order to start!</div>
            <?php else: ?>
            <?php foreach ($transactions as $tx): ?>
            <div class="tx-row">
                <div>
                    <div class="tx-desc"><?= htmlspecialchars($tx['description']) ?></div>
                    <div class="tx-date"><?= date('d M Y, h:i A', strtotime($tx['created_at'])) ?>
                        <?php if ($tx['order_number']): ?> &middot; <?= htmlspecialchars($tx['order_number']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="tx-pts">+<?= number_format((float)$tx['points_earned'], 0) ?> pts</div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
<div class="col-lg-4">

    <!-- How to Earn -->
    <div class="loy-card">
        <div class="loy-card-head"><i class="fa fa-question-circle me-2"></i>How to Earn Points</div>
        <div class="loy-card-body">
            <div class="earn-row">
                <div class="earn-icon" style="background:#fff3cd;"><i class="fa fa-shopping-bag" style="color:#f0932b;"></i></div>
                <div class="earn-info"><strong>Buy Any Product</strong><span>Single item order</span></div>
                <div class="earn-pts">+5 pts</div>
            </div>
            <div class="earn-row">
                <div class="earn-icon" style="background:#d1fae5;"><i class="fa fa-cube" style="color:#059669;"></i></div>
                <div class="earn-info"><strong>Buy a Suit / Set</strong><span>Multi-item order</span></div>
                <div class="earn-pts">+15 pts</div>
            </div>
            <div class="earn-row">
                <div class="earn-icon" style="background:#dbeafe;"><i class="fa fa-bolt" style="color:#2563eb;"></i></div>
                <div class="earn-info"><strong>50–80 pts</strong><span>+2 pts per order</span></div>
                <div class="earn-pts">+2 pts</div>
            </div>
            <div class="earn-row">
                <div class="earn-icon" style="background:#ede9fe;"><i class="fa fa-fire" style="color:#7c3aed;"></i></div>
                <div class="earn-info"><strong>80–100 pts</strong><span>+1 pt per order</span></div>
                <div class="earn-pts">+1 pt</div>
            </div>
            <div class="earn-row">
                <div class="earn-icon" style="background:#fef2f2;"><i class="fa fa-trophy" style="color:#dc2626;"></i></div>
                <div class="earn-info"><strong>Reach 100 Points</strong><span>Win a FREE Dress!</span></div>
                <div class="earn-pts" style="color:#dc2626;"><i class="fa fa-trophy"></i></div>
            </div>
        </div>
    </div>

    <!-- Prize Info -->
    <div class="loy-card" style="background:linear-gradient(135deg,#fff9db,#fff3cd);border-color:#fde68a;">
        <div class="loy-card-body" style="text-align:center;padding:24px;">
            <div style="font-size:3rem;"><i class="fa fa-gift" style="color:#e6a800;"></i></div>
            <h6 style="font-weight:800;margin:10px 0 6px;">The Prize</h6>
            <p style="font-size:.85rem;color:#666;margin:0;">The winner gets a <strong>FREE premium dress</strong> from Kapraa. We'll contact you within 2–3 days. Not heard from us? Message us on WhatsApp!</p>
            <a href="https://wa.me/923209815213" target="_blank" class="btn btn-sm mt-3" style="background:#25d366;color:#fff;border-radius:20px;font-weight:700;"><i class="fa fa-whatsapp me-1"></i>WhatsApp Us</a>
        </div>
    </div>

    <!-- Leaderboard teaser -->
    <div class="loy-card">
        <div class="loy-card-head"><i class="fa fa-list-ol me-2"></i>Leaderboard</div>
        <div class="loy-card-body" style="text-align:center;padding:20px;">
            <p style="font-size:.85rem;color:#666;">See where you stand against all players</p>
            <a href="<?= $url_domain ?>/leaderboard" class="btn btn-sm" style="background:#111;color:#fff;border-radius:20px;font-weight:700;"><i class="fa fa-trophy me-1"></i>View Leaderboard</a>
        </div>
    </div>

</div>
</div>
</div>
</div>

<script>
// Animate progress fill on load
window.addEventListener('load', function() {
    setTimeout(function() {
        document.getElementById('loyFill').style.width = '<?= min($score, 100) ?>%';
    }, 400);
});
</script>

<?php include 'include/footer.php'; ?>
