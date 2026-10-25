<?php
require 'include/head.php';
session('WEBSITE-LOGIN');
global $connection_database;

// Ensure tables exist
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

// Handle actions
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'adjust_points') {
        $custId  = (int)$_POST['customer_id'];
        $points  = (float)$_POST['points'];
        $reason  = $connection_database->real_escape_string($_POST['reason'] ?? 'Admin adjustment');

        $row = mysqli_fetch_assoc(mysqli_query($connection_database,
            "SELECT * FROM loyalty_scores WHERE customer_id=$custId LIMIT 1"));
        $before = $row ? (float)$row['score'] : 0;
        $after  = max(0, min(100, $before + $points));

        if ($row) {
            $connection_database->query("UPDATE loyalty_scores SET score=$after WHERE customer_id=$custId");
        } else {
            $connection_database->query("INSERT INTO loyalty_scores (customer_id,score) VALUES ($custId,$after)");
        }
        $connection_database->query("INSERT INTO loyalty_transactions (customer_id,order_id,points_earned,score_before,score_after,description)
            VALUES ($custId, 0, $points, $before, $after, '$reason')");

        // Check winner
        if ($after >= 100) {
            $connection_database->query("UPDATE loyalty_scores SET is_winner=1 WHERE customer_id=$custId");
        }
        $msg = 'Points updated successfully!';
    }

    if ($action === 'mark_notified') {
        $custId = (int)$_POST['customer_id'];
        $connection_database->query("UPDATE loyalty_scores SET winner_notified=1 WHERE customer_id=$custId");
        $msg = 'Marked as notified!';
    }

    if ($action === 'reset_score') {
        $custId = (int)$_POST['customer_id'];
        $connection_database->query("UPDATE loyalty_scores SET score=0, total_orders=0, is_winner=0, winner_notified=0 WHERE customer_id=$custId");
        $connection_database->query("DELETE FROM loyalty_transactions WHERE customer_id=$custId");
        $msg = 'Score reset successfully!';
    }
}

