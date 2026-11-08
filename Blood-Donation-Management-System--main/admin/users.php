<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["admin"]);

$message = "";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $target_id = filter_var($_POST["user_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $action = $_POST["action"] ?? "";
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $error = "Your session expired. Please refresh the page and try again.";
    } elseif ($target_id === false || $target_id === (int)$_SESSION["user_id"] || !in_array($action, ["approve", "suspend", "restore"], true)) {
        $error = "Choose a valid account action.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT u.role,
             CASE
               WHEN u.role='donor' THEN EXISTS(SELECT 1 FROM donors d WHERE d.user_id=u.id)
               WHEN u.role='recipient' THEN EXISTS(SELECT 1 FROM recipient_profiles rp WHERE rp.user_id=u.id)
               WHEN u.role='hospital' THEN EXISTS(SELECT 1 FROM hospitals h WHERE h.user_id=u.id)
               ELSE 1
             END AS profile_complete
             FROM users u WHERE u.id=? AND u.role<>'admin' LIMIT 1"
        );
        $target = null;
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $target_id);
            if (mysqli_stmt_execute($stmt)) {
                $target = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            } else {
                error_log("BloodBridge account review lookup failed: " . mysqli_stmt_error($stmt));
                $error = "We could not review that account right now. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("BloodBridge account review statement failed: " . mysqli_error($conn));
            $error = "We could not review that account right now. Please try again.";
        }
        if ($error === "" && !$target) {
            $error = "That account could not be found.";
        } elseif ($error === "" && in_array($action, ["approve", "restore"], true) && !(int)$target["profile_complete"]) {
            $error = "This account cannot be approved until its profile is complete.";
        } elseif ($error === "" && !mysqli_begin_transaction($conn)) {
            error_log("BloodBridge account review transaction could not start: " . mysqli_error($conn));
            $error = "We could not update that account. Please try again.";
        } elseif ($error === "") {
            $status = $action === "suspend" ? "suspended" : "active";
            $is_active = $action === "suspend" ? 0 : 1;
            $stmt = mysqli_prepare($conn, "UPDATE users SET status=?,is_active=? WHERE id=? AND role<>'admin'");
            $saved = false;
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sii", $status, $is_active, $target_id);
                $saved = mysqli_stmt_execute($stmt);
                if (!$saved) error_log("BloodBridge account review failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
            } else {
                error_log("BloodBridge account review statement failed: " . mysqli_error($conn));
            }
            if ($saved && in_array($target["role"], ["donor", "hospital"], true)) {
                $verified = $action === "approve" || $action === "restore" ? 1 : 0;
                if ($target["role"] === "donor") {
                    $profile = mysqli_prepare($conn, "UPDATE donors SET is_verified=? WHERE user_id=?");
                    if ($profile) {
                        mysqli_stmt_bind_param($profile, "ii", $verified, $target_id);
                    }
                } else {
                    $profile = mysqli_prepare($conn, "UPDATE hospitals SET is_verified=?,verified_at=IF(?=1,NOW(),NULL) WHERE user_id=?");
                    if ($profile) {
                        mysqli_stmt_bind_param($profile, "iii", $verified, $verified, $target_id);
                    }
                }
                if ($profile) {
                    $saved = mysqli_stmt_execute($profile);
                    if (!$saved) error_log("BloodBridge profile verification failed: " . mysqli_stmt_error($profile));
                    mysqli_stmt_close($profile);
                } else {
                    error_log("BloodBridge profile verification statement failed: " . mysqli_error($conn));
                    $saved = false;
                }
            }
            if ($saved && mysqli_commit($conn)) {
                $message = $action === "approve" ? "Account approved and activated." : ($action === "suspend" ? "Account suspended." : "Account restored.");
            } else {
                mysqli_rollback($conn);
                $error = "We could not update that account. Please try again.";
            }
        }
    }
}

$filter = $_GET["status"] ?? "all";
$valid_filters = ["all", "pending", "active", "suspended"];
if (!in_array($filter, $valid_filters, true)) $filter = "all";
$sql = "SELECT u.id,u.name,u.email,u.phone,u.role,u.status,u.is_active,u.created_at,
        d.id AS donor_profile_id,d.blood_group AS donor_blood_group,
        rp.id AS recipient_profile_id,h.id AS hospital_profile_id,h.name AS hospital_name,h.license_number
        FROM users u LEFT JOIN donors d ON d.user_id=u.id
        LEFT JOIN recipient_profiles rp ON rp.user_id=u.id LEFT JOIN hospitals h ON h.user_id=u.id
        WHERE u.role<>'admin'";
