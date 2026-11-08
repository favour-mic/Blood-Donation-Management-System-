<nav class="role-tabs">
    <a class="<?= basename($_SERVER["PHP_SELF"]) === "dashboard.php" ? "is-active" : "" ?>" href="dashboard.php">Dashboard</a>
    <a class="<?= basename($_SERVER["PHP_SELF"]) === "inventory.php" ? "is-active" : "" ?>" href="inventory.php">Inventory</a>
    <a class="<?= basename($_SERVER["PHP_SELF"]) === "appointments.php" ? "is-active" : "" ?>" href="appointments.php">Appointments</a>
    <a href="appointments.php#requests">Fulfill requests</a>
</nav>