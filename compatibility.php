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
// Check whether the expected form/query value was supplied.
if (isset($_GET['type'])) {
    $selected_type = $_GET['type'];
}
?>

<section class="section">
  <div class="container">
    <p class="label-eyebrow">Blood Compatibility</p>
    <h1 class="h1-display">Who can donate to who?</h1>
    <p class="body-lede" style="margin-top:12px;">Pick a blood type below to see who they can donate to and receive from.</p>

    <div class="compat-grid" style="margin-top: 28px;">
      <?php
      $all_types = array('O-','O+','A-','A+','B-','B+','AB-','AB+');
      foreach ($all_types as $type) {
          $active_style = ($selected_type == $type) ? 'border-color: var(--cherry-primary); border-width: 2px; box-shadow: var(--shadow-glow-cherry);' : '';
      ?>
        <a href="?type=<?php echo $type; ?>" class="card compat-cell" style="<?php echo $active_style; ?>">
          <div class="compat-cell__type"><?php echo $type; ?></div>
        </a>
      <?php } ?>
    </div>

    <?php if ($selected_type != "" && isset($compat_donate_to[$selected_type])) { ?>
      <div class="grid-2" style="margin-top: 32px;">
        <div class="card">
          <h3 class="h3-card-title"><?php echo $selected_type; ?> can donate to:</h3>
          <p class="body-default" style="margin-top: 10px;">
            <?php echo implode(", ", $compat_donate_to[$selected_type]); ?>
          </p>
        </div>
        <div class="card">
          <h3 class="h3-card-title"><?php echo $selected_type; ?> can receive from:</h3>
          <p class="body-default" style="margin-top: 10px;">
            <?php echo implode(", ", $compat_receive_from[$selected_type]); ?>
          </p>
        </div>
      </div>
    <?php } else { ?>
      <p class="label-small" style="margin-top: 20px;">Select a blood type above to see its matches.</p>
    <?php } ?>
  </div>
</section>

<?php require "includes/public_footer.php"; ?>
