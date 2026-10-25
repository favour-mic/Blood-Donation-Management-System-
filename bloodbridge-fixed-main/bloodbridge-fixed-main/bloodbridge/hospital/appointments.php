<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["hospital"]);

$user_id = (int)$_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id,name,city FROM hospitals WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$hospital = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
$hospital_id = (int)($hospital["id"] ?? 0);
if (!$hospital_id) {
    http_response_code(404);
    exit("Hospital profile was not found.");
}
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $error = "Your session expired. Please refresh the page and try again.";
    } elseif (($_POST["action"] ?? "") === "schedule") {
        $donor_id = filter_var($_POST["donor_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $date_input = trim($_POST["appointment_time"] ?? "");
        $timestamp = strtotime($date_input);
        $location = trim($_POST["location"] ?? ($hospital["city"] ?? ""));
        if ($donor_id === false || !$timestamp || $timestamp < time() || $location === "") {
            $error = "Choose an eligible donor, a future date and time, and a location.";
        } else {
            $eligibility = mysqli_prepare(
                $conn,
                "SELECT d.id FROM donors d INNER JOIN users u ON u.id=d.user_id
                 WHERE d.id=? AND d.is_available=1 AND u.status='active' AND u.is_active=1
                 AND (d.deferral_until IS NULL OR d.deferral_until<=CURDATE())
                 AND (d.last_donation_date IS NULL OR DATE_ADD(d.last_donation_date, INTERVAL 90 DAY)<=CURDATE())
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($eligibility, "i", $donor_id);
            mysqli_stmt_execute($eligibility);
            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($eligibility))) {
                $error = "That donor is not currently eligible or available.";
            } else {
                $scheduled_date = date("Y-m-d H:i:s", $timestamp);
                $insert = mysqli_prepare(
                    $conn,
                    "INSERT INTO appointments (donor_id,hospital_id,scheduled_date,location,status) VALUES (?,?,?,?,'Scheduled')"
                );
                mysqli_stmt_bind_param($insert, "iiss", $donor_id, $hospital_id, $scheduled_date, $location);
                if (mysqli_stmt_execute($insert)) {
                    $message = "Appointment scheduled.";
                } else {
                    error_log("BloodBridge appointment scheduling failed: " . mysqli_stmt_error($insert));
                    $error = mysqli_errno($conn) === 1062
                        ? "That hospital time slot is already booked."
                        : "We could not schedule this appointment. Please try again.";
                }
                mysqli_stmt_close($insert);
            }
            mysqli_stmt_close($eligibility);
        }
    } elseif (($_POST["action"] ?? "") === "appointment_status") {
        $appointment_id = filter_var($_POST["appointment_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $status = $_POST["status"] ?? "";
        $allowed = ["Scheduled", "Completed", "Cancelled", "No-show"];
        if ($appointment_id === false || !in_array($status, $allowed, true)) {
            $error = "Choose a valid appointment status.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT a.donor_id,a.status,a.location FROM appointments a WHERE a.id=? AND a.hospital_id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $hospital_id);
            mysqli_stmt_execute($stmt);
            $previous = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$previous) {
                $error = "That appointment could not be found.";
            } else {
                mysqli_begin_transaction($conn);
                $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=? AND hospital_id=?");
                mysqli_stmt_bind_param($stmt, "sii", $status, $appointment_id, $hospital_id);
                $updated = mysqli_stmt_execute($stmt);
                if ($updated && $status === "Completed" && $previous["status"] !== "Completed") {
                    $donor_id = (int)$previous["donor_id"];
                    $record = mysqli_prepare(
                        $conn,
                        "INSERT INTO donation_history (donor_id,hospital_id,appointment_id,date,units,location)
                         VALUES (?,?,?,CURDATE(),1,?)"
                    );
                    mysqli_stmt_bind_param($record, "iiis", $donor_id, $hospital_id, $appointment_id, $previous["location"]);
                    $updated = mysqli_stmt_execute($record);
                    if (!$updated) error_log("BloodBridge donation record failed: " . mysqli_stmt_error($record));
                    mysqli_stmt_close($record);
                    if ($updated) {
                        $donor_update = mysqli_prepare($conn, "UPDATE donors SET last_donation_date=CURDATE(),is_available=0 WHERE id=?");
                        mysqli_stmt_bind_param($donor_update, "i", $donor_id);
                        $updated = mysqli_stmt_execute($donor_update);
                        if (!$updated) error_log("BloodBridge donor update after donation failed: " . mysqli_stmt_error($donor_update));
                        mysqli_stmt_close($donor_update);
                        $blood_query = mysqli_prepare($conn, "SELECT blood_group FROM donors WHERE id=? LIMIT 1");
                        mysqli_stmt_bind_param($blood_query, "i", $donor_id);
                        mysqli_stmt_execute($blood_query);
                        $blood_group = mysqli_fetch_assoc(mysqli_stmt_get_result($blood_query))["blood_group"] ?? "";
                        mysqli_stmt_close($blood_query);
                        if ($updated && $blood_group !== "") {
                            $stock = mysqli_prepare(
                                $conn,
                                "INSERT INTO inventory (hospital_id,blood_group,units) VALUES (?,?,1)
                                 ON DUPLICATE KEY UPDATE units=units+1"
                            );
                            mysqli_stmt_bind_param($stock, "is", $hospital_id, $blood_group);
                            $updated = mysqli_stmt_execute($stock);
                            if (!$updated) error_log("BloodBridge inventory update after donation failed: " . mysqli_stmt_error($stock));
                            mysqli_stmt_close($stock);
                        }
                    }
                }
                mysqli_stmt_close($stmt);
                if ($updated) {
                    mysqli_commit($conn);
                    $message = "Appointment status updated.";
                } else {
                    mysqli_rollback($conn);
                    $error = "We could not update that appointment. Please try again.";
                }
            }
        }
    } elseif (($_POST["action"] ?? "") === "request_status") {
        $request_id = filter_var($_POST["request_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $status = $_POST["status"] ?? "";
        if ($request_id === false || !in_array($status, ["Matched", "Fulfilled", "Cancelled"], true)) {
            $error = "Choose a valid request status.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT blood_group,units_needed,status FROM requests WHERE id=? AND hospital_id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "ii", $request_id, $hospital_id);
            mysqli_stmt_execute($stmt);
            $request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$request) {
                $error = "That request could not be found.";
            } elseif ($status === "Fulfilled" && $request["status"] !== "Fulfilled") {
                mysqli_begin_transaction($conn);
                $stock = mysqli_prepare(
                    $conn,
                    "UPDATE inventory SET units=units-? WHERE hospital_id=? AND blood_group=? AND units>=?"
                );
                $units_needed = (int)$request["units_needed"];
                mysqli_stmt_bind_param($stock, "iisi", $units_needed, $hospital_id, $request["blood_group"], $units_needed);
                $stock_updated = mysqli_stmt_execute($stock) && mysqli_stmt_affected_rows($stock) === 1;
                mysqli_stmt_close($stock);
                if ($stock_updated) {
                    $update = mysqli_prepare($conn, "UPDATE requests SET status='Fulfilled' WHERE id=? AND hospital_id=?");
                    mysqli_stmt_bind_param($update, "ii", $request_id, $hospital_id);
                    $stock_updated = mysqli_stmt_execute($update);
                    mysqli_stmt_close($update);
                }
                if ($stock_updated) {
                    mysqli_commit($conn);
                    $message = "Request fulfilled and inventory updated.";
                } else {
                    mysqli_rollback($conn);
                    $error = "There is not enough stock to fulfill this request.";
                }
            } else {
                $update = mysqli_prepare($conn, "UPDATE requests SET status=? WHERE id=? AND hospital_id=?");
                mysqli_stmt_bind_param($update, "sii", $status, $request_id, $hospital_id);
                if (mysqli_stmt_execute($update)) $message = "Request status updated.";
                else {
                    error_log("BloodBridge request status update failed: " . mysqli_stmt_error($update));
                    $error = "We could not update that request. Please try again.";
                }
                mysqli_stmt_close($update);
            }
        }
    }
}

$donors = mysqli_query(
    $conn,
    "SELECT d.id,u.name,d.blood_group,d.city FROM donors d INNER JOIN users u ON u.id=d.user_id
     WHERE u.status='active' AND u.is_active=1 AND d.is_available=1
     AND (d.deferral_until IS NULL OR d.deferral_until<=CURDATE())
     AND (d.last_donation_date IS NULL OR DATE_ADD(d.last_donation_date,INTERVAL 90 DAY)<=CURDATE())
     ORDER BY u.name"
);
$stmt = mysqli_prepare(
    $conn,
    "SELECT a.id,a.scheduled_date,a.location,a.status,u.name AS donor_name,d.blood_group
     FROM appointments a INNER JOIN donors d ON d.id=a.donor_id INNER JOIN users u ON u.id=d.user_id
     WHERE a.hospital_id=? ORDER BY a.scheduled_date DESC"
);
mysqli_stmt_bind_param($stmt, "i", $hospital_id);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);
$stmt = mysqli_prepare(
    $conn,
    "SELECT r.id,r.blood_group,r.units_needed,r.urgency,r.status,r.created_at,u.name AS requester
     FROM requests r INNER JOIN users u ON u.id=r.requester_id
     WHERE r.hospital_id=? ORDER BY FIELD(r.status,'Pending','Matched','Fulfilled','Cancelled'),r.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $hospital_id);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);
