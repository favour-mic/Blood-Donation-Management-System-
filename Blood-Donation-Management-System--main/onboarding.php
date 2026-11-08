<?php
require_once "includes/config.php";
require_once "includes/auth.php";
requireLogin();

$user_id = (int)$_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id,name,email,role,status,is_active,phone FROM users WHERE id=? LIMIT 1");
if (!$stmt) {
    error_log("BloodBridge profile setup lookup failed: " . mysqli_error($conn));
    http_response_code(503);
    exit("Profile setup is temporarily unavailable. Please try again.");
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
if (!mysqli_stmt_execute($stmt)) {
    error_log("BloodBridge profile setup lookup failed: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(503);
    exit("Profile setup is temporarily unavailable. Please try again.");
}
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$account || (int)$account["is_active"] !== 1 || !in_array($account["role"], ["donor", "recipient", "hospital"], true)) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php?status=inactive");
    exit;
}

if (($account["status"] ?? "") === "active") {
    $destinations = ["donor" => "donor/dashboard.php", "recipient" => "recipient_folder/search-donors.php", "hospital" => "hospital/dashboard.php"];
    header("Location: " . $destinations[$account["role"]]);
    exit;
}
if (($account["status"] ?? "") !== "pending") {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php?status=inactive");
    exit;
}

$profile_tables = ["donor" => "donors", "recipient" => "recipient_profiles", "hospital" => "hospitals"];
$profile_table = $profile_tables[$account["role"]];
$profile_stmt = mysqli_prepare($conn, "SELECT id FROM `$profile_table` WHERE user_id=? LIMIT 1");
if (!$profile_stmt) {
    error_log("BloodBridge profile completion check failed: " . mysqli_error($conn));
    http_response_code(503);
    exit("Profile setup is temporarily unavailable. Please try again.");
}
mysqli_stmt_bind_param($profile_stmt, "i", $user_id);
if (!mysqli_stmt_execute($profile_stmt)) {
    error_log("BloodBridge profile completion check failed: " . mysqli_stmt_error($profile_stmt));
    mysqli_stmt_close($profile_stmt);
    http_response_code(503);
    exit("Profile setup is temporarily unavailable. Please try again.");
}
$profile_complete = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($profile_stmt));
mysqli_stmt_close($profile_stmt);

