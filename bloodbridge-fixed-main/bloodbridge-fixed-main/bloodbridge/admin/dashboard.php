<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["admin"]);

// Build the system totals from live records so the overview stays current.
$counts = [];
$count_queries = [
    "donors" => "SELECT COUNT(*) AS total FROM donors",
    "recipients" => "SELECT COUNT(*) AS total FROM users WHERE role='recipient'",
    "hospitals" => "SELECT COUNT(*) AS total FROM hospitals",
    "pending" => "SELECT COUNT(*) AS total FROM users WHERE status='pending' AND role<>'admin'",
    "requests" => "SELECT COUNT(*) AS total FROM requests",
    "units" => "SELECT COALESCE(SUM(units),0) AS total FROM inventory",
    "donations" => "SELECT COALESCE(SUM(units),0) AS total FROM donation_history"
];
foreach ($count_queries as $key => $query) {
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("BloodBridge admin metric `$key` failed: " . mysqli_error($conn));
        http_response_code(503);
        exit("Dashboard metrics are temporarily unavailable. Please try again.");
    }
    $counts[$key] = (int)(mysqli_fetch_assoc($result)["total"] ?? 0);
}

// These role totals feed the community distribution chart below.
$role_result = mysqli_query($conn, "SELECT role,COUNT(*) AS total FROM users WHERE role<>'admin' GROUP BY role");
$roles = ["donor" => 0, "recipient" => 0, "hospital" => 0];
while ($row = mysqli_fetch_assoc($role_result)) {
    if (isset($roles[$row["role"]])) $roles[$row["role"]] = (int)$row["total"];
}
$role_total = max(1, array_sum($roles));
$role_colors = ["donor" => "#8d102d", "recipient" => "#d46b7d", "hospital" => "#d9a25f"];
$angle = 0;
$segments = [];
foreach ($roles as $role => $amount) {
    $next = $angle + ($amount / $role_total * 360);
    $segments[] = $role_colors[$role] . " " . $angle . "deg " . $next . "deg";
    $angle = $next;
}

$group_result = mysqli_query($conn, "SELECT blood_group,SUM(units) AS units FROM inventory GROUP BY blood_group ORDER BY FIELD(blood_group,'O+','O-','A+','A-','B+','B-','AB+','AB-')");
$blood_stock = [];
while ($row = mysqli_fetch_assoc($group_result)) $blood_stock[] = ["group" => $row["blood_group"], "units" => (int)$row["units"]];
$max_stock = max(1, max(array_column($blood_stock, "units") ?: [1]));

