<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["recipient"]);

$groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
$urgencies = ["Low", "Medium", "High"];
$errors = [];
$success = "";
$values = [
    "blood_group" => $_POST["blood_group"] ?? $_GET["blood_group"] ?? "",
    "units" => $_POST["units"] ?? "1",
    "urgency" => $_POST["urgency"] ?? "Medium",
    "hospital_id" => $_POST["hospital_id"] ?? "",
    "patient_name" => $_POST["patient_name"] ?? ""
];
$hospitals = mysqli_query(
    $conn,
    "SELECT h.id,h.name,h.city FROM hospitals h
     INNER JOIN users u ON u.id=h.user_id
     WHERE h.is_verified=1 AND u.status='active' AND u.is_active=1 ORDER BY h.name"
);
if (!$hospitals) {
    error_log("BloodBridge hospital list failed: " . mysqli_error($conn));
    http_response_code(503);
    exit("The hospital list is temporarily unavailable. Please try again.");
}
$hospital_options = [];
while ($hospital = mysqli_fetch_assoc($hospitals)) $hospital_options[] = $hospital;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $units = filter_var($values["units"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 100]]);
    $hospital_id = filter_var($values["hospital_id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) $errors[] = "Your session expired. Please refresh the page and try again.";
    if (!in_array($values["blood_group"], $groups, true)) $errors[] = "Select a valid blood group.";
    if ($units === false) $errors[] = "Enter a number of units between 1 and 100.";
    if (!in_array($values["urgency"], $urgencies, true)) $errors[] = "Select a valid urgency.";
    if ($hospital_id === false || !array_filter($hospital_options, function ($hospital) use ($hospital_id) { return (int)$hospital["id"] === $hospital_id; })) {
        $errors[] = "Select a verified hospital.";
    }
    if (mb_strlen(trim($values["patient_name"])) > 150) $errors[] = "Patient name must be 150 characters or fewer.";

    if (!$errors) {
        $requester_id = (int)$_SESSION["user_id"];
        $patient_name = trim($values["patient_name"]);
        if ($patient_name === "") $patient_name = null;
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO requests (requester_id,patient_name,blood_group,units_needed,urgency,hospital_id,status)
             VALUES (?,?,?,?,?,?,'Pending')"
        );
        if (!$stmt) {
            error_log("BloodBridge request insert could not be prepared: " . mysqli_error($conn));
            $errors[] = "We could not submit your request right now. Please try again.";
        } else {
            mysqli_stmt_bind_param($stmt, "issisi", $requester_id, $patient_name, $values["blood_group"], $units, $values["urgency"], $hospital_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: my-request.php?created=1");
                exit;
            }
            error_log("BloodBridge request insert failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            $errors[] = "We could not submit your request right now. Please try again.";
        }
    }
}

$csrf_token = generateCsrfToken();
$app_role = "recipient";
$app_active = "submit-request";
$app_asset_base = "../";
$page_title = "Submit blood request";
require "../includes/role_header.php";
?>
<main class="recipient-page">
    <div class="recipient-page__heading">
        <div><p class="page-label">Recipient workspace</p><h1 class="submit-title">Submit a blood request</h1>
            <p class="role-subtitle">Send a request to a verified hospital. You can follow its progress from My requests.</p>
        </div>
    </div>
    <?php if ($errors): ?><div class="error-message" role="alert"><?php foreach ($errors as $error): ?><p><?= safe($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <section class="request-form-card">
        <?php if (!$hospital_options): ?>
            <div class="recipient-empty"><h2>No verified hospitals yet</h2><p>Please try again after an administrator verifies a hospital account.</p></div>
        <?php else: ?>
            <form method="post" action="submit-request.php">
                <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
                <div class="form-group">
                    <label for="patient_name">Patient name <span class="field-optional">(optional)</span></label>
                    <input id="patient_name" name="patient_name" maxlength="150" value="<?= safe($values["patient_name"]) ?>" autocomplete="name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_group">Blood group needed</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">Choose a blood group</option>
                            <?php foreach ($groups as $group): ?><option value="<?= safe($group) ?>" <?= $values["blood_group"] === $group ? "selected" : "" ?>><?= safe($group) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="units">Units needed</label>
                        <input type="number" id="units" name="units" min="1" max="100" value="<?= safe($values["units"]) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="urgency">Urgency</label>
                        <select id="urgency" name="urgency" required>
                            <?php foreach ($urgencies as $urgency): ?><option value="<?= safe($urgency) ?>" <?= $values["urgency"] === $urgency ? "selected" : "" ?>><?= safe($urgency) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hospital_id">Hospital</label>
                        <select id="hospital_id" name="hospital_id" required>
                            <option value="">Choose a hospital</option>
                            <?php foreach ($hospital_options as $hospital): ?>
                                <option value="<?= (int)$hospital["id"] ?>" <?= (string)$values["hospital_id"] === (string)$hospital["id"] ? "selected" : "" ?>>
                                    <?= safe($hospital["name"] . " · " . $hospital["city"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="submit-request-button" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit request</button>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php require "../includes/role_footer.php"; ?>
