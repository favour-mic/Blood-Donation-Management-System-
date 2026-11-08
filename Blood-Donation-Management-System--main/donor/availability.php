<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["donor"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}
if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
    http_response_code(400);
    exit("Your session expired. Please refresh the page and try again.");
}
$available = filter_var($_POST["available"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 1]]);
if ($available === false) {
    http_response_code(400);
    exit("Choose a valid availability setting.");
}
$stmt = mysqli_prepare($conn, "UPDATE donors SET is_available = ? WHERE user_id = ?");
if (!$stmt) {
    error_log("BloodBridge availability update could not be prepared: " . mysqli_error($conn));
    http_response_code(503);
    exit("We could not update your availability. Please try again.");
}
$user_id = (int)$_SESSION["user_id"];
mysqli_stmt_bind_param($stmt, "ii", $available, $user_id);
if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) < 0) {
    error_log("BloodBridge availability update failed: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(503);
    exit("We could not update your availability. Please try again.");
}
mysqli_stmt_close($stmt);
header("Location: dashboard.php");
exit;
