<?php
require_once "includes/config.php";

$csrf_token = generateCsrfToken();
$errors = [];
$email = trim($_POST["email"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $errors[] = "Your session expired. Please refresh the page and try again.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (($_POST["password"] ?? "") === "") {
        $errors[] = "Password is required.";
    }

    if (!$errors) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password, role, status, is_active, failed_login_attempts, locked_until
             FROM users WHERE email = ? LIMIT 1"
        );
        if (!$stmt) {
            error_log("BloodBridge login query failed: " . mysqli_error($conn));
            $errors[] = "We could not sign you in right now. Please try again.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            if (!mysqli_stmt_execute($stmt)) {
                error_log("BloodBridge login query execution failed: " . mysqli_stmt_error($stmt));
                $errors[] = "We could not sign you in right now. Please try again.";
            } else {
                $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if (!$user || !password_verify($_POST["password"], $user["password"])) {
                    if ($user) {
                        mysqli_query(
                            $conn,
                            "UPDATE users SET failed_login_attempts = failed_login_attempts + 1,
                             locked_until = IF(failed_login_attempts + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), locked_until)
                             WHERE id = " . (int)$user["id"]
                        );
                    }
                    $errors[] = "Invalid email or password.";
                } elseif (!empty($user["locked_until"]) && strtotime($user["locked_until"]) > time()) {
                    $errors[] = "Too many sign-in attempts. Please try again in 15 minutes.";
                } elseif ((int)$user["is_active"] !== 1) {
                    $errors[] = "This account has been disabled. Please contact the administrator.";
                } elseif (($user["status"] ?? "") !== "active") {
                    $errors[] = "Your account is awaiting administrator verification.";
                } else {
                    mysqli_query(
                        $conn,
                        "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = " . (int)$user["id"]
                    );
                    session_regenerate_id(true);
                    $_SESSION["user_id"] = (int)$user["id"];
                    $_SESSION["full_name"] = $user["name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];
                    switch ($user["role"]) {
                        case "donor":
                            header("Location: donor/dashboard.php");
                            exit;
                        case "recipient":
                            header("Location: recipient_folder/search-donors.php");
                            exit;
                        case "hospital":
                            header("Location: hospital/dashboard.php");
                            exit;
                        case "admin":
                            header("Location: admin/dashboard.php");
                            exit;
                        default:
                            $_SESSION = [];
                            session_destroy();
                            $errors[] = "This account has an unsupported role.";
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
$page_title = "Log In";
require "includes/public_header.php";
?>
<main class="auth-container auth-container--login">
    <section class="auth-card login-card">
        <p class="register-eyebrow">BloodBridge workspace</p>
        <h1>Welcome back</h1>
        <p>Sign in to continue to your secure account.</p>
        <?php if ($errors): ?>
            <div class="error-message" role="alert">
                <?php foreach ($errors as $error): ?><p><?= safe($error) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= safe($email) ?>" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit">Log in</button>
            <div class="auth-links"><a href="register.php">Create an account</a></div>
        </form>
    </section>
</main>
<?php require "includes/public_footer.php"; ?>