// Prepare a complete seven-day series, including days with no activity.
$start = new DateTimeImmutable("today -6 days");
$labels = [];
$series = ["requests" => [], "donations" => []];
for ($offset = 0; $offset < 7; $offset++) {
    $day = $start->modify("+$offset days");
    $labels[] = $day->format("M j");
    $series["requests"][$day->format("Y-m-d")] = 0;
    $series["donations"][$day->format("Y-m-d")] = 0;
}
$trend = mysqli_query(
    $conn,
    "SELECT DATE(created_at) AS day,COUNT(*) AS total FROM requests
     WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(created_at)"
);
while ($row = mysqli_fetch_assoc($trend)) {
    if (isset($series["requests"][$row["day"]])) $series["requests"][$row["day"]] = (int)$row["total"];
}
$trend = mysqli_query(
    $conn,
    "SELECT date AS day,COALESCE(SUM(units),0) AS total FROM donation_history
     WHERE date>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY date"
);
while ($row = mysqli_fetch_assoc($trend)) {
    if (isset($series["donations"][$row["day"]])) $series["donations"][$row["day"]] = (int)$row["total"];
}
$chart_max = max(1, max($series["requests"]), max($series["donations"]));
$request_points = [];
$donation_points = [];
$chart_labels = [];
$index = 0;
foreach ($series["requests"] as $day => $value) {
    $x = 42 + $index * 96;
    $request_points[] = $x . "," . (142 - (int)round($value / $chart_max * 112));
    $donation_points[] = $x . "," . (142 - (int)round($series["donations"][$day] / $chart_max * 112));
    $chart_labels[] = ["x" => $x, "label" => $labels[$index]];
    $index++;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id,name,email,role,created_at FROM users
     WHERE status='pending' AND role<>'admin' ORDER BY created_at ASC LIMIT 6"
);
mysqli_stmt_execute($stmt);
$pending = mysqli_stmt_get_result($stmt);
$page_title = "Admin dashboard";
$app_role = "admin";
$app_active = "dashboard";
$app_asset_base = "../";
require "../includes/role_header.php";
?>
<main class="admin-dashboard">
    <div class="admin-heading">
        <div><p class="page-label">System overview</p><h1 class="role-title">Admin dashboard</h1>
            <p class="role-subtitle">Monitor blood supply, requests and accounts across the network.</p></div>
        <a class="app-button" href="users.php"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Review accounts (<?= $counts["pending"] ?>)</a>
    </div>
    <section class="admin-metrics" aria-label="System totals">
        <article class="admin-metric"><span class="admin-metric__icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span><span class="admin-metric__label">Registered donors</span><strong><?= $counts["donors"] ?></strong><small><?= $roles["donor"] ?> active and pending accounts</small></article>
        <article class="admin-metric"><span class="admin-metric__icon"><i class="fa-solid fa-hospital" aria-hidden="true"></i></span><span class="admin-metric__label">Partner hospitals</span><strong><?= $counts["hospitals"] ?></strong><small><?= $roles["hospital"] ?> hospital accounts</small></article>
        <article class="admin-metric"><span class="admin-metric__icon"><i class="fa-solid fa-droplet" aria-hidden="true"></i></span><span class="admin-metric__label">Units in inventory</span><strong><?= $counts["units"] ?></strong><small><?= $counts["donations"] ?> units donated</small></article>
        <article class="admin-metric"><span class="admin-metric__icon"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span><span class="admin-metric__label">Blood requests</span><strong><?= $counts["requests"] ?></strong><small><?= $counts["recipients"] ?> registered recipients</small></article>
    </section>

    <section class="admin-charts" aria-label="System analytics">
        <article class="admin-chart-card admin-chart-card--wide">
            <div class="admin-chart-card__heading"><div><p class="panel-eyebrow">Last seven days</p><h2>Network activity</h2></div>
                <div class="chart-legend"><span><i class="legend-dot legend-dot--requests"></i>Requests</span><span><i class="legend-dot legend-dot--donations"></i>Units donated</span></div>
            </div>
            <div class="admin-line-chart">
                <svg viewBox="0 0 660 185" role="img" aria-label="Line chart showing daily blood requests and units donated">
                    <?php for ($grid = 30; $grid <= 142; $grid += 56): ?><line x1="42" y1="<?= $grid ?>" x2="618" y2="<?= $grid ?>" class="chart-grid-line" /><?php endfor; ?>
                    <polyline class="chart-line chart-line--requests" points="<?= safe(implode(" ", $request_points)) ?>" />
                    <polyline class="chart-line chart-line--donations" points="<?= safe(implode(" ", $donation_points)) ?>" />
                    <?php foreach ($chart_labels as $label): ?><text x="<?= (int)$label["x"] ?>" y="171" class="chart-axis-label"><?= safe($label["label"]) ?></text><?php endforeach; ?>
                </svg>
            </div>
        </article>
        <article class="admin-chart-card">
            <div class="admin-chart-card__heading"><div><p class="panel-eyebrow">Community</p><h2>Accounts by role</h2></div></div>
            <div class="role-chart-row">
                <div class="role-donut" style="background:conic-gradient(<?= safe(implode(",", $segments)) ?>)"><span><?= array_sum($roles) ?><small>accounts</small></span></div>
                <ul class="role-chart-legend">
                    <?php foreach ($roles as $role => $amount): ?><li><span><i style="background:<?= safe($role_colors[$role]) ?>"></i><?= safe(ucfirst($role)) ?></span><strong><?= $amount ?></strong></li><?php endforeach; ?>
                </ul>
            </div>
        </article>
        <article class="admin-chart-card admin-chart-card--wide">
            <div class="admin-chart-card__heading"><div><p class="panel-eyebrow">Live blood supply</p><h2>Inventory by blood group</h2></div></div>
            <?php if (!$blood_stock): ?><p class="app-empty">No inventory has been recorded.</p>
            <?php else: ?><div class="admin-stock-chart">
                <?php foreach ($blood_stock as $stock): ?><div class="admin-stock-chart__item">
                    <div><strong><?= safe($stock["group"]) ?></strong><span><?= $stock["units"] ?> units</span></div>
                    <div class="admin-stock-chart__track"><span style="width:<?= (int)round($stock["units"] / $max_stock * 100) ?>%"></span></div>
                </div><?php endforeach; ?>
            </div><?php endif; ?>
        </article>
        <article class="admin-chart-card">
            <div class="admin-chart-card__heading"><div><p class="panel-eyebrow">Needs your attention</p><h2>Pending accounts</h2></div><a href="users.php">View all</a></div>
            <?php if (mysqli_num_rows($pending) === 0): ?><p class="app-empty">All accounts are up to date.</p>
            <?php else: while ($account = mysqli_fetch_assoc($pending)): ?>
                <div class="pending-account">
                    <span class="app-avatar app-avatar--small"><?= safe(strtoupper(substr($account["name"], 0, 1))) ?></span>
                    <span><strong><?= safe($account["name"]) ?></strong><small><?= safe(ucfirst($account["role"])) ?> · <?= safe($account["email"]) ?></small></span>
                </div>
            <?php endwhile; endif; ?>
        </article>
    </section>
</main>
<?php require "../includes/role_footer.php"; ?>
