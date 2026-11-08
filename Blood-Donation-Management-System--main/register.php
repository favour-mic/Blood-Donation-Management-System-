<?php
require_once "includes/config.php";

$csrf_token = generateCsrfToken();
$errors = [];
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$role = $_POST["role"] ?? "donor";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $errors[] = "Your session expired. Please refresh the page and try again.";
    }
    if ($name === "" || mb_strlen($name) > 150) {
        $errors[] = "Enter a name of 150 characters or fewer.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
        $errors[] = "Please enter a valid email address.";
    }
    if (!in_array($role, ["donor", "recipient", "hospital"], true)) {
        $errors[] = "Choose a valid account type.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (!$errors) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (name,email,password,role,status,is_active) VALUES (?,?,?,?,'pending',1)"
        );
        if (!$stmt) {
            error_log("BloodBridge registration statement failed: " . mysqli_error($conn));
            $errors[] = "We could not create the account right now. Please try again.";
        } else {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password_hash, $role);
            if (mysqli_stmt_execute($stmt)) {
                $user_id = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                session_regenerate_id(true);
                $_SESSION["user_id"] = $user_id;
                $_SESSION["full_name"] = $name;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = $role;
                header("Location: onboarding.php");
                exit;
            }
            if (mysqli_stmt_errno($stmt) === 1062) {
                $errors[] = "An account with this email already exists.";
            } else {
                error_log("BloodBridge registration insert failed: " . mysqli_stmt_error($stmt));
                $errors[] = "We could not create the account right now. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$page_title = "Create Account";
require "includes/public_header.php";
?>
<main class="auth-container auth-container--register">
    <section class="auth-card register-card">
        <p class="register-eyebrow">Join BloodBridge</p>
        <h1>Create your account</h1>
        <p>Start with your name, email and password. You can complete your profile after creating your account.</p>
        <?php if ($errors): ?>
            <div class="error-message" role="alert">
                <?php foreach ($errors as $error): ?><p><?= safe($error) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
            <div class="form-group">
                <label for="name">Name</label>
                <input id="name" name="name" maxlength="150" value="<?= safe($name) ?>" autocomplete="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="180" value="<?= safe($email) ?>" autocomplete="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                <small class="field-help">Use at least 8 characters.</small>
            </div>
            <div class="form-group">
                <label for="role">Account type</label>
                <select id="role" name="role" required>
                    <option value="donor" <?= $role === "donor" ? "selected" : "" ?>>Donor</option>
                    <option value="recipient" <?= $role === "recipient" ? "selected" : "" ?>>Recipient</option>
                    <option value="hospital" <?= $role === "hospital" ? "selected" : "" ?>>Hospital</option>
                </select>
            </div>
            <button type="submit">Create account</button>
            <div class="auth-links"><a href="login.php">Already registered? Sign in</a></div>
        </form>
    </section>
</main>
<?php require "includes/public_footer.php"; ?>
