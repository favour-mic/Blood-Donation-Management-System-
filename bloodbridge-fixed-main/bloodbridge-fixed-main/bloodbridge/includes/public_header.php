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


    <header id="header">
        <div class="container1">
            <nav>
                <a href="index.php" class="logo">
                    <i class="fa-solid fa-droplet"></i>
                    BloodBridge
                </a>
                <ul class="first-ul" id="firstUl">
                    <li>
                        <a href="index.php">home</a>
                    </li>
                    <li>
                        <a href="eligibility.php">eligibility</a>
                    </li>
                    <li>
                        <a href="compatibility.php">compatibility</a>
                    </li>
                    <li>
                        <a href="donationcenter.php">donation center</a>
                    </li>
                </ul>
                <ul class="second-ul" id="secondUl">
                    <li>
                        <button class="header-btn" type="button" onclick="window.location.href='login.php'">Log In</button>
                    </li>
                    <li>
                        <button class="header-btn" type="button" onclick="window.location.href='register.php'">
                            get started
                        </button>
                    </li>
                </ul>
                
                <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
        <div class="nav-backdrop" id="navBackdrop"></div>
    </header>
