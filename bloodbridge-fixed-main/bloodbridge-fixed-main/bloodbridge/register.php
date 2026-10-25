<?php
require_once "includes/config.php";

$csrf_token = generateCsrfToken();
$errors = [];
$success = "";
$roles = ["donor", "recipient", "hospital"];
$selected_role = $_POST["role"] ?? $_GET["role"] ?? "donor";
if (!in_array($selected_role, $roles, true)) {
    $selected_role = "donor";
}

// Keep entered non-secret values available when server-side validation fails.
$old = $_POST;
$requesting_for_options = ["Myself", "Family member", "Someone else"];

// The final submit validates both steps again; browser validation is only a usability aid.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $hospital_name = trim($_POST["hospital_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";
    $blood_group = trim($_POST["blood_group"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $age = filter_var($_POST["age"] ?? null, FILTER_VALIDATE_INT);
    $weight = filter_var($_POST["weight"] ?? null, FILTER_VALIDATE_FLOAT);
    $city = "";
    if ($selected_role === "donor") {
        $city = trim($_POST["donor_city"] ?? "");
    } elseif ($selected_role === "recipient") {
        $city = trim($_POST["recipient_city"] ?? "");
    } elseif ($selected_role === "hospital") {
        $city = trim($_POST["hospital_city"] ?? "");
    }
    $address = trim($_POST["hospital_address"] ?? "");
    $license = trim($_POST["license_number"] ?? "");
    $requesting_for = trim($_POST["requesting_for"] ?? "");
    $preferred_hospital = trim($_POST["preferred_hospital"] ?? "");

    if (!verifyCsrfToken($_POST["csrf_token"] ?? "")) {
        $errors[] = "Your session expired. Please refresh the page and try again.";
    }
    if (!in_array($selected_role, $roles, true)) {
        $errors[] = "Please select a valid account type.";
    }
    if ($full_name === "" || mb_strlen($full_name) > 150) {
        $errors[] = "Enter a name of 150 characters or fewer.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
        $errors[] = "Please enter a valid email address.";
    }
    if ($phone === "" || mb_strlen($phone) > 30) {
        $errors[] = "Enter a phone number of 30 characters or fewer.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Validate the active role's second-step fields before any account data is written.
    if ($selected_role === "donor") {
        if (!in_array($blood_group, ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"], true)) {
            $errors[] = "Please select a valid blood group.";
        }
        if ($age === false || $age < 18 || $age > 100) {
            $errors[] = "Age must be between 18 and 100.";
        }
        if (!in_array($gender, ["Female", "Male", "Non-binary", "Prefer not to say"], true)) {
            $errors[] = "Please select a gender option.";
        }
        if ($weight === false || $weight <= 0 || $weight > 500) {
            $errors[] = "Please enter a valid weight.";
        }
        if ($city === "") {
            $errors[] = "City is required.";
        }
    } elseif ($selected_role === "recipient") {
        if ($city === "") {
            $errors[] = "City is required.";
        }
        if (!in_array($requesting_for, $requesting_for_options, true)) {
            $errors[] = "Choose who the blood request is usually for.";
        }
        if (mb_strlen($preferred_hospital) > 200) {
            $errors[] = "Preferred hospital must be 200 characters or fewer.";
        }
    } elseif ($selected_role === "hospital") {
        if ($hospital_name === "" || mb_strlen($hospital_name) > 200) {
            $errors[] = "Enter a hospital name of 200 characters or fewer.";
        }
        if ($address === "" || mb_strlen($address) > 255) {
            $errors[] = "Enter a street address of 255 characters or fewer.";
        }
        if ($city === "" || mb_strlen($city) > 100) {
            $errors[] = "Enter a city of 100 characters or fewer.";
        }
        if ($license === "" || mb_strlen($license) > 50) {
            $errors[] = "Enter a license number of 50 characters or fewer.";
        }
    }

    // A parameterized lookup prevents duplicate emails and avoids SQL injection.
    if (!$errors) {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        if (!$check) {
            error_log("BloodBridge registration lookup failed: " . mysqli_error($conn));
            $errors[] = "We could not create the account right now. Please try again.";
        } else {
            mysqli_stmt_bind_param($check, "s", $email);
            if (!mysqli_stmt_execute($check)) {
                error_log("BloodBridge registration lookup execution failed: " . mysqli_stmt_error($check));
                $errors[] = "We could not create the account right now. Please try again.";
            } else {
                mysqli_stmt_store_result($check);
                if (mysqli_stmt_num_rows($check) > 0) {
                    $errors[] = "An account with this email already exists.";
                }
            }
            mysqli_stmt_close($check);
        }
    }

    // Store the account and role profile atomically so partial registrations cannot remain.
    if (!$errors) {
        if (!mysqli_begin_transaction($conn)) {
            error_log("BloodBridge registration transaction could not start: " . mysqli_error($conn));
            $errors[] = "We could not create the account right now. Please try again.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $user_insert = mysqli_prepare(
                $conn,
                "INSERT INTO users (name,email,password,phone,role,status,is_active) VALUES (?,?,?,?,?,'pending',1)"
            );
            $created = false;
            if ($user_insert) {
                mysqli_stmt_bind_param($user_insert, "sssss", $full_name, $email, $password_hash, $phone, $selected_role);
                $created = mysqli_stmt_execute($user_insert);
                if (!$created) {
                    error_log("BloodBridge registration insert failed: " . mysqli_stmt_error($user_insert));
                }
                $new_user_id = (int)mysqli_insert_id($conn);
                mysqli_stmt_close($user_insert);
            } else {
                error_log("BloodBridge registration statement failed: " . mysqli_error($conn));
                $new_user_id = 0;
            }

            if ($created && $selected_role === "donor") {
                $profile = mysqli_prepare(
                    $conn,
                    "INSERT INTO donors (user_id,blood_group,age,gender,weight,city) VALUES (?,?,?,?,?,?)"
                );
                if ($profile) {
                    mysqli_stmt_bind_param($profile, "isisds", $new_user_id, $blood_group, $age, $gender, $weight, $city);
                    $created = mysqli_stmt_execute($profile);
                    if (!$created) {
                        error_log("BloodBridge donor profile insert failed: " . mysqli_stmt_error($profile));
                    }
                    mysqli_stmt_close($profile);
                } else {
                    error_log("BloodBridge donor profile statement failed: " . mysqli_error($conn));
                    $created = false;
                }
            } elseif ($created && $selected_role === "recipient") {
                $profile = mysqli_prepare(
                    $conn,
                    "INSERT INTO recipient_profiles (user_id,city,requesting_for,preferred_hospital) VALUES (?,?,?,?)"
                );
                if ($profile) {
                    $preferred_hospital_value = $preferred_hospital === "" ? null : $preferred_hospital;
                    mysqli_stmt_bind_param(
                        $profile,
                        "isss",
                        $new_user_id,
                        $city,
                        $requesting_for,
                        $preferred_hospital_value
                    );
                    $created = mysqli_stmt_execute($profile);
                    if (!$created) {
                        error_log("BloodBridge recipient profile insert failed: " . mysqli_stmt_error($profile));
                    }
                    mysqli_stmt_close($profile);
                } else {
                    error_log("BloodBridge recipient profile statement failed: " . mysqli_error($conn));
                    $created = false;
                }
            } elseif ($created && $selected_role === "hospital") {
                $profile = mysqli_prepare(
                    $conn,
                    "INSERT INTO hospitals (user_id,name,address,city,license_number,contact_phone) VALUES (?,?,?,?,?,?)"
                );
                if ($profile) {
                    mysqli_stmt_bind_param($profile, "isssss", $new_user_id, $hospital_name, $address, $city, $license, $phone);
                    $created = mysqli_stmt_execute($profile);
                    if (!$created) {
                        error_log("BloodBridge hospital profile insert failed: " . mysqli_stmt_error($profile));
                    }
                    mysqli_stmt_close($profile);
                } else {
                    error_log("BloodBridge hospital profile statement failed: " . mysqli_error($conn));
                    $created = false;
                }
            }

            if ($created && mysqli_commit($conn)) {
                $success = "Your account has been created and is waiting for administrator verification.";
                $old = [];
            } else {
                mysqli_rollback($conn);
                $errors[] = "We could not create the account right now. Please check your details and try again.";
            }
        }
    }
}

$active_step = $_SERVER["REQUEST_METHOD"] === "POST" ? 2 : 1;
$page_title = "Create Account";
$page_stylesheets = ["css/register-wizard.css"];
require "includes/public_header.php";
?>
    <main class="auth-container auth-container--register register-wizard-page">
        <section class="register-wizard register-wizard--<?= safe($selected_role) ?>" id="register-wizard">
            <header class="register-heading">
                <p class="register-eyebrow" id="register-eyebrow">Join BDMS · <?= safe($selected_role) ?></p>
                <h1 id="register-title"><?= $selected_role === "hospital" ? "Create your facility account" : "Create your account" ?></h1>
                <p class="register-intro">A few details help us connect you with the right people.</p>
            </header>

            <ol class="register-progress" aria-label="Registration progress">
                <li class="<?= $active_step === 1 ? "is-current" : "is-complete" ?>" id="progress-account">
                    <span>1</span><strong>Account</strong>
                </li>
                <li class="<?= $active_step === 2 ? "is-current" : "" ?>" id="progress-details">
                    <span>2</span><strong>Details</strong>
                </li>
            </ol>

            <?php if ($success): ?>
                <div class="register-success" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <p><?= safe($success) ?></p>
                    <a class="register-button" href="login.php">Go to sign in</a>
                </div>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="register-errors" role="alert">
                        <?php foreach ($errors as $error): ?><p><?= safe($error) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="register.php" id="register-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= safe($csrf_token) ?>">
                    <input type="hidden" name="step" id="register-step-value" value="<?= $active_step ?>">

                    <!-- Step one captures the shared account credentials before role details appear. -->
                    <section class="register-step <?= $active_step === 1 ? "is-current" : "" ?>" id="register-step-one" aria-labelledby="register-title">
                        <p class="step-caption">Step 1 of 2 · Account information</p>
                        <fieldset class="role-group">
                            <legend>I am joining as</legend>
                            <div class="role-options">
                                <?php foreach (["donor" => "Donor", "recipient" => "Recipient", "hospital" => "Hospital"] as $role => $label): ?>
                                    <label class="role-option" for="role-<?= safe($role) ?>">
                                        <input type="radio" id="role-<?= safe($role) ?>" name="role" value="<?= safe($role) ?>" <?= $selected_role === $role ? "checked" : "" ?> required>
                                        <span><?= safe($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <div class="form-group">
                            <label id="contact-name-label" for="full_name"><?= $selected_role === "hospital" ? "Contact name" : "Full name" ?></label>
                            <input id="full_name" name="full_name" maxlength="150" value="<?= safe($old["full_name"] ?? "") ?>" autocomplete="name" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email address</label>
                                <input id="email" name="email" type="email" maxlength="180" value="<?= safe($old["email"] ?? "") ?>" autocomplete="email" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone number</label>
                                <input id="phone" name="phone" type="tel" maxlength="30" value="<?= safe($old["phone"] ?? "") ?>" autocomplete="tel" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm password</label>
                                <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
                            </div>
                        </div>
                        <button class="register-button register-button--next" id="register-next" type="button">
                            Continue to details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                        <p class="register-login">Already have an account? <a href="login.php">Sign in</a></p>
                    </section>

                    <!-- Step two shows only the fields belonging to the selected role. -->
                    <section class="register-step <?= $active_step === 2 ? "is-current" : "" ?>" id="register-step-two" aria-labelledby="details-title">
                        <p class="step-caption" id="details-caption">Step 2 of 2 · Donor details</p>
                        <h2 class="details-title" id="details-title">Tell us a little more about you</h2>

                        <section class="register-role-fields" data-role-fields="donor" aria-label="Donor details">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="blood_group">Blood group</label>
                                    <select id="blood_group" name="blood_group">
                                        <option value="">Select blood group</option>
                                        <?php foreach (["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"] as $group): ?>
                                            <option value="<?= safe($group) ?>" <?= ($old["blood_group"] ?? "") === $group ? "selected" : "" ?>><?= safe($group) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender">
                                        <option value="">Select an option</option>
                                        <?php foreach (["Female", "Male", "Non-binary", "Prefer not to say"] as $option): ?>
                                            <option value="<?= safe($option) ?>" <?= ($old["gender"] ?? "") === $option ? "selected" : "" ?>><?= safe($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="age">Age</label>
                                    <input id="age" type="number" name="age" min="18" max="100" value="<?= safe($old["age"] ?? "") ?>">
                                </div>
                                <div class="form-group">
                                    <label for="weight">Weight (kg)</label>
                                    <input id="weight" type="number" step="0.1" min="1" max="500" name="weight" value="<?= safe($old["weight"] ?? "") ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="donor-city">City</label>
                                <input id="donor-city" name="donor_city" value="<?= safe($old["donor_city"] ?? "") ?>" autocomplete="address-level2">
                            </div>
                        </section>

                        <section class="register-role-fields" data-role-fields="recipient" aria-label="Recipient details">
                            <div class="form-group">
                                <label for="recipient-city">City</label>
                                <input id="recipient-city" name="recipient_city" value="<?= safe($old["recipient_city"] ?? "") ?>" autocomplete="address-level2">
                            </div>
                            <div class="form-group">
                                <label for="requesting_for">Usually requesting for</label>
                                <select id="requesting_for" name="requesting_for">
                                    <option value="">Choose one</option>
                                    <?php foreach ($requesting_for_options as $option): ?>
                                        <option value="<?= safe($option) ?>" <?= ($old["requesting_for"] ?? "Myself") === $option ? "selected" : "" ?>><?= safe($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="preferred_hospital">Preferred hospital <span>(optional)</span></label>
                                <input id="preferred_hospital" name="preferred_hospital" maxlength="200" value="<?= safe($old["preferred_hospital"] ?? "") ?>" placeholder="Hospital name">
                                <small class="field-help">You can change the hospital when you make a request.</small>
                            </div>
                        </section>

                        <section class="register-role-fields" data-role-fields="hospital" aria-label="Hospital details">
                            <div class="form-group">
                                <label for="hospital_name">Hospital name</label>
                                <input id="hospital_name" name="hospital_name" maxlength="200" value="<?= safe($old["hospital_name"] ?? "") ?>">
                            </div>
                            <div class="form-group">
                                <label for="hospital_address">Street address</label>
                                <input id="hospital_address" name="hospital_address" maxlength="255" value="<?= safe($old["hospital_address"] ?? "") ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="hospital_city">City</label>
                                    <input id="hospital_city" name="hospital_city" maxlength="100" value="<?= safe($old["hospital_city"] ?? "") ?>">
                                </div>
                                <div class="form-group">
                                    <label for="license_number">License number</label>
                                    <input id="license_number" name="license_number" maxlength="50" value="<?= safe($old["license_number"] ?? "") ?>">
                                </div>
                            </div>
                        </section>

                        <div class="register-actions">
                            <button class="register-button register-button--back" id="register-back" type="button">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
                            </button>
                            <button class="register-button" id="register-submit" type="submit">Create account</button>
                        </div>
                    </section>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <!-- The wizard preserves all role values while only enabling the selected role's fields. -->
    <script>
        (function () {
            var wizard = document.getElementById("register-wizard");
            var form = document.getElementById("register-form");
            if (!wizard || !form) return;

            var stepOne = document.getElementById("register-step-one");
            var stepTwo = document.getElementById("register-step-two");
            var stepValue = document.getElementById("register-step-value");
            var accountProgress = document.getElementById("progress-account");
            var detailsProgress = document.getElementById("progress-details");
            var activeRole = <?= json_encode($selected_role) ?>;
            var startAtDetails = <?= $active_step === 2 ? "true" : "false" ?>;
            var roleLabels = {
                donor: { eyebrow: "Join BDMS · Donor", caption: "Step 2 of 2 · Donor details", title: "Create your account" },
                recipient: { eyebrow: "Join BDMS · Recipient", caption: "Step 2 of 2 · Recipient details", title: "Create your account" },
                hospital: { eyebrow: "Join BDMS · Hospital", caption: "Step 2 of 2 · Hospital details", title: "Create your facility account" }
            };

            wizard.classList.add("is-enhanced");

            function showStep(step) {
                var isDetails = step === 2;
                stepOne.classList.toggle("is-current", !isDetails);
                stepTwo.classList.toggle("is-current", isDetails);
                stepValue.value = isDetails ? "2" : "1";
                accountProgress.classList.toggle("is-current", !isDetails);
                accountProgress.classList.toggle("is-complete", isDetails);
                detailsProgress.classList.toggle("is-current", isDetails);
                updateRole();
                if (isDetails) {
                    stepTwo.querySelector(".step-caption").textContent = roleLabels[activeRole].caption;
                }
            }

            function updateRole() {
                var selected = form.querySelector('input[name="role"]:checked');
                if (!selected) return;
                activeRole = selected.value;
                wizard.className = "register-wizard register-wizard--" + activeRole + " is-enhanced";
                document.getElementById("register-eyebrow").textContent = roleLabels[activeRole].eyebrow;
                document.getElementById("register-title").textContent = roleLabels[activeRole].title;
                document.getElementById("contact-name-label").textContent =
                    activeRole === "hospital" ? "Contact name" : "Full name";

                form.querySelectorAll("[data-role-fields]").forEach(function (section) {
                    var isActive = section.getAttribute("data-role-fields") === activeRole;
                    section.classList.toggle("is-active", isActive);
                    section.querySelectorAll("input,select").forEach(function (field) {
                        field.disabled = !isActive || !stepTwo.classList.contains("is-current");
                        field.required = isActive && stepTwo.classList.contains("is-current") && field.id !== "preferred_hospital";
                    });
                });
                if (stepTwo.classList.contains("is-current")) {
                    stepTwo.querySelector(".step-caption").textContent = roleLabels[activeRole].caption;
                }
            }

            document.getElementById("register-next").addEventListener("click", function () {
                if (!form.reportValidity()) return;
                var password = document.getElementById("password");
                var confirm = document.getElementById("confirm_password");
                confirm.setCustomValidity(password.value === confirm.value ? "" : "Passwords do not match.");
                if (!confirm.reportValidity()) return;
                showStep(2);
                document.getElementById("details-title").focus();
            });
            document.getElementById("register-back").addEventListener("click", function () {
                showStep(1);
                document.getElementById("full_name").focus();
            });
            document.getElementById("confirm_password").addEventListener("input", function () {
                this.setCustomValidity(this.value === document.getElementById("password").value ? "" : "Passwords do not match.");
            });
            form.querySelectorAll('input[name="role"]').forEach(function (radio) {
                radio.addEventListener("change", updateRole);
            });
            form.addEventListener("submit", function (event) {
                var password = document.getElementById("password");
                var confirm = document.getElementById("confirm_password");
                confirm.setCustomValidity(password.value === confirm.value ? "" : "Passwords do not match.");
                if (stepValue.value !== "2" || !form.reportValidity()) {
                    event.preventDefault();
                }
            });

            updateRole();
            showStep(startAtDetails ? 2 : 1);
        }());
    </script>
<?php require "includes/public_footer.php"; ?>
