<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo safe($page_title); ?> - BloodBridge</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/design-system.css">
<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="css/footer.css">
<link rel="stylesheet" href="css/donationcenters.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<?php foreach ($page_stylesheets ?? [] as $stylesheet): ?>
<link rel="stylesheet" href="<?php echo safe($stylesheet); ?>">
<?php endforeach; ?>
</head>
<body>

<?php $current_page = basename($_SERVER["PHP_SELF"] ?? ""); ?>

    <header id="header">
        <div class="container1">
            <nav aria-label="Main navigation">
                <a href="index.php" class="logo">
                    <i class="fa-solid fa-droplet"></i>
                    BloodBridge
                </a>
                <div class="nav-drawer" id="navDrawer">
                <ul class="first-ul" id="firstUl">
                    <li>
                        <a href="index.php" class="<?= $current_page === "index.php" ? "is-active" : "" ?>" <?= $current_page === "index.php" ? 'aria-current="page"' : "" ?>>home</a>
                    </li>
                    <li>
                        <a href="eligibility.php" class="<?= $current_page === "eligibility.php" ? "is-active" : "" ?>" <?= $current_page === "eligibility.php" ? 'aria-current="page"' : "" ?>>eligibility</a>
                    </li>
                    <li>
                        <a href="compatibility.php" class="<?= $current_page === "compatibility.php" ? "is-active" : "" ?>" <?= $current_page === "compatibility.php" ? 'aria-current="page"' : "" ?>>compatibility</a>
                    </li>
                    <li>
                        <a href="donationcenter.php" class="<?= $current_page === "donationcenter.php" ? "is-active" : "" ?>" <?= $current_page === "donationcenter.php" ? 'aria-current="page"' : "" ?>>donation center</a>
                    </li>
                    <li>
                        <a href="contact.php" class="<?= $current_page === "contact.php" ? "is-active" : "" ?>" <?= $current_page === "contact.php" ? 'aria-current="page"' : "" ?>>contact</a>
                    </li>
                </ul>
                <ul class="second-ul" id="secondUl">
                    <li>
                        <a class="header-btn" href="login.php">Log In</a>
                    </li>
                    <li>
                        <a class="header-btn" href="register.php">
                            get started
                        </a>
                    </li>
                </ul>
                </div>
                
                <button class="nav-toggle" id="navToggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="navDrawer">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
        <div class="nav-backdrop" id="navBackdrop"></div>
    </header>
