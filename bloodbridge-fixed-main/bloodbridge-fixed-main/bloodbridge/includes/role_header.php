<?php
$app_role = $app_role ?? "donor";
$app_active = $app_active ?? basename($_SERVER["PHP_SELF"], ".php");
$app_asset_base = $app_asset_base ?? "../";
$app_name = $_SESSION["full_name"] ?? ucfirst($app_role);
$app_notifications = [];
if ($app_role === "hospital" && isset($conn, $_SESSION["user_id"])) {
    $notification_hospital = mysqli_prepare($conn, "SELECT id FROM hospitals WHERE user_id = ? LIMIT 1");
    if ($notification_hospital) {
        $notification_user_id = (int)$_SESSION["user_id"];
        mysqli_stmt_bind_param($notification_hospital, "i", $notification_user_id);
        if (mysqli_stmt_execute($notification_hospital)) {
            $notification_hospital_id = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($notification_hospital))["id"] ?? 0);
            mysqli_stmt_close($notification_hospital);
            if ($notification_hospital_id > 0) {
                $notification_query = mysqli_prepare(
                    $conn,
                    "SELECT 'appointment' AS kind, u.name AS detail, a.scheduled_date AS event_date
                     FROM appointments a
                     INNER JOIN donors d ON d.id = a.donor_id
                     INNER JOIN users u ON u.id = d.user_id
                     WHERE a.hospital_id = ? AND a.scheduled_date >= NOW() AND a.status = 'Scheduled'
                     UNION ALL
                     SELECT 'request' AS kind, CONCAT(r.blood_group, ' · ', r.units_needed, ' unit(s)') AS detail, r.created_at AS event_date
                     FROM requests r WHERE r.hospital_id = ? AND r.status IN ('Pending','Matched')
                     ORDER BY event_date DESC LIMIT 8"
                );
                if ($notification_query) {
                    mysqli_stmt_bind_param($notification_query, "ii", $notification_hospital_id, $notification_hospital_id);
                    if (mysqli_stmt_execute($notification_query)) {
                        $notification_result = mysqli_stmt_get_result($notification_query);
                        while ($notification = mysqli_fetch_assoc($notification_result)) {
                            $app_notifications[] = $notification;
                        }
                    }
                    mysqli_stmt_close($notification_query);
                }
            }
        } else {
            mysqli_stmt_close($notification_hospital);
        }
    }
}
$app_nav = [
    "admin" => [
        ["dashboard", "Dashboard", "fa-chart-pie", "dashboard.php"],
        ["users", "User management", "fa-users", "users.php"]
    ],
    "donor" => [
        ["dashboard", "Dashboard", "fa-chart-pie", "dashboard.php"],
        ["profile", "My profile", "fa-user", "profile.php"],
        ["history", "Donation history", "fa-clock-rotate-left", "history.php"]
    ],
    "recipient" => [
        ["search-donors", "Find donors", "fa-magnifying-glass", "search-donors.php"],
        ["submit-request", "New request", "fa-droplet", "submit-request.php"],
        ["my-request", "My requests", "fa-clipboard-list", "my-request.php"]
    ],
    "hospital" => [
        ["dashboard", "Dashboard", "fa-chart-pie", "dashboard.php"],
        ["inventory", "Blood inventory", "fa-boxes-stacked", "inventory.php"],
        ["appointments", "Appointments", "fa-calendar-check", "appointments.php"]
    ]
];
?>
<!-- One shared shell keeps navigation, account identity, notifications, and assets consistent by role. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6d1029">
    <title><?= safe($page_title ?? "Dashboard") ?> | BloodBridge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <?php if ($app_role === "donor"): ?>
        <link rel="stylesheet" href="donor.css">
    <?php elseif ($app_role === "hospital"): ?>
        <link rel="stylesheet" href="hospital.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= safe($app_asset_base) ?>css/app-shell.css">
</head>
<body class="app-shell app-shell--<?= safe($app_role) ?>">
    <div class="app-frame">
        <aside class="app-sidebar" id="appSidebar">
            <a class="app-brand" href="<?= safe($app_asset_base) ?>index.php">
                <span class="app-brand__mark"><i class="fa-solid fa-droplet" aria-hidden="true"></i></span>
                <span>BloodBridge<small>Donation management</small></span>
            </a>
            <div class="app-sidebar__label">WORKSPACE</div>
            <nav class="app-nav" aria-label="<?= safe(ucfirst($app_role)) ?> navigation">
                <?php foreach ($app_nav[$app_role] ?? [] as $item): ?>
                    <a href="<?= safe($item[3]) ?>" class="<?= $app_active === $item[0] ? "is-active" : "" ?>"
                       <?= $app_active === $item[0] ? 'aria-current="page"' : "" ?>>
                        <i class="fa-solid <?= safe($item[2]) ?>" aria-hidden="true"></i>
                        <span><?= safe($item[1]) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="app-sidebar__bottom">
                <div class="app-sidebar__identity">
                    <span class="app-avatar"><?= safe(strtoupper(substr($app_name, 0, 1))) ?></span>
                    <span><strong><?= safe($app_name) ?></strong><small><?= safe(ucfirst($app_role)) ?> account</small></span>
                </div>
                <a class="app-signout" href="<?= safe($app_asset_base) ?>logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                    Sign out
                </a>
            </div>
        </aside>
        <div class="app-workspace">
            <header class="app-topbar">
                <button class="app-menu-toggle" id="appMenuToggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="appSidebar">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                <div class="app-topbar__section"><?= safe(ucfirst($app_role)) ?> workspace</div>
                <?php if ($app_role === "hospital"): ?>
                    <div class="app-notifications">
                        <button class="app-notifications__button" id="notificationButton" type="button" aria-label="Notifications" aria-expanded="false">
                            <i class="fa-regular fa-bell" aria-hidden="true"></i>
                            <?php if ($app_notifications): ?><span><?= count($app_notifications) ?></span><?php endif; ?>
                        </button>
                        <div class="app-notifications__panel" id="notificationPanel" hidden>
                            <strong>Notifications</strong>
                            <?php if (!$app_notifications): ?><p>No new notifications.</p>
                            <?php else: foreach ($app_notifications as $notification): ?>
                                <a href="<?= $notification["kind"] === "appointment" ? "appointments.php" : "appointments.php#requests" ?>">
                                    <span><?= $notification["kind"] === "appointment" ? "Upcoming appointment" : "Request awaiting action" ?></span>
                                    <small><?= safe($notification["detail"]) ?></small>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <a class="app-topbar__account" href="<?= safe($app_nav[$app_role][0][3] ?? "dashboard.php") ?>">
                    <span class="app-avatar app-avatar--small"><?= safe(strtoupper(substr($app_name, 0, 1))) ?></span>
                    <span><?= safe($app_name) ?></span>
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </a>
            </header>
            <div class="app-content">
                <script>
                    (function () {
                        var button = document.getElementById("notificationButton");
                        var panel = document.getElementById("notificationPanel");
                        if (!button || !panel) return;
                        button.addEventListener("click", function () {
                            var open = button.getAttribute("aria-expanded") === "true";
                            button.setAttribute("aria-expanded", String(!open));
                            panel.hidden = open;
                        });
                        document.addEventListener("click", function (event) {
                            if (!event.target.closest(".app-notifications")) {
                                button.setAttribute("aria-expanded", "false");
                                panel.hidden = true;
                            }
                        });
                    }());
                </script>
