<?php
error_reporting(E_ALL);
ini_set("display_errors", "0");
ini_set("log_errors", "1");
mysqli_report(MYSQLI_REPORT_OFF);

require_once __DIR__ . "/csrf.php";

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "bdms";

$conn = mysqli_connect($db_host, $db_user, $db_pass);
if (!$conn) {
    error_log("BloodBridge database connection failed: " . mysqli_connect_error());
    http_response_code(503);
    exit("BloodBridge is temporarily unavailable. Please try again shortly.");
}

if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$db_name`") ||
    !mysqli_select_db($conn, $db_name)) {
    error_log("BloodBridge could not initialize database `$db_name`: " . mysqli_error($conn));
    http_response_code(503);
    exit("BloodBridge is temporarily unavailable. Please try again shortly.");
}
mysqli_set_charset($conn, "utf8mb4");

function bdms_ensure_column($conn, $table, $column, $definition)
{
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query(
        $conn,
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'"
    );
    if (!$check) {
        error_log("BloodBridge schema check failed for `$table`.`$column`: " . mysqli_error($conn));
        return false;
    }
    if (mysqli_num_rows($check) > 0) {
        return true;
    }
    if (!mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
        error_log("BloodBridge schema update failed for `$table`.`$column`: " . mysqli_error($conn));
        return false;
    }
    return true;
}

$schema = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(180) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','donor','recipient','hospital') NOT NULL,
        phone VARCHAR(30) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        failed_login_attempts INT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'pending'
    )",
    "donors" => "CREATE TABLE IF NOT EXISTS donors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        blood_group VARCHAR(5) NOT NULL,
        age INT NOT NULL,
        gender VARCHAR(20) NOT NULL DEFAULT 'Prefer not to say',
        weight DECIMAL(5,2) NULL,
        city VARCHAR(100) NOT NULL,
        last_donation_date DATE NULL,
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        deferral_until DATE NULL,
        deferral_reason VARCHAR(255) NULL,
        is_verified TINYINT(1) NOT NULL DEFAULT 0,
        INDEX (blood_group),
        INDEX (is_available)
    )",
    "hospitals" => "CREATE TABLE IF NOT EXISTS hospitals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        name VARCHAR(200) NOT NULL,
        address VARCHAR(255) NOT NULL,
        city VARCHAR(100) NOT NULL,
        license_number VARCHAR(50) NOT NULL,
        contact_phone VARCHAR(30) NULL,
        is_verified TINYINT(1) NOT NULL DEFAULT 0,
        verified_at DATETIME NULL,
        INDEX (city)
    )",
    // Keep recipient registration preferences out of the shared login record.
    "recipient_profiles" => "CREATE TABLE IF NOT EXISTS recipient_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        city VARCHAR(100) NOT NULL,
        requesting_for VARCHAR(40) NOT NULL DEFAULT 'Myself',
        preferred_hospital VARCHAR(200) NULL
    )",
    "requests" => "CREATE TABLE IF NOT EXISTS requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requester_id INT NOT NULL,
        patient_name VARCHAR(150) NULL,
        blood_group VARCHAR(5) NOT NULL,
        units_needed INT NOT NULL DEFAULT 1,
        urgency ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
        is_emergency TINYINT(1) NOT NULL DEFAULT 0,
        hospital_id INT NOT NULL,
        status ENUM('Pending','Matched','Fulfilled','Cancelled') NOT NULL DEFAULT 'Pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (requester_id),
        INDEX (blood_group),
        INDEX (hospital_id),
        INDEX (status)
    )",
    "appointments" => "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        donor_id INT NOT NULL,
        hospital_id INT NOT NULL,
        scheduled_date DATETIME NOT NULL,
        status ENUM('Scheduled','Completed','Cancelled','No-show') NOT NULL DEFAULT 'Scheduled',
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        location VARCHAR(150) NULL,
        UNIQUE KEY uq_hospital_slot (hospital_id, scheduled_date),
        INDEX (donor_id),
        INDEX (status)
    )",
    "donation_history" => "CREATE TABLE IF NOT EXISTS donation_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        donor_id INT NOT NULL,
        date DATE NOT NULL,
        units INT NOT NULL DEFAULT 1,
        location VARCHAR(200) NULL,
        hospital_id INT NULL,
        appointment_id INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_donor_date (donor_id, date),
        INDEX (hospital_id),
        INDEX (appointment_id)
    )",
    "inventory" => "CREATE TABLE IF NOT EXISTS inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hospital_id INT NOT NULL,
        blood_group VARCHAR(5) NOT NULL,
        units INT NOT NULL DEFAULT 0,
        UNIQUE KEY uq_hospital_blood_group (hospital_id, blood_group)
    )",
    "camps" => "CREATE TABLE IF NOT EXISTS camps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        date DATE NOT NULL,
        location VARCHAR(255) NOT NULL,
        description TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",
    "password_resets" => "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        token VARCHAR(64) NULL,
        expires_at DATETIME NULL,
        used_at DATETIME NULL
    )"
];

foreach ($schema as $table => $sql) {
    if (!mysqli_query($conn, $sql)) {
        error_log("BloodBridge could not initialize `$table`: " . mysqli_error($conn));
        http_response_code(503);
        exit("BloodBridge is temporarily unavailable. Please try again shortly.");
    }
}

foreach ([
    ["users", "is_active", "TINYINT(1) NOT NULL DEFAULT 1"],
    ["users", "status", "VARCHAR(20) DEFAULT 'pending'"],
    ["users", "failed_login_attempts", "INT NOT NULL DEFAULT 0"],
    ["users", "locked_until", "DATETIME NULL"],
    ["donors", "gender", "VARCHAR(20) NOT NULL DEFAULT 'Prefer not to say'"],
    ["donors", "is_available", "TINYINT(1) NOT NULL DEFAULT 1"],
    ["donors", "deferral_until", "DATE NULL"],
    ["donors", "deferral_reason", "VARCHAR(255) NULL"],
    ["donors", "is_verified", "TINYINT(1) NOT NULL DEFAULT 0"],
    ["hospitals", "is_verified", "TINYINT(1) NOT NULL DEFAULT 0"],
    ["hospitals", "verified_at", "DATETIME NULL"],
    ["hospitals", "contact_phone", "VARCHAR(30) NULL"],
    ["appointments", "scheduled_date", "DATETIME NULL"],
    ["appointments", "location", "VARCHAR(150) NULL"],
    ["donation_history", "date", "DATE NULL"],
    ["donation_history", "appointment_id", "INT NULL"],
    ["requests", "requester_id", "INT NULL"],
    ["requests", "units_needed", "INT NOT NULL DEFAULT 1"],
    ["requests", "patient_name", "VARCHAR(150) NULL"],
    ["requests", "is_emergency", "TINYINT(1) NOT NULL DEFAULT 0"],
    ["camps", "date", "DATE NULL"]
] as $column) {
    if (!bdms_ensure_column($conn, $column[0], $column[1], $column[2])) {
        http_response_code(503);
        exit("BloodBridge could not finish a database update. Please contact the administrator.");
    }
}

if (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users LIMIT 1")) === 0) {
    $hash = password_hash("password123", PASSWORD_DEFAULT);
    $seed_users = [
        ["Admin User", "admin@bdms.test", "670000001", "admin", "active"],
        ["Achu Ngwa", "achu@bdms.test", "670000002", "donor", "active"],
        ["Delphine Ashu", "delphine@bdms.test", "670000003", "donor", "pending"],
        ["Brenda Fon", "brenda@bdms.test", "670000004", "recipient", "active"],
        ["Buea General Hospital", "buea.general@bdms.test", "670000005", "hospital", "active"]
    ];
    $insert_user = mysqli_prepare($conn, "INSERT INTO users (name,email,password,phone,role,status,is_active) VALUES (?,?,?,?,?,?,1)");
    if ($insert_user) {
        foreach ($seed_users as $seed) {
            mysqli_stmt_bind_param($insert_user, "ssssss", $seed[0], $seed[1], $hash, $seed[2], $seed[3], $seed[4]);
            if (!mysqli_stmt_execute($insert_user)) {
                error_log("BloodBridge could not create a demo account: " . mysqli_stmt_error($insert_user));
            }
        }
    }
    $donor_user_id = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE email='achu@bdms.test'"))["id"] ?? 0);
    $recipient_user_id = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE email='brenda@bdms.test'"))["id"] ?? 0);
    $hospital_user_id = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE email='buea.general@bdms.test'"))["id"] ?? 0);
    if ($donor_user_id && $recipient_user_id && $hospital_user_id) {
        mysqli_query($conn, "INSERT INTO donors (user_id,blood_group,age,gender,weight,city,is_verified) VALUES ($donor_user_id,'O+',32,'Prefer not to say',72,'Buea',1)");
        mysqli_query($conn, "INSERT INTO hospitals (user_id,name,address,city,license_number,is_verified,verified_at) VALUES ($hospital_user_id,'Buea General Hospital','123 Molyko Road','Buea','LIC-0001',1,NOW())");
        $hospital_id = mysqli_insert_id($conn);
        foreach (["O+" => 24, "O-" => 6, "A+" => 18, "A-" => 9, "B+" => 14, "B-" => 4, "AB+" => 10, "AB-" => 3] as $group => $units) {
            $safe_group = mysqli_real_escape_string($conn, $group);
            mysqli_query($conn, "INSERT INTO inventory (hospital_id,blood_group,units) VALUES ($hospital_id,'$safe_group',$units)");
        }
        mysqli_query($conn, "INSERT INTO requests (requester_id,blood_group,units_needed,urgency,hospital_id,status) VALUES ($recipient_user_id,'O+',2,'High',$hospital_id,'Pending')");
    }
}

function safe($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, "UTF-8");
}

function clean($conn, $text)
{
    return mysqli_real_escape_string($conn, trim((string)$text));
}

function make_csrf_token()
{
    return generateCsrfToken();
}

function check_csrf_token()
{
    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        http_response_code(400);
        exit("Your session expired. Please refresh the page and try again.");
    }
}
?>
