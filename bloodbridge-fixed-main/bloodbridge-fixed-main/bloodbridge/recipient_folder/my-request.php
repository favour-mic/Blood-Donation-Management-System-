<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["recipient"]);

$user_id = (int)$_SESSION["user_id"];
$stmt = mysqli_prepare(
    $conn,
    "SELECT r.id,r.blood_group,r.units_needed,r.urgency,r.status,r.created_at,
            h.name AS hospital_name,h.city AS hospital_city
     FROM requests r LEFT JOIN hospitals h ON h.id=r.hospital_id
     WHERE r.requester_id=? ORDER BY r.created_at DESC"
);
if (!$stmt) {
    error_log("BloodBridge recipient request lookup failed: " . mysqli_error($conn));
    http_response_code(503);
    exit("Your requests are temporarily unavailable. Please try again.");
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);
$rows = [];
while ($row = mysqli_fetch_assoc($requests)) $rows[] = $row;
mysqli_stmt_close($stmt);

$app_role = "recipient";
$app_active = "my-request";
$app_asset_base = "../";
$page_title = "My requests";
require "../includes/role_header.php";
?>
<main class="recipient-page">
    <div class="recipient-page__heading">
        <div><p class="page-label">Recipient workspace</p><h1 class="page-title">My requests</h1>
            <p class="role-subtitle">See each request's current status and the hospital coordinating your care.</p>
        </div>
        <a class="app-button" href="submit-request.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> New request</a>
    </div>
    <?php if (isset($_GET["created"])): ?><p class="role-message" role="status">Your request was submitted successfully.</p><?php endif; ?>
    <?php if (!$rows): ?>
        <section class="recipient-empty">
            <span><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span>
            <h2>You have no requests yet</h2>
            <p>Submit a request and a verified hospital will coordinate the next steps.</p>
            <a class="app-button" href="submit-request.php">Submit a blood request</a>
        </section>
    <?php else: ?>
        <section class="request-list" aria-label="Your blood requests">
            <?php foreach ($rows as $request):
                $stages = ["Pending" => 1, "Matched" => 2, "Fulfilled" => 3, "Cancelled" => 0];
                $stage = $stages[$request["status"]] ?? 1;
            ?>
                <article class="request-card">
                    <div class="request-card__top">
                        <div><p class="panel-eyebrow">Request #<?= (int)$request["id"] ?></p>
                            <h2><?= safe($request["blood_group"]) ?> <span>·</span> <?= (int)$request["units_needed"] ?> unit<?= (int)$request["units_needed"] === 1 ? "" : "s" ?></h2>
                            <p><?= safe($request["hospital_name"] ?? "Hospital") ?><?= !empty($request["hospital_city"]) ? " · " . safe($request["hospital_city"]) : "" ?> · Submitted <?= safe(date("M j, Y", strtotime($request["created_at"]))) ?></p>
                        </div>
                        <span class="request-status request-status--<?= safe(strtolower($request["status"])) ?>"><?= safe($request["status"]) ?></span>
                    </div>
                    <div class="request-progress" aria-label="Request status: <?= safe($request["status"]) ?>">
                        <div class="request-progress__line"><span style="width:<?= $stage === 0 ? "0" : (int)round(($stage - 1) / 2 * 100) ?>%"></span></div>
                        <div class="request-progress__labels"><span class="<?= $stage >= 1 ? "is-complete" : "" ?>">Pending</span><span class="<?= $stage >= 2 ? "is-complete" : "" ?>">Matched</span><span class="<?= $stage >= 3 ? "is-complete" : "" ?>">Fulfilled</span></div>
                    </div>
                    <p class="request-card__meta">Urgency: <strong><?= safe($request["urgency"]) ?></strong></p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require "../includes/role_footer.php"; ?>
