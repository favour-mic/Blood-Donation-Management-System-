<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
requireRole(["donor"]);

$stmt = mysqli_prepare($conn, "SELECT dh.date AS donated_at, dh.units, 
h.name AS hospital_name, h.city FROM donation_history dh LEFT JOIN 
donors d ON d.id = dh.donor_id LEFT JOIN hospitals h ON h.id
 = dh.hospital_id WHERE d.user_id = ? ORDER BY dh.date DESC");
$user_id = (int) $_SESSION["user_id"];
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);
?>
<?php $page_title = "Donation History";
require "header.php"; ?><main class="role-main">
    <div class="role-container">
        <p class="role-kicker">Donor</p>
        <h1 class="role-title">Donation History</h1>
        <section class="role-table-wrap">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Hospital</th>
                        <th>Location</th>
                        <th>Units</th>
                    </tr>
                </thead>
                <tbody><?php if (mysqli_num_rows($history) === 0): ?>
                    <tr>
                        <td colspan="4">No donations recorded yet.</td>
                    </tr><?php endif; ?><?php while ($row = mysqli_fetch_assoc($history)): ?><tr>
                        <td><?= htmlspecialchars(date("M j, Y", strtotime($row["donated_at"] ?? "now"))) ?></td>
                        <td><?= htmlspecialchars($row["hospital_name"] ?? "Unknown") ?></td>
                        <td><?= htmlspecialchars($row["city"] ?? "-") ?></td>
                        <td><?= (int)$row["units"] ?></td>
                    </tr><?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>
<?php require "../includes/role_footer.php"; ?>