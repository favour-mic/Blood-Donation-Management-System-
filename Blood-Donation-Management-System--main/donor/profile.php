<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["donor"]);
$user_id = (int)$_SESSION["user_id"];
$message = "";
$csrf_token = generateCsrfToken();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $message = "Your session expired. Refresh the page and try again.";
    } else {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $blood_group = trim($_POST["blood_group"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $age = (int)($_POST["age"] ?? 0);
    $weight = (float)($_POST["weight"] ?? 0);
    $city = trim($_POST["city"] ?? "");
    $groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === "" 
    || !in_array($blood_group, $groups, true) || !in_array($gender, ["Female", "Male", "Non-binary", "Prefer not to say"], true)
    || $age < 18 || $weight <= 0 || $city === "") {
        $message = "Please complete every field with valid information.";
    } else {
        $duplicate = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        mysqli_stmt_bind_param($duplicate, "si", $email, $user_id);
        mysqli_stmt_execute($duplicate);
        mysqli_stmt_store_result($duplicate);
        if (mysqli_stmt_num_rows($duplicate) > 0) {
            $message = "That email address is already used by another account.";
        }
        mysqli_stmt_close($duplicate);
        if ($message !== "") {
            $name = null;
        }
        if ($name !== null) {
        $user_stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        mysqli_stmt_bind_param($user_stmt, "sssi", $name, $email, $phone, $user_id);
        $donor_stmt = mysqli_prepare($conn, "UPDATE donors SET blood_group = ?, gender = ?,
        age = ?, weight = ?, city = ? WHERE user_id = ?");
        if ($user_stmt && $donor_stmt) {
            mysqli_stmt_bind_param($donor_stmt, "ssidsi", $blood_group, $gender, $age, $weight, $city, $user_id);
            mysqli_stmt_bind_param($user_stmt, "sssi", $name, $email, $phone, $user_id);
            mysqli_begin_transaction($conn);
            if (mysqli_stmt_execute($user_stmt) && mysqli_stmt_execute($donor_stmt)) {
                mysqli_commit($conn);
                $_SESSION["full_name"] = $name;
                $_SESSION["email"] = $email;
                $message = "Profile saved successfully.";
            } else {
                mysqli_rollback($conn);
                error_log("BloodBridge donor profile update failed: " . mysqli_stmt_error($donor_stmt));
                $message = "We could not save your profile. Please try again.";
            }
        } else {
            error_log("BloodBridge donor profile update could not be prepared: " . mysqli_error($conn));
            $message = "We could not save your profile. Please try again.";
        }
        }
    }
    }
}
$stmt = mysqli_prepare($conn, "SELECT u.name,u.email,u.phone,d.blood_group,
d.age,d.gender,d.weight,d.city FROM users u INNER JOIN donors d ON d.user_id=u.id WHERE u.id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
$page_title = "Edit Profile";
require "header.php";

?>
<main class="role-main">
    <div class="role-container">
        <p class="role-kicker">Donor</p>
        <h1 class="role-title">Edit Profile</h1><?php if ($message !== ""): ?>
        <p class="role-message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form class="role-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, "UTF-8") ?>">
            <div class="role-form-grid">
                <div>
                    <label for="name">Name</label>
                    <input id="name" name="name" value="<?= htmlspecialchars($profile["name"] ?? "") ?>" required>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?= htmlspecialchars($profile["email"] ?? "") ?>" required>
                </div>
                <div class="full">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="<?= htmlspecialchars($profile["phone"] ?? "") ?>" required>
                </div>
                <div>
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group" required><?php foreach (["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"] as $group): ?>
                        <option <?= ($profile["blood_group"] ?? "") === $group ? "selected" : "" ?>><?= $group ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required><?php foreach (["Female", "Male", "Non-binary", "Prefer not to say"] as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= ($profile["gender"] ?? "Prefer not to say") === $option ? "selected" : "" ?>><?= htmlspecialchars($option) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="age">Age</label>
                    <input id="age" type="number" name="age" min="18" max="100" value="<?= htmlspecialchars($profile["age"] ?? "") ?>" required>
                </div>
                <div>
                    <label for="weight">Weight (kg)</label>
                    <input id="weight" type="number" step="0.1" min="1" name="weight" value="<?= htmlspecialchars($profile["weight"] ?? "") ?>" required>
                </div>
                <div>
                    <label for="city">City</label>
                    <input id="city" name="city" value="<?= htmlspecialchars($profile["city"] ?? "") ?>" required>
                </div>
            </div>
            <button class="role-button" type="submit">Save Changes</button>
        </form>
    </div>
</main>
<?php require "../includes/role_footer.php"; ?>