$csrf_token = generateCsrfToken();
$errors = [];
$old = $_POST;
$groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
$requesting_for_options = ["Myself", "Family member", "Someone else"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$profile_complete) {
    $phone = trim($_POST["phone"] ?? "");
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $errors[] = "Your session expired. Please refresh the page and try again.";
    }
    if (mb_strlen($phone) > 30) {
        $errors[] = "Phone number must be 30 characters or fewer.";
    }

    if ($account["role"] === "donor") {
        $blood_group = trim($_POST["blood_group"] ?? "");
        $age = filter_var($_POST["age"] ?? null, FILTER_VALIDATE_INT);
        $gender = trim($_POST["gender"] ?? "");
        $weight = filter_var($_POST["weight"] ?? null, FILTER_VALIDATE_FLOAT);
        $city = trim($_POST["city"] ?? "");
        if (!in_array($blood_group, $groups, true)) $errors[] = "Select a valid blood group.";
        if ($age === false || $age < 18 || $age > 100) $errors[] = "Age must be between 18 and 100.";
        if (!in_array($gender, ["Female", "Male", "Non-binary", "Prefer not to say"], true)) $errors[] = "Select a gender option.";
        if ($weight === false || $weight <= 0 || $weight > 500) $errors[] = "Enter a valid weight.";
        if ($city === "" || mb_strlen($city) > 100) $errors[] = "Enter a city of 100 characters or fewer.";
    } elseif ($account["role"] === "recipient") {
        $city = trim($_POST["city"] ?? "");
        $requesting_for = trim($_POST["requesting_for"] ?? "");
        $preferred_hospital = trim($_POST["preferred_hospital"] ?? "");
        if ($city === "" || mb_strlen($city) > 100) $errors[] = "Enter a city of 100 characters or fewer.";
        if (!in_array($requesting_for, $requesting_for_options, true)) $errors[] = "Choose who the blood request is usually for.";
        if (mb_strlen($preferred_hospital) > 200) $errors[] = "Preferred hospital must be 200 characters or fewer.";
    } else {
        $hospital_name = trim($_POST["hospital_name"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $license = trim($_POST["license_number"] ?? "");
        if ($hospital_name === "" || mb_strlen($hospital_name) > 200) $errors[] = "Enter a hospital name of 200 characters or fewer.";
        if ($address === "" || mb_strlen($address) > 255) $errors[] = "Enter a street address of 255 characters or fewer.";
        if ($city === "" || mb_strlen($city) > 100) $errors[] = "Enter a city of 100 characters or fewer.";
        if ($license === "" || mb_strlen($license) > 50) $errors[] = "Enter a license number of 50 characters or fewer.";
    }

    if (!$errors && !mysqli_begin_transaction($conn)) {
        error_log("BloodBridge profile completion transaction could not start: " . mysqli_error($conn));
        $errors[] = "We could not save your profile right now. Please try again.";
    } elseif (!$errors) {
        if ($account["role"] === "donor") {
            $profile = mysqli_prepare($conn, "INSERT INTO donors (user_id,blood_group,age,gender,weight,city,is_available) VALUES (?,?,?,?,?,?,0)");
            if ($profile) {
                mysqli_stmt_bind_param($profile, "isisds", $user_id, $blood_group, $age, $gender, $weight, $city);
            }
        } elseif ($account["role"] === "recipient") {
            $profile = mysqli_prepare($conn, "INSERT INTO recipient_profiles (user_id,city,requesting_for,preferred_hospital) VALUES (?,?,?,?)");
            $preferred_hospital_value = $preferred_hospital === "" ? null : $preferred_hospital;
            if ($profile) mysqli_stmt_bind_param($profile, "isss", $user_id, $city, $requesting_for, $preferred_hospital_value);
        } else {
            $profile = mysqli_prepare($conn, "INSERT INTO hospitals (user_id,name,address,city,license_number,contact_phone) VALUES (?,?,?,?,?,?)");
            if ($profile) mysqli_stmt_bind_param($profile, "isssss", $user_id, $hospital_name, $address, $city, $license, $phone);
        }

        $saved = isset($profile) && $profile && mysqli_stmt_execute($profile);
        if (isset($profile) && $profile) {
            if (!$saved) error_log("BloodBridge profile completion insert failed: " . mysqli_stmt_error($profile));
            mysqli_stmt_close($profile);
        } else {
            error_log("BloodBridge profile completion statement failed: " . mysqli_error($conn));
        }

        if ($saved && $phone !== "") {
            $phone_stmt = mysqli_prepare($conn, "UPDATE users SET phone=? WHERE id=?");
            if ($phone_stmt) {
                mysqli_stmt_bind_param($phone_stmt, "si", $phone, $user_id);
                $saved = mysqli_stmt_execute($phone_stmt);
                if (!$saved) error_log("BloodBridge phone update failed: " . mysqli_stmt_error($phone_stmt));
                mysqli_stmt_close($phone_stmt);
            } else {
                error_log("BloodBridge phone update statement failed: " . mysqli_error($conn));
                $saved = false;
            }
        }

        if ($saved && mysqli_commit($conn)) {
            $profile_complete = true;
        } else {
            mysqli_rollback($conn);
            $errors[] = "We could not save your profile right now. Please review your details and try again.";
        }
    }
}

$page_title = "Complete your profile";
$page_stylesheets = ["css/onboarding.css"];
require "includes/public_header.php";
?>
<main class="auth-container onboarding-page">
    <section class="auth-card onboarding-card">
        <p class="register-eyebrow"><?= safe(ucfirst($account["role"])) ?> account setup</p>
        <?php if ($profile_complete): ?>
            <h1>Profile received</h1>
            <p>Your profile is complete and your account is awaiting administrator verification. You have limited access until it is approved.</p>
            <a class="btn btn-primary" href="logout.php">Sign out</a>
        <?php else: ?>
            <h1>Complete your profile</h1>
            <p>These details can be added after creating your account. Your role workspace will become available after an administrator verifies your profile.</p>
            <?php if ($errors): ?>
                <div class="error-message" role="alert"><?php foreach ($errors as $error): ?><p><?= safe($error) ?></p><?php endforeach; ?></div>
            <?php endif; ?>
            <form method="post" action="onboarding.php">
                <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
                <?php if ($account["role"] === "donor"): ?>
                    <div class="form-row">
                        <div class="form-group"><label for="blood_group">Blood group</label><select id="blood_group" name="blood_group" required><option value="">Select blood group</option><?php foreach ($groups as $group): ?><option value="<?= safe($group) ?>" <?= ($old["blood_group"] ?? "") === $group ? "selected" : "" ?>><?= safe($group) ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label for="gender">Gender</label><select id="gender" name="gender" required><option value="">Select an option</option><?php foreach (["Female", "Male", "Non-binary", "Prefer not to say"] as $option): ?><option value="<?= safe($option) ?>" <?= ($old["gender"] ?? "") === $option ? "selected" : "" ?>><?= safe($option) ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="age">Age</label><input id="age" name="age" type="number" min="18" max="100" value="<?= safe($old["age"] ?? "") ?>" required></div>
                        <div class="form-group"><label for="weight">Weight (kg)</label><input id="weight" name="weight" type="number" min="1" max="500" step="0.1" value="<?= safe($old["weight"] ?? "") ?>" required></div>
                    </div>
                    <div class="form-group"><label for="city">City</label><input id="city" name="city" maxlength="100" value="<?= safe($old["city"] ?? "") ?>" required></div>
                <?php elseif ($account["role"] === "recipient"): ?>
                    <div class="form-group"><label for="city">City</label><input id="city" name="city" maxlength="100" value="<?= safe($old["city"] ?? "") ?>" required></div>
                    <div class="form-group"><label for="requesting_for">Usually requesting for</label><select id="requesting_for" name="requesting_for" required><option value="">Choose one</option><?php foreach ($requesting_for_options as $option): ?><option value="<?= safe($option) ?>" <?= ($old["requesting_for"] ?? "") === $option ? "selected" : "" ?>><?= safe($option) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="preferred_hospital">Preferred hospital <span>(optional)</span></label><input id="preferred_hospital" name="preferred_hospital" maxlength="200" value="<?= safe($old["preferred_hospital"] ?? "") ?>"></div>
                <?php else: ?>
                    <div class="form-group"><label for="hospital_name">Hospital name</label><input id="hospital_name" name="hospital_name" maxlength="200" value="<?= safe($old["hospital_name"] ?? "") ?>" required></div>
                    <div class="form-group"><label for="address">Street address</label><input id="address" name="address" maxlength="255" value="<?= safe($old["address"] ?? "") ?>" required></div>
                    <div class="form-row">
                        <div class="form-group"><label for="city">City</label><input id="city" name="city" maxlength="100" value="<?= safe($old["city"] ?? "") ?>" required></div>
                        <div class="form-group"><label for="license_number">License number</label><input id="license_number" name="license_number" maxlength="50" value="<?= safe($old["license_number"] ?? "") ?>" required></div>
                    </div>
                <?php endif; ?>
                <div class="form-group"><label for="phone">Phone number <span>(optional)</span></label><input id="phone" name="phone" type="tel" maxlength="30" value="<?= safe($old["phone"] ?? $account["phone"] ?? "") ?>" autocomplete="tel"></div>
                <button class="onboarding-submit" type="submit">Save profile and await review</button>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php require "includes/public_footer.php"; ?>