$csrf_token = generateCsrfToken();
$page_title = "Appointments";
require "header.php";
?>
<main class="role-main">
    <div class="role-container">
        <p class="role-kicker">Hospital workspace</p>
        <h1 class="role-title">Appointments &amp; requests</h1>
        <p class="role-subtitle">Coordinate eligible donors and keep every blood request moving.</p>
        <?php require "tabs.php"; ?>
        <?php if ($message): ?><p class="role-message" role="status"><?= safe($message) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error-message" role="alert"><?= safe($error) ?></p><?php endif; ?>

        <section class="role-form appointment-form">
            <p class="panel-eyebrow">Plan a donation</p>
            <h2>Schedule an appointment</h2>
            <?php if (!$donors || mysqli_num_rows($donors) === 0): ?><p class="app-empty">There are no eligible donors available to schedule right now.</p><?php endif; ?>
            <form method="post" class="role-form-grid">
                <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
                <input type="hidden" name="action" value="schedule">
                <div>
                    <label for="donor_id">Eligible donor</label>
                    <select id="donor_id" name="donor_id" required>
                        <?php if ($donors): while ($donor = mysqli_fetch_assoc($donors)): ?>
                            <option value="<?= (int)$donor["id"] ?>"><?= safe($donor["name"] . " · " . $donor["blood_group"] . " · " . $donor["city"]) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div><label for="appointment_time">Date and time</label><input id="appointment_time" name="appointment_time" type="datetime-local" required></div>
                <div><label for="location">Donation location</label><input id="location" name="location" value="<?= safe($hospital["city"] ?? "") ?>" required></div>
                <button class="role-button" type="submit" <?= (!$donors || mysqli_num_rows($donors) === 0) ? "disabled" : "" ?>>Schedule appointment</button>
            </form>
        </section>

        <section class="dashboard-panel app-table-section">
            <p class="panel-eyebrow">Your calendar</p>
            <h2>Donation appointments</h2>
            <div class="role-table-wrap">
                <table class="role-table">
                    <thead><tr><th>Donor</th><th>When</th><th>Location</th><th>Status</th><th>Update</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($appointments) === 0): ?><tr><td colspan="5">No appointments yet.</td></tr>
                    <?php else: while ($row = mysqli_fetch_assoc($appointments)): ?>
                        <tr>
                            <td><?= safe($row["donor_name"] . " · " . $row["blood_group"]) ?></td>
                            <td><?= safe(date("M j, Y · g:i A", strtotime($row["scheduled_date"]))) ?></td>
                            <td><?= safe($row["location"] ?? "—") ?></td>
                            <td><span class="app-status app-status--<?= safe(strtolower(str_replace(" ", "-", $row["status"]))) ?>"><?= safe($row["status"]) ?></span></td>
                            <td><form method="post" class="inline-action">
                                <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
                                <input type="hidden" name="action" value="appointment_status">
                                <input type="hidden" name="appointment_id" value="<?= (int)$row["id"] ?>">
                                <select name="status" aria-label="Appointment status">
                                    <?php foreach (["Scheduled", "Completed", "Cancelled", "No-show"] as $status): ?><option value="<?= safe($status) ?>" <?= $row["status"] === $status ? "selected" : "" ?>><?= safe($status) ?></option><?php endforeach; ?>
                                </select><button class="role-button" type="submit">Save</button>
                            </form></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel app-table-section" id="requests">
            <p class="panel-eyebrow">Blood supply</p>
            <h2>Recipient requests</h2>
            <div class="role-table-wrap">
                <table class="role-table">
                    <thead><tr><th>Recipient</th><th>Blood group</th><th>Units</th><th>Urgency</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($requests) === 0): ?><tr><td colspan="6">No requests have been sent to your hospital.</td></tr>
                    <?php else: while ($request = mysqli_fetch_assoc($requests)): ?>
                        <tr>
                            <td><?= safe($request["requester"]) ?></td>
                            <td><?= safe($request["blood_group"]) ?></td>
                            <td><?= (int)$request["units_needed"] ?></td>
                            <td><?= safe($request["urgency"]) ?></td>
                            <td><?= safe($request["status"]) ?></td>
                            <td><?php if (!in_array($request["status"], ["Fulfilled", "Cancelled"], true)): ?>
                                <form method="post" class="inline-action">
                                    <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
                                    <input type="hidden" name="action" value="request_status">
                                    <input type="hidden" name="request_id" value="<?= (int)$request["id"] ?>">
                                    <select name="status" aria-label="Request status">
                                        <option value="Matched" <?= $request["status"] === "Matched" ? "selected" : "" ?>>Matched</option>
                                        <option value="Fulfilled">Fulfilled</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                    <button class="role-button" type="submit">Update</button>
                                </form>
                            <?php else: ?><span><?= safe($request["status"]) ?></span><?php endif; ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<?php require "../includes/role_footer.php"; ?>
