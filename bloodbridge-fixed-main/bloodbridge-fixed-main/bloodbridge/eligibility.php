<?php

require "includes/config.php";
$page_title = "Eligibility Info";
require "includes/public_header.php";
?>

<section class="section">
  <div class="container">
    <p class="label-eyebrow">Why Donate Blood</p>
    <h1 class="h1-display">Who can donate?</h1>
    <p class="body-lede" style="margin-top:12px;">Most healthy adults can donate. Here's the basic checklist we ask every donor to meet.</p>

    <div class="grid-2" style="margin-top: 36px;">
      <div class="card">
        <h3 class="h3-card-title">General Eligibility</h3>
        <ul class="checklist" style="margin-top:12px;">
          <li>Aged 18 to 65</li>
          <li>Weigh at least 50kg</li>
          <li>Feeling well and not currently sick</li>
          <li>Hemoglobin level within the normal range</li>
          <li>No major surgery in the last 6 months</li>
        </ul>
      </div>
      <div class="card">
        <h3 class="h3-card-title">The 90-Day Rule</h3>
        <p class="body-default" style="margin-top:12px;">Whole blood donors need to wait at least 90 days between donations. This gives your body time to fully replace the donated blood cells. Your dashboard calculates your next eligible date automatically once you've made a donation.</p>
      </div>
    </div>

    <div class="card" style="margin-top: 32px; background: linear-gradient(160deg, var(--cherry-light), #fff);">
      <h3 class="h3-card-title">What to expect on donation day</h3>
      <div class="grid-3" style="margin-top: 20px;">
        <div>
          <h4 class="h4-sub-label">1. Quick check-up</h4>
          <p class="body-default" style="margin-top:6px;">A short health screening, including blood pressure and hemoglobin.</p>
        </div>
        <div>
          <h4 class="h4-sub-label">2. The donation</h4>
          <p class="body-default" style="margin-top:6px;">Takes about 10-15 minutes for a standard unit.</p>
        </div>
        <div>
          <h4 class="h4-sub-label">3. Rest and refresh</h4>
          <p class="body-default" style="margin-top:6px;">A short rest with a snack before you head out.</p>
        </div>
      </div>
    </div>

    <div class="cta-band" style="margin-top: 48px;  background: linear-gradient(150deg, var(--neutral-body) 0%, var(--cherry-primary) 55%,
    #c6295a 100%
  );">
      <h2 class="h2-section">Think you're eligible?</h2>
      <a href="register.php" class="btn btn-white" style="margin-top: 16px;">Register as a Donor</a>
    </div>
  </div>
</section>

<?php require "includes/public_footer.php"; ?>
