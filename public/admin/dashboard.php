<?php
require_once __DIR__ . '/../../_init.php';

session_start();
require __DIR__ . '/auth.php';
requireLogin();

$title = "Dashboard";
require __DIR__ . '/layout.php';

// ===============================
// 🔹 MODERATOR METRICS (SAFE QUERY)
// ===============================
$moderatorStats = [];

try {
    $stmt = $pdo->query("
        SELECT 
            admin_user,
            COUNT(*) as total_actions,
            SUM(action = 'approved') as approvals,
            SUM(action = 'rejected') as rejections
        FROM moderation_logs
        GROUP BY admin_user
    ");

    $moderatorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<div style='color:red;'>Query Error: " . $e->getMessage() . "</div>";
}
?>

<h2>Analytics Dashboard</h2>

<!-- ===============================
     🔹 DEBUG OUTPUT (TEMPORARY)
================================ -->
<pre style="background:#111;color:#0f0;padding:10px;">
<?php print_r($moderatorStats); ?>
</pre>

<!-- ===============================
     🔹 BASIC METRICS (FROM API)
================================ -->
<div id="metrics"></div>

<!-- ===============================
     🔹 MODERATOR PERFORMANCE TABLE
================================ -->
<h3>Moderator Performance</h3>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Admin</th>
        <th>Total Actions</th>
        <th>Approved</th>
        <th>Rejected</th>
    </tr>

    <?php foreach ($moderatorStats as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['admin_user']) ?></td>
        <td><?= $row['total_actions'] ?></td>
        <td><?= $row['approvals'] ?></td>
        <td><?= $row['rejections'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<br>

<!-- ===============================
     🔹 CHARTS
================================ -->
<canvas id="trendChart" height="100"></canvas>
<canvas id="statusChart" height="100"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
fetch('analytics.php')
.then(r => r.json())
.then(data => {
    if (!data.ok) return;

    // ---- METRICS ----
    document.getElementById('metrics').innerHTML = `
        <p>Total: ${data.totals.total}</p>
        <p>Pending: ${data.totals.pending}</p>
        <p>Approved: ${data.totals.approved}</p>
        <p>Rejected: ${data.totals.rejected}</p>
    `;

    // ---- TREND CHART ----
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: data.trend.map(x => x.date),
            datasets: [{
                label: 'Submissions',
                data: data.trend.map(x => x.count)
            }]
        }
    });

    // ---- STATUS CHART ----
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: data.status.map(x => x.status),
            datasets: [{
                label: 'Count',
                data: data.status.map(x => x.count)
            }]
        }
    });
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>