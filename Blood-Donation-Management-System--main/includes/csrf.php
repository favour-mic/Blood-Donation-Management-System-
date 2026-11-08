<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Generate CSRF token
|--------------------------------------------------------------------------
*/

/*
 * FIX (linking pass): both functions below were entirely commented out,
 * so every page that called generateCsrfToken()/verifyCsrfToken() (register.php,
 * login.php, auth/forgot-password.php, auth/reset/password.php) was calling
 * an undefined function and would fatal-error the moment it ran. Un-commented
 * the original logic — it was already correct, just switched off.
 */
function generateCsrfToken()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION["csrf_token"];
}

/*
|--------------------------------------------------------------------------
| Verify CSRF token
|--------------------------------------------------------------------------
*/

function verifyCsrfToken($token)
{
    if (
        empty($token) ||
        empty($_SESSION["csrf_token"])
    ) {
        return false;
    }
    return hash_equals(
        $_SESSION["csrf_token"],
        $token
    );
}