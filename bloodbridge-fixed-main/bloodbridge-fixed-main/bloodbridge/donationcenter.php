<?php

require "includes/config.php";


$page_title = 'Donation Centers';
$pageDesc   = 'Find admin-verified partner hospitals near you, or reach out with a general enquiry.';
$pageCSS    = 'donationcenters.css';
$activePage = 'centers';
require "includes/public_header.php";

$centers = [
    ['name' => 'Buea General Hospital',      'address' => 'classquaters Road, Buea'],
    ['name' => 'Douala Central Clinic',      'address' => '45 Akwa Avenue, Douala'],
    ['name' => 'Yaoundé Central Hospital',   'address' => '9 Bastos Street, Yaoundé'],
];

?>

<main>
    <section class="don-center" id="don-center">
        <div class="don-center-div1">
            <h4 class="eyebrow">Find a Center</h4>
            <h2>Donation Centers Near You</h2>
            <p class="don-p1">
                Every partner hospital on BDMS is admin-verified before it appears
                here.
            </p>
        </div>

        <div class="don-center-div2">
            <div class="sub-don-center-div1">
                <?php foreach ($centers as $center): ?>
                <div class="subs">
                    <div>
                        <h5><?php echo htmlspecialchars($center['name']); ?></h5>
                        <p class="don-p2"><?php echo htmlspecialchars($center['address']); ?></p>
                    </div>
                    <div class="verified">Verified</div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="sub-don-center-div2">
                <h5>General Inquiries</h5>
                <p class="don-p3">
                    Have a question about donating, requesting blood, or partnering as a
                    hospital?
                </p>
                <h5>Email</h5>
                <p class="don-p3">support@bdms.local</p>
                <h5>Hours</h5>
                <p class="don-p3">Mon – Sat, 8:00 AM – 6:00 PM</p>
                <a class="btn" href="/register.php?role=hospital">Register a Hospital</a>
            </div>
        </div>
    </section>
</main>

<?php require "includes/public_footer.php"; ?>

