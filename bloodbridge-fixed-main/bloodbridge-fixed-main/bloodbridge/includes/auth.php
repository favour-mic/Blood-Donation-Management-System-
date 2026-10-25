<?php
// session_start();

/*
|--------------------------------------------------------------------------
| Check if user is logged in
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (!isset($_SESSION["user_id"])) {
        /*
         * Role pages live one directory below the project root, so the shared
         * login page is one level above them.
         */
        header("Location: ../login.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Get currently logged-in user's role
|--------------------------------------------------------------------------
*/

function getUserRole()
{
    return $_SESSION["role"] ?? null;
}

/*
|--------------------------------------------------------------------------
| Require a specific role
|--------------------------------------------------------------------------
*/

function requireRole($allowedRoles)
{
    requireLogin();

    $userRole = getUserRole();

    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        echo "<h1>403 - Access Denied</h1>";
        echo "<p>You do not have permission to access this page.</p>";
        exit;
    }

    global $conn;
    $userId = (int)$_SESSION["user_id"];
    $stmt = mysqli_prepare($conn, "SELECT status, is_active FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        error_log("BloodBridge access check failed: " . mysqli_error($conn));
        http_response_code(503);
        exit("BloodBridge is temporarily unavailable. Please try again shortly.");
    }
    mysqli_stmt_bind_param($stmt, "i", $userId);
    if (!mysqli_stmt_execute($stmt)) {
        error_log("BloodBridge access check failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        http_response_code(503);
        exit("BloodBridge is temporarily unavailable. Please try again shortly.");
    }
    $account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$account || (int)$account["is_active"] !== 1 || ($account["status"] ?? "") !== "active") {
        $_SESSION = [];
        session_destroy();
        header("Location: ../login.php?status=inactive");
        exit;
    }
}

/* Teammates now just get to simply do 
require_once "../includes/auth.php";"
requireLogin();
instead of every teammate writing complicated authentication code */