// Summary stats
$statsRow = mysqli_fetch_assoc(mysqli_query($connection_database,
    "SELECT COUNT(*) AS total, SUM(is_winner) AS winners,
            AVG(score) AS avg_score, MAX(score) AS max_score
     FROM loyalty_scores"));

// All loyalty players with customer info
$playersRes = mysqli_query($connection_database,
    "SELECT ls.*, c.name, c.email, c.number FROM loyalty_scores ls
     LEFT JOIN customers c ON c.id = ls.customer_id
     ORDER BY ls.score DESC");
$players = [];
if ($playersRes) while ($r = mysqli_fetch_assoc($playersRes)) $players[] = $r;

// All customers for add-points dropdown
$custsRes = mysqli_query($connection_database, "SELECT id, name, email FROM customers ORDER BY name ASC");
$custs = [];
if ($custsRes) while ($r = mysqli_fetch_assoc($custsRes)) $custs[] = $r;
?>
<div class="content-body">
<div class="container-fluid">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show mt-3" role="alert">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mt-3">
    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
        <div class="widget-stat card bg-primary rounded-2">
            <div class="card-body p-4">
                <div class="media"><span class="me-3"><i class="fa fa-users"></i></span>
                <div class="media-body text-white"><p class="mb-1">Total Players</p>
                <h5 class="text-white"><?= (int)($statsRow['total'] ?? 0) ?></h5></div></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
        <div class="widget-stat card bg-success rounded-2">
            <div class="card-body p-4">
                <div class="media"><span class="me-3"><i class="fa fa-trophy"></i></span>
                <div class="media-body text-white"><p class="mb-1">Winners (100 pts)</p>
                <h5 class="text-white"><?= (int)($statsRow['winners'] ?? 0) ?></h5></div></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
        <div class="widget-stat card bg-warning rounded-2">
            <div class="card-body p-4">
                <div class="media"><span class="me-3"><i class="fa fa-bar-chart"></i></span>
                <div class="media-body text-white"><p class="mb-1">Avg Score</p>
                <h5 class="text-white"><?= number_format((float)($statsRow['avg_score'] ?? 0), 1) ?></h5></div></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
        <div class="widget-stat card bg-danger rounded-2">
            <div class="card-body p-4">
                <div class="media"><span class="me-3"><i class="fa fa-star"></i></span>
                <div class="media-body text-white"><p class="mb-1">Top Score</p>
                <h5 class="text-white"><?= number_format((float)($statsRow['max_score'] ?? 0), 0) ?></h5></div></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Adjust Points Form -->
    <div class="col-xl-4 col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="text-primary fw-bold mb-0"><i class="fa fa-pencil me-2"></i>Adjust Points</h4></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="adjust_points">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">— Choose Customer —</option>
                            <?php foreach ($custs as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Points to Add/Remove</label>
                        <input type="number" name="points" class="form-control" placeholder="e.g. 10 or -5" step="0.5" required>
                        <div class="form-text">Use negative number to remove points.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Admin bonus, correction, etc.">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fa fa-check me-1"></i>Update Points
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Players Table -->
    <div class="col-xl-8 col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="text-primary fw-bold mb-0"><i class="fa fa-list-ol me-2"></i>All Players</h4>
                <a href="<?= $url_domain ?>/leaderboard" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-external-link me-1"></i>View Public Leaderboard
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($players)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa fa-trophy" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>
                    No players yet — points are awarded automatically when orders are placed.
                </div>
                <?php else: ?>
                <div style="max-height:500px;overflow-y:auto;">
                <table class="table table-hover mb-0">
                    <thead style="position:sticky;top:0;background:#fff;z-index:1;">
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Score</th>
                            <th>Orders</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($players as $i => $p):
                        $rank  = $i + 1;
                        $pScore = (float)$p['score'];
                        $rankIcon = $rank===1 ? '<i class="fa fa-diamond" style="color:#0dcaf0;"></i>' : ($rank===2 ? '<i class="fa fa-star" style="color:#ffd700;"></i>' : ($rank===3 ? '<i class="fa fa-star" style="color:#c0c0c0;"></i>' : "#$rank"));
                    ?>
                    <tr class="<?= $p['is_winner'] ? 'table-warning' : '' ?>">
                        <td><strong><?= $rankIcon ?></strong></td>
                        <td>
                            <div class="fw-bold" style="font-size:.88rem;"><?= htmlspecialchars($p['name'] ?? '—') ?></div>
                            <div style="font-size:.76rem;color:#999;"><?= htmlspecialchars($p['email'] ?? '') ?></div>
                            <?php if ($p['number'] ?? ''): ?>
                            <div style="font-size:.76rem;color:#999;"><?= htmlspecialchars($p['number']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= number_format($pScore,0) ?>/100</strong>
                            <div style="height:6px;width:80px;background:#f0f0f0;border-radius:6px;margin-top:4px;overflow:hidden;">
                                <div style="height:100%;width:<?= min($pScore,100) ?>%;background:linear-gradient(90deg,#f9ca24,#f0932b);border-radius:6px;"></div>
                            </div>
                        </td>
                        <td><?= (int)$p['total_orders'] ?></td>
                        <td>
                            <?php if ($p['is_winner'] && !$p['winner_notified']): ?>
                                <span class="badge bg-danger"><i class="fa fa-trophy me-1"></i>Winner — Not Notified</span>
                            <?php elseif ($p['is_winner'] && $p['winner_notified']): ?>
                                <span class="badge bg-success"><i class="fa fa-check me-1"></i>Winner — Notified</span>
                            <?php elseif ($pScore >= 75): ?>
                                <span class="badge bg-warning text-dark">Almost There</span>
                            <?php elseif ($pScore >= 50): ?>
                                <span class="badge bg-info text-dark">Halfway</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Playing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if ($p['is_winner'] && !$p['winner_notified']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="mark_notified">
                                    <input type="hidden" name="customer_id" value="<?= $p['customer_id'] ?>">
                                    <button class="btn btn-success btn-sm" title="Mark Notified">
                                        <i class="fa fa-check"></i>
                                    </button>
                                </form>
                                <a href="https://wa.me/<?= preg_replace('/\D/','',$p['number']??'') ?>" target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff;" title="WhatsApp">
                                    <i class="fa fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                                <button class="btn btn-outline-danger btn-sm" title="Reset Score"
                                    onclick="if(confirm('Reset this player\'s score to 0?')){
                                        var f=document.createElement('form');f.method='POST';
                                        f.innerHTML='<input name=action value=reset_score><input name=customer_id value=<?= $p['customer_id'] ?>>';
                                        document.body.appendChild(f);f.submit();}">
                                    <i class="fa fa-refresh"></i>
                                </button>
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
</div>

</div>
</div>
<?php include 'include/footer.php'; ?>
