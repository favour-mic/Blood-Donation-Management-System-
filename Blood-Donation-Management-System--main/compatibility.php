<?php

require "includes/config.php";
$page_title = "Blood Compatibility";
require "includes/public_header.php";

$compat_donate_to = array(
    'O-'  => array('O-','O+','A-','A+','B-','B+','AB-','AB+'),
    'O+'  => array('O+','A+','B+','AB+'),
    'A-'  => array('A-','A+','AB-','AB+'),
    'A+'  => array('A+','AB+'),
    'B-'  => array('B-','B+','AB-','AB+'),
    'B+'  => array('B+','AB+'),
    'AB-' => array('AB-','AB+'),
    'AB+' => array('AB+'),
);
$compat_receive_from = array(
    'O-'  => array('O-'),
    'O+'  => array('O-','O+'),
    'A-'  => array('O-','A-'),
    'A+'  => array('O-','O+','A-','A+'),
    'B-'  => array('O-','B-'),
    'B+'  => array('O-','O+','B-','B+'),
    'AB-' => array('O-','A-','B-','AB-'),
    'AB+' => array('O-','O+','A-','A+','B-','B+','AB-','AB+'),
);

$selected_type = "";
if (isset($_GET["type"]) && is_string($_GET["type"]) && isset($compat_donate_to[$_GET["type"]])) {
    $selected_type = $_GET["type"];
}
?>

<section class="section">
  <div class="container">
    <p class="label-eyebrow">Blood Compatibility</p>
    <h1 class="h1-display">Who can donate to who?</h1>
    <p class="body-lede" style="margin-top:12px;">Pick a blood type to see red blood cell compatibility. Healthcare professionals must confirm compatibility before transfusion.</p>

    <?php if ($selected_type !== "" && isset($compat_donate_to[$selected_type])): ?>
      <div class="grid-2 compat-results" id="compatibility-results" aria-live="polite">
        <div class="card">
          <h2 class="h3-card-title" id="compat-result-title"><?= safe($selected_type) ?> red blood cell compatibility</h2>
          <h3 class="h4-sub-label" style="margin-top:14px;">Can donate red blood cells to</h3>
          <p class="body-default" id="compat-donate-to" style="margin-top:6px;"><?= safe(implode(", ", $compat_donate_to[$selected_type])) ?></p>
        </div>
        <div class="card">
          <h3 class="h4-sub-label">Can receive red blood cells from</h3>
          <p class="body-default" id="compat-receive-from" style="margin-top:6px;"><?= safe(implode(", ", $compat_receive_from[$selected_type])) ?></p>
        </div>
      </div>
    <?php else: ?>
      <div class="grid-2 compat-results" id="compatibility-results" aria-live="polite" hidden>
        <div class="card">
          <h2 class="h3-card-title" id="compat-result-title"></h2>
          <h3 class="h4-sub-label" style="margin-top:14px;">Can donate red blood cells to</h3>
          <p class="body-default" id="compat-donate-to" style="margin-top:6px;"></p>
        </div>
        <div class="card">
          <h3 class="h4-sub-label">Can receive red blood cells from</h3>
          <p class="body-default" id="compat-receive-from" style="margin-top:6px;"></p>
        </div>
      </div>
      <p class="label-small" id="compatibility-prompt" style="margin-top:20px;">Select a blood type below to view its matches.</p>
    <?php endif; ?>

    <div class="compat-grid" id="compatibility-groups" style="margin-top: 28px;">
      <?php
      $all_types = array('O-','O+','A-','A+','B-','B+','AB-','AB+');
      foreach ($all_types as $type) {
      ?>
        <a href="?type=<?php echo rawurlencode($type); ?>" class="card compat-cell<?= $selected_type === $type ? " is-selected" : "" ?>" data-blood-type="<?= safe($type) ?>" data-donate-to="<?= safe(implode(", ", $compat_donate_to[$type])) ?>" data-receive-from="<?= safe(implode(", ", $compat_receive_from[$type])) ?>" <?= $selected_type === $type ? 'aria-current="page"' : "" ?>>
          <div class="compat-cell__type"><?php echo $type; ?></div>
        </a>
      <?php } ?>
    </div>
  </div>
</section>

<?php require "includes/public_footer.php"; ?>
