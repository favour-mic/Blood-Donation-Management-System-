<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["recipient"]);

$blood_group = trim($_GET["blood_group"] ?? "");
$city = trim($_GET["city"] ?? "");
$groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
$donors = [];
$errors = [];
// Only verified, active, available donors outside their deferral period can be shown to recipients.
if ($blood_group !== "" && !in_array($blood_group, $groups, true)) {
    $errors[] = "Choose a valid blood group.";
    $blood_group = "";
}
$sql = "SELECT d.id,d.blood_group,d.city,d.last_donation_date,d.deferral_until
        FROM donors d INNER JOIN users u ON u.id=d.user_id
        WHERE u.status='active' AND u.is_active=1 AND d.is_verified=1 AND d.is_available=1
        AND (d.deferral_until IS NULL OR d.deferral_until<=CURDATE())
        AND (d.last_donation_date IS NULL OR DATE_ADD(d.last_donation_date,INTERVAL 90 DAY)<=CURDATE())";
$types = "";
$params = [];
if ($blood_group !== "") {
    $sql .= " AND d.blood_group=?";
    $types .= "s";
    $params[] = $blood_group;
}
if ($city !== "") {
    $sql .= " AND d.city LIKE ?";
    $types .= "s";
    $params[] = "%" . $city . "%";
}
$sql .= " ORDER BY d.city,d.blood_group";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    error_log("BloodBridge donor search query failed: " . mysqli_error($conn));
    http_response_code(503);
    exit("Donor search is temporarily unavailable. Please try again.");
}
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
if (!mysqli_stmt_execute($stmt)) {
    error_log("BloodBridge donor search failed: " . mysqli_stmt_error($stmt));
    http_response_code(503);
    exit("Donor search is temporarily unavailable. Please try again.");
}
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) $donors[] = $row;
mysqli_stmt_close($stmt);

$app_role = "recipient";
$app_active = "search-donors";
$app_asset_base = "../";
$page_title = "Find donors";
require "../includes/role_header.php";
?>
<main class="recipient-page">
    <div class="recipient-page__heading">
        <div><p class="page-label">Recipient workspace</p><h1 class="page-title">Find a donor</h1>
            <p class="role-subtitle">Search verified donors by blood group and location. Personal contact details stay private.</p>
        </div>
        <a class="app-button" href="submit-request.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Create blood request</a>
    </div>
    <form action="search-donors.php" method="get" class="search-box recipient-search">
        <div class="search-field">
            <label for="blood-group">Blood group</label>
            <select id="blood-group" name="blood_group">
                <option value="">Any blood group</option>
                <?php foreach ($groups as $group): ?><option value="<?= safe($group) ?>" <?= $blood_group === $group ? "selected" : "" ?>><?= safe($group) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="search-field">
            <label for="city">City or town</label>
            <input type="search" id="city" name="city" value="<?= safe($city) ?>" placeholder="e.g. Buea">
        </div>
        <button type="submit" class="search-button"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search donors</button>
    </form>
    <?php if ($errors): ?><div class="error-message" role="alert"><?php foreach ($errors as $error): ?><p><?= safe($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <div class="recipient-results-heading">
        <div><p class="panel-eyebrow">Verified and eligible</p><h2><?= count($donors) ?> donor<?= count($donors) === 1 ? "" : "s" ?> found</h2></div>
        <span class="privacy-note"><i class="fa-solid fa-lock" aria-hidden="true"></i> Contact details stay private</span>
    </div>
    <?php if (!$donors): ?>
        <section class="recipient-empty">
            <span><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
            <h2>No matching donors yet</h2>
            <p>Try another city or blood group, or submit a request so a hospital can help coordinate.</p>
            <a class="app-button" href="submit-request.php">Submit a blood request</a>
        </section>
    <?php else: ?>
        <section class="donor-results" aria-label="Matching donors">
            <?php foreach ($donors as $donor): ?>
                <article class="donor-card">
                    <span class="donor-card__group"><?= safe($donor["blood_group"]) ?></span>
                    <div><span class="donor-card__status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Available</span>
                        <h2><?= safe($donor["city"]) ?></h2>
                        <p><?= empty($donor["last_donation_date"]) ? "No previous donation recorded" : "Last donated " . safe(date("M j, Y", strtotime($donor["last_donation_date"]))) ?></p>
                    </div>
                    <a class="request-button" href="submit-request.php?blood_group=<?= rawurlencode($donor["blood_group"]) ?>">Request this type</a>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require "../includes/role_footer.php"; ?>