if ($filter === "suspended") $sql .= " AND u.is_active=0";
elseif ($filter !== "all") $sql .= " AND u.status='" . mysqli_real_escape_string($conn, $filter) . "' AND u.is_active=1";
$sql .= " ORDER BY CASE WHEN u.status='pending' THEN 0 ELSE 1 END,u.created_at DESC LIMIT 200";
$accounts = mysqli_query($conn, $sql);
if (!$accounts) {
    error_log("BloodBridge admin accounts query failed: " . mysqli_error($conn));
    http_response_code(503);
    exit("Accounts are temporarily unavailable. Please try again.");
}
$csrf_token = generateCsrfToken();
$page_title = "User management";
$app_role = "admin";
$app_active = "users";
$app_asset_base = "../";
require "../includes/role_header.php";
?>
<main class="admin-dashboard">
    <div class="admin-heading">
        <div><p class="page-label">Administration</p><h1 class="role-title">User management</h1>
            <p class="role-subtitle">Verify new registrations and manage active network accounts.</p></div>
    </div>
    <?php if ($message): ?><p class="role-message" role="status"><?= safe($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error-message" role="alert"><?= safe($error) ?></p><?php endif; ?>
    <nav class="admin-filter" aria-label="Filter accounts">
        <?php foreach (["all" => "All accounts", "pending" => "Pending review", "active" => "Active", "suspended" => "Suspended"] as $key => $label): ?>
            <a class="<?= $filter === $key ? "is-active" : "" ?>" href="users.php?status=<?= safe($key) ?>"><?= safe($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <section class="admin-table-card">
        <div class="admin-table-card__heading"><div><p class="panel-eyebrow">Accounts</p><h2><?= safe(ucfirst($filter)) ?> users</h2></div><span>Showing up to 200 accounts</span></div>
        <div class="role-table-wrap">
            <table class="role-table admin-users-table">
                <thead><tr><th>User</th><th>Role</th><th>Registration details</th><th>Status</th><th>Registered</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($accounts) === 0): ?><tr><td colspan="6">No accounts match this filter.</td></tr>
                <?php else: while ($account = mysqli_fetch_assoc($accounts)):
                    $account_status = (int)$account["is_active"] === 0 ? "Suspended" : ucfirst($account["status"] ?? "pending");
                    $action = $account_status === "Pending" ? "approve" : ($account_status === "Suspended" ? "restore" : "suspend");
                    $action_label = $action === "approve" ? "Approve" : ($action === "restore" ? "Restore" : "Suspend");
                    $profile_complete = $account["role"] === "donor"
                        ? !empty($account["donor_profile_id"])
                        : ($account["role"] === "recipient" ? !empty($account["recipient_profile_id"]) : !empty($account["hospital_profile_id"]));
                    $details = $account["role"] === "donor" ? ("Blood group " . ($account["donor_blood_group"] ?? "not set")) : ($account["role"] === "hospital" ? ($account["hospital_name"] ?? "Hospital profile missing") . " · " . ($account["license_number"] ?? "No license") : "Recipient account");
                    if (!$profile_complete) $details .= " · Profile incomplete";
                ?>
                    <tr>
                        <td><strong><?= safe($account["name"]) ?></strong><small class="table-secondary"><?= safe($account["email"]) ?><br><?= safe($account["phone"] ?? "") ?></small></td>
                        <td><span class="admin-role-tag admin-role-tag--<?= safe($account["role"]) ?>"><?= safe(ucfirst($account["role"])) ?></span></td>
                        <td><?= safe($details) ?></td>
                        <td><span class="account-status account-status--<?= safe(strtolower($account_status)) ?>"><?= safe($account_status) ?></span></td>
                        <td><?= safe(date("M j, Y", strtotime($account["created_at"]))) ?></td>
                        <td><form method="post" class="admin-account-action" data-confirm="<?= $action === "suspend" ? "Suspend this account?" : "" ?>">
                            <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
                            <input type="hidden" name="user_id" value="<?= (int)$account["id"] ?>">
                            <input type="hidden" name="action" value="<?= safe($action) ?>">
                            <button class="app-button <?= $action === "suspend" ? "app-button--quiet" : "" ?>" type="submit" <?= !$profile_complete && in_array($action, ["approve", "restore"], true) ? "disabled" : "" ?> <?= $action === "suspend" ? 'data-confirm="Suspend this account?"' : "" ?>><?= !$profile_complete && in_array($action, ["approve", "restore"], true) ? "Awaiting profile" : safe($action_label) ?></button>
                        </form></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script>
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            var message = form.getAttribute("data-confirm");
            if (message && !window.confirm(message)) event.preventDefault();
        });
    });
</script>
<?php require "../includes/role_footer.php"; ?>
