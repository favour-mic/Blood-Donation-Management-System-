<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["hospital"]);
$uid = (int)$_SESSION["user_id"];
$q = mysqli_prepare($conn, "SELECT status FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($q, "i", $uid);
mysqli_stmt_execute($q);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($q)) ?: [];
if (($account["status"] ?? "pending") !== "active") {
    http_response_code(403);
    exit("Your hospital account is awaiting administrator verification.");
}
$q = mysqli_prepare($conn, "SELECT id FROM hospitals WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($q, "i", $uid);
mysqli_stmt_execute($q);
$hid = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($q))["id"] ?? 0);
$groups = ["O+", "O-", "A+", "A-", "B+", "B-", "AB+", "AB-"];
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $message = "Your session expired. Refresh the page and try again.";
    } else {
    $bg = $_POST["blood_group"] ?? "";
    $units = filter_var($_POST["units"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
    if (in_array($bg, $groups, true) && $units !== false) {
        $q = mysqli_prepare($conn, "INSERT INTO inventory(hospital_id,blood_group,units) VALUES(?,?,?) ON DUPLICATE KEY UPDATE units=VALUES(units)");
        mysqli_stmt_bind_param($q, "isi", $hid, $bg, $units);
        if (mysqli_stmt_execute($q)) $message = "Inventory updated.";
        else {
            error_log("BloodBridge inventory update failed: " . mysqli_stmt_error($q));
            $message = "We could not update inventory. Please try again.";
        }
    } else $message = "Enter a valid blood group and a non-negative unit count.";
    }
}
$q = mysqli_prepare($conn, "SELECT blood_group,units FROM inventory WHERE hospital_id=? ORDER BY FIELD(blood_group,'O+','O-','A+','A-','B+','B-','AB+','AB-')");
mysqli_stmt_bind_param($q, "i", $hid);
mysqli_stmt_execute($q);
$result = mysqli_stmt_get_result($q);
$inventory = array_fill_keys($groups, 0);
while ($r = mysqli_fetch_assoc($result)) $inventory[$r["blood_group"]] = (int)$r["units"];
$max = max(1, max($inventory));
$page_title = "Inventory";
require "header.php";
?><main class="role-main hospital-inventory-page">
    <div class="role-container">
        <p class="role-kicker">Hospital workspace</p>
        <h1 class="role-title">Inventory Management</h1>
        <p class="role-subtitle">Monitor stock by blood type. Inventory is also updated automatically when you record a completed donation or fulfill a request.</p><?php if ($message): ?><p class="role-message" role="status"><?= htmlspecialchars($message) ?></p><?php endif; ?><section class="inventory-grid"><?php foreach ($groups as $group): $units = $inventory[$group];
                                                                                                                                                                                                                                                                                                                                                $low = $units <= 5;
                                                                                                                                                                                                                                                                                                                                                $width = min(100, max(5, (int)round($units / $max * 100)));
                                                                                                                                                                                                                                                                                                                                                $id = str_replace("-", "minus", str_replace("+", "plus", $group)); ?><article class="inventory-card <?= $low ? "is-low" : "" ?>">
                <div class="inventory-card__top"><strong><?= htmlspecialchars($group) ?></strong><?php if ($low): ?><span class="inventory-low-badge">Low</span><?php endif; ?></div><strong class="inventory-card__units"><?= $units ?></strong>
                <div class="inventory-bar"><span style="width:<?= $width ?>%"></span></div>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, "UTF-8") ?>"><input type="hidden" name="blood_group" value="<?= htmlspecialchars($group) ?>"><label for="units-<?= $id ?>">Set exact units</label><input id="units-<?= $id ?>" name="units" type="number" min="0" value="<?= $units ?>" required><button type="submit">Update stock</button></form>
            </article><?php endforeach; ?></section>
    </div>
</main>
<?php require "../includes/role_footer.php"; ?>