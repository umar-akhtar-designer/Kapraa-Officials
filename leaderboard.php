<?php
include 'include/head.php';
include 'include/navbar.php';
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

// Fetch top real users (up to 10)
$realRes = mysqli_query($connection_database,
    "SELECT ls.*, c.name, c.image FROM loyalty_scores ls
     LEFT JOIN customers c ON c.id = ls.customer_id
     ORDER BY ls.score DESC LIMIT 10");
$realUsers = [];
if ($realRes) while ($r = mysqli_fetch_assoc($realRes)) $realUsers[] = $r;

// Demo seed entries to show when no real data or pad to 5
$demoUsers = [
    ['name'=>'Fatima Malik',   'score'=>87, 'total_orders'=>9,  'image'=>''],
    ['name'=>'Zara Ahmed',     'score'=>72, 'total_orders'=>7,  'image'=>''],
    ['name'=>'Sara Khan',      'score'=>65, 'total_orders'=>6,  'image'=>''],
    ['name'=>'Nadia Hussain',  'score'=>45, 'total_orders'=>5,  'image'=>''],
    ['name'=>'Ayesha Tariq',   'score'=>30, 'total_orders'=>3,  'image'=>''],
];

// Merge: real users first, then demo to pad to 5
$leaderboard = $realUsers;
if (count($leaderboard) < 5) {
    $needed = 5 - count($leaderboard);
    foreach (array_slice($demoUsers, 0, $needed) as $d) {
        $d['is_winner'] = 0; $d['customer_id'] = 0;
        $leaderboard[] = $d;
    }
}

// Logged-in user data
$myRank = null; $myScore = null; $myName = null;
if (isset($_SESSION['WEBSITE-LOGINSS'])) {
    $uid = (int)$_SESSION['WEBSITE-LOGINSS'];
    $myCust = mysqli_fetch_assoc(mysqli_query($connection_database, "SELECT name FROM customers WHERE id=$uid LIMIT 1"));
    $myLoy  = mysqli_fetch_assoc(mysqli_query($connection_database, "SELECT score FROM loyalty_scores WHERE customer_id=$uid LIMIT 1"));
    $myName  = $myCust['name'] ?? '';
    $myScore = $myLoy ? (float)$myLoy['score'] : 0;
    $rankRow = mysqli_fetch_assoc(mysqli_query($connection_database,
        "SELECT COUNT(*)+1 AS r FROM loyalty_scores WHERE score > $myScore"));
    $myRank = (int)($rankRow['r'] ?? 1);
}
?>
<style>
.lb-page { padding: 50px 0 80px; background: linear-gradient(180deg, #0f0c29 0%, #1a1a2e 60%, #f5f5f5 60%); min-height: 80vh; }

/* Header */
.lb-header { text-align: center; padding-bottom: 40px; }
.lb-title { font-size: 2.4rem; font-weight: 900; color: #fff; letter-spacing: 1px; margin-bottom: 6px; }
.lb-sub   { color: rgba(255,255,255,.55); font-size: .92rem; }

/* Top 3 podium */
.podium-wrap { display: flex; align-items: flex-end; justify-content: center; gap: 16px; margin-bottom: 32px; }
.podium-item { text-align: center; flex: 1; max-width: 180px; }
.podium-avatar {
    width: 72px; height: 72px; border-radius: 50%; object-fit: cover;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 900; color: #fff; margin: 0 auto 8px;
}
.podium-name  { font-size: .8rem; font-weight: 700; color: #fff; margin-bottom: 4px; text-shadow: 0 1px 4px rgba(0,0,0,.5); }
.podium-score { font-size: .75rem; color: rgba(255,255,255,.65); margin-bottom: 8px; }
.podium-block {
    border-radius: 10px 10px 0 0; display: flex; align-items: flex-start; justify-content: center;
    padding-top: 14px; font-size: 1.6rem; font-weight: 900; color: rgba(0,0,0,.3);
}
.podium-1 .podium-block { height: 110px; background: linear-gradient(180deg,#b9f2ff,#0dcaf0); }
.podium-2 .podium-block { height: 80px;  background: linear-gradient(180deg,#ffd700,#e6a800); }
.podium-3 .podium-block { height: 60px;  background: linear-gradient(180deg,#e0e0e0,#9e9e9e); }

.podium-crown { font-size: 1.6rem; display: block; margin-bottom: 4px; }

/* List table */
.lb-card { background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.08); overflow: hidden; }
.lb-table { width: 100%; border-collapse: collapse; }
.lb-table thead th { background: #1a1a2e; color: #fff; padding: 12px 18px; font-size: .8rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
.lb-table tbody tr { border-bottom: 1px solid #f5f5f5; transition: background .15s; }
.lb-table tbody tr:last-child { border-bottom: none; }
.lb-table tbody tr:hover { background: #fafafa; }
.lb-table td { padding: 12px 18px; vertical-align: middle; }

.rank-badge {
    width: 36px; height: 36px; border-radius: 50%; display: inline-flex;
    align-items: center; justify-content: center; font-weight: 900; font-size: .85rem;
}
.rank-1 { background: linear-gradient(135deg,#b9f2ff,#0dcaf0); color: #0f3460; }
.rank-2 { background: linear-gradient(135deg,#ffd700,#e6a800); color: #5c3c00; }
.rank-3 { background: linear-gradient(135deg,#e0e0e0,#9e9e9e); color: #333; }
.rank-n { background: #f0f0f0; color: #666; }

.player-av {
    width: 38px; height: 38px; border-radius: 50%; object-fit: cover;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .95rem; color: #fff; flex-shrink: 0;
}
.player-name { font-size: .9rem; font-weight: 600; color: #111; }

/* Score bar inside table */
.score-track { height: 8px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin-top: 3px; width: 120px; }
.score-fill  { height: 100%; border-radius: 10px; background: linear-gradient(90deg, #f9ca24, #f0932b); }

/* My rank box */
.my-rank-box {
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    border-radius: 14px; padding: 20px 24px; color: #fff; margin-top: 24px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.my-rank-box strong { font-size: 1.1rem; }
.my-rank-box span   { font-size: .85rem; color: rgba(255,255,255,.6); }
</style>

<div class="lb-page">
<div class="container">

    <!-- Header -->
    <div class="lb-header">
        <div class="lb-title"><i class="fa fa-trophy me-2" style="color:#f9ca24;"></i>Score Leaderboard</div>
        <div class="lb-sub">Top players competing for the Kapraa Prize Dress</div>
    </div>

    <!-- Podium (top 3) -->
    <?php
    $p1 = $leaderboard[0] ?? null;
    $p2 = $leaderboard[1] ?? null;
    $p3 = $leaderboard[2] ?? null;
    $avColors = ['#e74c3c','#3498db','#9b59b6','#e67e22','#1abc9c','#e91e63'];
    function avBg($name, $colors) {
        return $colors[abs(crc32($name)) % count($colors)];
    }
    ?>
    <div class="podium-wrap">
        <!-- Rank 2 -->
        <?php if ($p2): $av = strtoupper(mb_substr($p2['name'],0,1)); ?>
        <div class="podium-item podium-2">
            <span class="podium-crown"><i class="fa fa-star" style="color:#c0c0c0;"></i></span>
            <div class="podium-avatar" style="background:<?= avBg($p2['name'],$avColors) ?>;"><?= htmlspecialchars($av) ?></div>
            <div class="podium-name"><?= htmlspecialchars($p2['name']) ?></div>
            <div class="podium-score"><?= number_format((float)$p2['score'],0) ?> pts</div>
            <div class="podium-block">2</div>
        </div>
        <?php endif; ?>
        <!-- Rank 1 -->
        <?php if ($p1): $av = strtoupper(mb_substr($p1['name'],0,1)); ?>
        <div class="podium-item podium-1">
            <span class="podium-crown"><i class="fa fa-diamond" style="color:#b9f2ff;"></i></span>
            <div class="podium-avatar" style="background:<?= avBg($p1['name'],$avColors) ?>;border:3px solid #f9ca24;"><?= htmlspecialchars($av) ?></div>
            <div class="podium-name"><?= htmlspecialchars($p1['name']) ?></div>
            <div class="podium-score"><?= number_format((float)$p1['score'],0) ?> pts</div>
            <div class="podium-block">1</div>
        </div>
        <?php endif; ?>
        <!-- Rank 3 -->
        <?php if ($p3): $av = strtoupper(mb_substr($p3['name'],0,1)); ?>
        <div class="podium-item podium-3">
            <span class="podium-crown"><i class="fa fa-star" style="color:#cd7f32;"></i></span>
            <div class="podium-avatar" style="background:<?= avBg($p3['name'],$avColors) ?>;"><?= htmlspecialchars($av) ?></div>
            <div class="podium-name"><?= htmlspecialchars($p3['name']) ?></div>
            <div class="podium-score"><?= number_format((float)$p3['score'],0) ?> pts</div>
            <div class="podium-block">3</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Full list -->
    <div class="lb-card">
        <table class="lb-table">
            <thead>
                <tr>
                    <th style="width:60px;">Rank</th>
                    <th>Player</th>
                    <th>Score</th>
                    <th>Orders</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($leaderboard as $i => $player):
                $rank = $i + 1;
                $pScore = (float)$player['score'];
                $pName  = htmlspecialchars($player['name'] ?? 'Player');
                $avLetter = strtoupper(mb_substr($player['name'] ?? 'P', 0, 1));
                $avBgColor = avBg($player['name'] ?? '', $avColors);
                $rankClass = $rank === 1 ? 'rank-1' : ($rank === 2 ? 'rank-2' : ($rank === 3 ? 'rank-3' : 'rank-n'));
                $rankIcon  = $rank === 1 ? '<i class="fa fa-diamond"></i>' : ($rank === 2 ? '<i class="fa fa-star" style="color:#ffd700;"></i>' : ($rank === 3 ? '<i class="fa fa-star" style="color:#c0c0c0;"></i>' : $rank));
            ?>
            <tr>
                <td><span class="rank-badge <?= $rankClass ?>"><?= $rankIcon ?></span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="player-av" style="background:<?= $avBgColor ?>;"><?= $avLetter ?></div>
                        <div class="player-name"><?= $pName ?></div>
                    </div>
                </td>
                <td>
                    <strong style="font-size:.95rem;"><?= number_format($pScore, 0) ?></strong>
                    <span style="color:#aaa;font-size:.8rem;">/100</span>
                    <div class="score-track"><div class="score-fill" style="width:<?= min($pScore,100) ?>%"></div></div>
                </td>
                <td style="color:#666;font-size:.88rem;"><?= (int)($player['total_orders'] ?? 0) ?> orders</td>
                <td>
                    <?php if ($pScore >= 100): ?>
                        <span class="badge" style="background:#f0932b;color:#fff;font-size:.75rem;border-radius:20px;padding:4px 10px;"><i class="fa fa-trophy me-1"></i>Winner</span>
                    <?php elseif ($pScore >= 75): ?>
                        <span class="badge" style="background:#dcfce7;color:#15803d;font-size:.75rem;border-radius:20px;padding:4px 10px;">Almost There!</span>
                    <?php elseif ($pScore >= 50): ?>
                        <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:.75rem;border-radius:20px;padding:4px 10px;">Halfway</span>
                    <?php else: ?>
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.75rem;border-radius:20px;padding:4px 10px;">Playing</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- My rank row (logged in) -->
        <?php if ($myRank !== null): ?>
        <div class="my-rank-box" style="margin:0;border-radius:0;">
            <div>
                <strong>Your Rank: #<?= $myRank ?></strong><br>
                <span><?= htmlspecialchars($myName) ?> &middot; <?= number_format($myScore,0) ?> pts</span>
            </div>
            <a href="<?= $url_domain ?>/loyalty" class="btn btn-sm" style="background:#f9ca24;color:#1a1a1a;font-weight:800;border-radius:20px;">View My Score</a>
        </div>
        <?php else: ?>
        <div style="background:#f8f8f8;padding:18px 24px;text-align:center;font-size:.88rem;color:#666;">
            <i class="fa fa-lock me-1"></i> <a href="<?= $url_domain ?>/login?redirect=leaderboard" style="color:#111;font-weight:700;">Login</a> to see your rank on the leaderboard
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<?php include 'include/footer.php'; ?>
