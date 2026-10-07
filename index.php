<?php

require "includes/config.php";

$total_donors = 0;
$result = mysqli_query($conn, "SELECT id FROM users WHERE role='donor' AND status='active'");
$total_donors = mysqli_num_rows($result);

$total_units = 0;
$result = mysqli_query($conn, "SELECT units FROM donation_history");
while ($row = mysqli_fetch_assoc($result)) {
    $total_units = $total_units + $row['units'];
}

$result = mysqli_query($conn, "SELECT id FROM hospitals");
$total_hospitals = mysqli_num_rows($result);

$page_title = "Home";
require "includes/public_header.php";
?>

<section class="hero">
    <div class="container">
        <p class="label-eyebrow">Blood Donation Management System</p>
        <h1 class="h1-display">Every donor found faster is a life brought back from the edge.</h1>
        <p class="body-lede">BDMS connects donors, recipients, and hospitals in one place, so blood gets to the people who need it without the usual delays.</p>
        <div class="hero__actions">
            <a href="register.php?role=donor" class="btn btn-white">Become a Donor</a>
            <a href="register.php?role=recipient" class="btn btn-outline-white">Request Blood</a>
            <!-- <a href="bdms-admin/bdms-admin/login.php" class="btn btn-outline-white">Admin Access</a> -->
        </div>
        <div class="hero__stats count-container">
            <div>
                <div class="hero__stat-value" data-target="<?php echo $total_donors ?>">
                    <?php echo $total_donors; ?>0
                </div>
                <div class="hero__stat-label">Verified Donors</div>
            </div>
            <div>
                <div class="hero__stat-value" data-target="<?php echo $total_units ?>">0</div>
                <div class="hero__stat-label">Units Collected</div>
            </div>
            <div>
                <div class="hero__stat-value" data-target="<?php echo $total_hospitals ?>">0</div>
                <div class="hero__stat-label">Partner Hospitals</div>
            </div>  
        </div>
    </div>
</section>

<section class="section">
  <div class="container">
    <p class="label-eyebrow">How it works</p>
    <h2 class="h2-section">One system, four roles</h2>
    <div class="grid-4" style="margin-top: 36px;">
      <div class="card card-hover-lift">
        <div class="icon-circle icon-circle--soft-navy">🛡️</div>
        <h4 class="h4-sub-label">Admin</h4>
        <p class="body-default" style="margin-top:6px;">Verifies accounts, watches trends, and keeps the whole system running smoothly.</p>
      </div>
      <div class="card card-hover-lift">
        <div class="icon-circle icon-circle--soft-cherry">🩸</div>
        <h4 class="h4-sub-label">Donor</h4>
        <p class="body-default" style="margin-top:6px;">Sets availability, tracks the next eligible donation date, and sees donation history.</p>
      </div>
      <div class="card card-hover-lift">
        <div class="icon-circle icon-circle--soft-cherry">🙋</div>
        <h4 class="h4-sub-label">Recipient</h4>
        <p class="body-default" style="margin-top:6px;">Searches for matching donors and tracks a blood request from start to finish.</p>
      </div>
      <div class="card card-hover-lift">
        <div class="icon-circle icon-circle--soft-teal">🏥</div>
        <h4 class="h4-sub-label">Hospital</h4>
        <p class="body-default" style="margin-top:6px;">Manages inventory, books appointments, and fulfills matched requests.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section--tinted">
  <div class="container grid-2">
    <div>
      <p class="label-eyebrow">Mission</p>
      <h2 class="h2-section">Donating shouldn't be complicated</h2>
      <p class="body-lede" style="margin-top:12px;">We built BDMS so the process of finding, verifying, and matching donors is simple enough that nobody has to wait longer than they should.</p>
      <a href="eligibility.php" class="btn btn-primary" style="margin-top: 20px;">See if you're eligible</a>
    </div>
    <div class="card">
      <h4 class="h4-sub-label">Why donors come back</h4>
      <ul class="checklist" style="margin-top:12px;">
        <li>✔ A clear next-eligible-date, calculated automatically</li>
        <li>✔ Full history of every donation you've made</li>
        <li>✔ One toggle to mark yourself available or not</li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band" style="  background: linear-gradient(150deg, var(--neutral-body) 0%, var(--cherry-primary) 55%,
    #c6295a 100%
  );">
      <h2 class="h2-section">Ready to save a life?</h2>
      <p class="body-lede" style="color: black; margin-top:8px;">It takes less than two minutes to register.</p>
      <a href="register.php" class="btn btn-white" style="margin-top: 20px;">Get Started</a>
    </div>
  </div>
</section>

<?php require "includes/public_footer.php"; ?>
