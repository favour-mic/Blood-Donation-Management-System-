<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["hospital"]);

// Resolve the signed-in hospital once, then use its ID to scope every dashboard query.
$user_id = (int)$_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id,name,address,city,license_number FROM hospitals WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$hospital = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
$hospital_name = trim($hospital["name"] ?? "");
if ($hospital_name === "") {
    http_response_code(404);
    exit("Hospital profile was not found.");
}
$hospital_id = (int)$hospital["id"];

$stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(units),0) AS units FROM inventory WHERE hospital_id=?");
mysqli_stmt_bind_param($stmt, "i", $hospital_id);
mysqli_stmt_execute($stmt);
$inventory_total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["units"] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM requests WHERE hospital_id=? AND status IN ('Pending','Matched')");
mysqli_stmt_bind_param($stmt, "i", $hospital_id);
mysqli_stmt_execute($stmt);
$request_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["total"] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM appointments WHERE hospital_id=? AND scheduled_date>=NOW() AND status='Scheduled'");
mysqli_stmt_bind_param($stmt, "i", $hospital_id);
mysqli_stmt_execute($stmt);
$appointment_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["total"] ?? 0);

$stmt = mysqli_prepare(
    $conn,
    "SELECT a.scheduled_date,u.name AS donor_name,d.blood_group
     FROM appointments a
     INNER JOIN donors d ON d.id=a.donor_id
     INNER JOIN users u ON u.id=d.user_id
     WHERE a.hospital_id=? AND a.scheduled_date>=NOW() AND a.status='Scheduled'
     ORDER BY a.scheduled_date LIMIT 4"
);
mysqli_stmt_bind_param($stmt, "i", $hospital_id);
mysqli_stmt_execute($stmt);
$upcoming = mysqli_stmt_get_result($stmt);

$stmt = mysqli_prepare($conn, "SELECT blood_group,units FROM inventory WHERE hospital_id=? ORDER BY FIELD(blood_group,'O+','O-','A+','A-','B+','B-','AB+','AB-')");
mysqli_stmt_bind_param($stmt, "i", $hospital_id);
mysqli_stmt_execute($stmt);
$snapshot = mysqli_stmt_get_result($stmt);
$stock = [];
$low_groups = [];
while ($row = mysqli_fetch_assoc($snapshot)) {
    $row["units"] = (int)$row["units"];
    $stock[] = $row;
    if ($row["units"] <= 5) $low_groups[] = $row["blood_group"];
}
$max_units = max(1, max(array_column($stock, "units") ?: [1]));
$page_title = "Dashboard";
require "header.php";
?>
<!-- Summary cards, near-term appointments, and stock levels share the common V3 workspace shell. -->
<main class="role-main">
    <div class="role-container">
        <p class="role-kicker">Hospital workspace</p>
        <h1 class="role-title"><?= safe($hospital_name) ?></h1>
        <p class="role-subtitle"><?= safe($hospital["address"] ?? "") ?> · <?= safe($hospital["city"] ?? "") ?></p>
        <?php if ($low_groups): ?>
            <div class="hospital-alert" role="status">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <span><strong>Low stock:</strong> <?= safe(implode(", ", $low_groups)) ?> — update your inventory or schedule a donation.</span>
            </div>
        <?php endif; ?>

        <div class="role-grid">
            <section class="role-panel metric-card">
                <span class="metric-icon"><i class="fa-solid fa-droplet" aria-hidden="true"></i></span>
                <strong class="metric-value"><?= $inventory_total ?></strong>
                <span class="metric-label">Units in inventory</span>
            </section>
            <section class="role-panel metric-card">
                <span class="metric-icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
                <strong class="metric-value"><?= $appointment_count ?></strong>
                <span class="metric-label">Upcoming appointments</span>
            </section>
            <section class="role-panel metric-card">
                <span class="metric-icon"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span>
                <strong class="metric-value"><?= $request_count ?></strong>
                <span class="metric-label">Requests to action</span>
            </section>
        </div>

        <div class="dashboard-columns">
            <section class="dashboard-panel">
                <div class="panel-heading-row">
                    <div><p class="panel-eyebrow">Your schedule</p><h2>Upcoming appointments</h2></div>
                    <a class="manage-button" href="appointments.php">Manage</a>
                </div>
                <?php if (mysqli_num_rows($upcoming) === 0): ?>
                    <p class="app-empty">No upcoming appointments. Schedule your next donor from Appointments.</p>
                <?php else: while ($appointment = mysqli_fetch_assoc($upcoming)): ?>
                    <div class="appointment-row">
                        <div>
                            <div class="appointment-person"><?= safe($appointment["donor_name"]) ?> <span>(<?= safe($appointment["blood_group"]) ?>)</span></div>
                            <div class="appointment-date"><?= safe(date("M j, Y · g:i A", strtotime($appointment["scheduled_date"]))) ?></div>
                        </div>
                        <a class="manage-button" href="appointments.php">View</a>
                    </div>
                <?php endwhile; endif; ?>
            </section>
            <section class="dashboard-panel">
                <div class="panel-heading-row">
                    <div><p class="panel-eyebrow">Current stock</p><h2>Inventory snapshot</h2></div>
                    <a class="manage-button" href="inventory.php">Update</a>
                </div>
                <?php if (!$stock): ?><p class="app-empty">No inventory has been recorded yet.</p>
                <?php else: foreach ($stock as $item): $width = min(100, max(4, (int)round($item["units"] / $max_units * 100))); ?>
                    <div class="inventory-row">
                        <strong><?= safe($item["blood_group"]) ?></strong>
                        <span class="inventory-meter"><span style="width:<?= $width ?>%" class="<?= $item["units"] <= 5 ? "is-low" : "" ?>"></span></span>
                        <span class="inventory-units <?= $item["units"] <= 5 ? "is-low" : "" ?>"><?= $item["units"] ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </section>
        </div>
    </div>
</main>
<?php require "../includes/role_footer.php"; ?>
