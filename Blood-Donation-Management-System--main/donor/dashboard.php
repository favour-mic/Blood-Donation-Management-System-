<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["donor"]);

// Load the donor profile and lifetime totals used by the dashboard cards.
$user_id = (int) $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id, blood_group, age, weight, city, is_available, 
last_donation_date, DATE_ADD(last_donation_date, INTERVAL 90 DAY) AS next_eligible_date 
FROM donors WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$donor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
$donor_id = (int) ($donor["id"] ?? 0);
$history_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS donations, COALESCE(SUM(units), 0) 
AS units FROM donation_history WHERE donor_id = ?");
mysqli_stmt_bind_param($history_stmt, "i", $donor_id);
mysqli_stmt_execute($history_stmt);
$history = mysqli_fetch_assoc(mysqli_stmt_get_result($history_stmt)) ?: ["donations" => 0, "units" => 0];
$next_date = $donor["next_eligible_date"] ?? null;
$is_eligible = !$next_date || $next_date <= date("Y-m-d");
?>
<?php $page_title = "Dashboard";
require "header.php"; ?>
<!-- The first panel contains a CSRF-protected availability action; the other cards summarize impact. -->
<main class="role-main">
    <div class="role-container">
        <p class="role-kicker">Donor</p>
        <h1 class="role-title">Welcome back, <?= htmlspecialchars($_SESSION["full_name"]) ?></h1>
        <p class="role-subtitle">Blood group <?= htmlspecialchars($donor["blood_group"] ?? "Not set") ?> 
        · <?= htmlspecialchars($donor["city"] ?? "Not set") ?></p>
        <div class="role-grid">
            <section class="role-panel">
                <h2>Availability</h2>
                <p>Toggle this off anytime you can't donate right now.</p>
                <form method="post" action="availability.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, "UTF-8") ?>">
                    <button class="role-switch <?= !empty($donor["is_available"]) ? "is-on" : "" ?>" 
                    type="submit" name="available" value="<?= !empty($donor["is_available"]) ? "0" : "1" ?>">
                        <span></span>
                    </button>
                    <span class="role-status <?= !empty($donor["is_available"]) ? "is-on" : "" ?>">
                        <?= !empty($donor["is_available"]) ? "Available" : "Unavailable" ?>
                    </span>
                </form>
            </section>
            <section class="role-panel">
                <h2>Next Eligible Date</h2>
                <span class="role-pill"><?= $is_eligible ? "Eligible now" : htmlspecialchars(date("M j, Y", strtotime($next_date))) ?></span>
            </section>
            <section class="role-panel">
                <h2>Lifetime Impact</h2>
                <strong class="role-impact"><?= (int)$history["units"] ?></strong>
                <p>Total Units Donated</p>
            </section>
        </div>
    </div>
</main>
<?php require "../includes/role_footer.php"; ?>