<nav class="role-tabs" aria-label="Donor section navigation">
    <a class="<?= basename($_SERVER["PHP_SELF"]) === "dashboard.php" ? "is-active" : "" ?>" href="dashboard.php">
        Dashboard
    </a>
    <a class="<?= basename($_SERVER["PHP_SELF"]) === "profile.php" ? "is-active" : "" ?>" href="profile.php">
        Edit Profile
    </a>
    <a class="<?= basename($_SERVER["PHP_SELF"]) === "history.php" ? "is-active" : "" ?>" href="history.php">
        Donation History
    </a>
</nav>