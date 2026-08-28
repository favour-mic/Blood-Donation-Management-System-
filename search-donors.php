<main class="recipient-page">

    <section class="page-content">

        <!-- PAGE TITLE -->
        <h1 class="page-title">Search Donors</h1>

        <!-- PAGE NAVIGATION -->
        <div class="page-navigation">
            <a href="search-donors.php" class="active">Find Donors</a>
            <a href="submit-request.php">Submit Request</a>
            <a href="my-request.php">My Requests</a>
        </div>


        <!-- SEARCH SECTION -->
        <section class="search-section">

            <form class="search-form" action="search-donors.php" method="GET">

                <!-- BLOOD GROUP -->
                <div class="form-group">
                    <label for="blood_group">Blood Group</label>

                    <select name="blood_group" id="blood_group">
                        <option value="">Any</option>

                        <option value="O+"
                            <?php echo (($_GET['blood_group'] ?? '') === 'O+') ? 'selected' : ''; ?>>
                            O+
                        </option>

                        <option value="O-"
                            <?php echo (($_GET['blood_group'] ?? '') === 'O-') ? 'selected' : ''; ?>>
                            O-
                        </option>

                        <option value="A+"
                            <?php echo (($_GET['blood_group'] ?? '') === 'A+') ? 'selected' : ''; ?>>
                            A+
                        </option>

                        <option value="A-"
                            <?php echo (($_GET['blood_group'] ?? '') === 'A-') ? 'selected' : ''; ?>>
                            A-
                        </option>

                        <option value="B+"
                            <?php echo (($_GET['blood_group'] ?? '') === 'B+') ? 'selected' : ''; ?>>
                            B+
                        </option>

                        <option value="B-"
                            <?php echo (($_GET['blood_group'] ?? '') === 'B-') ? 'selected' : ''; ?>>
                            B-
                        </option>

                        <option value="AB+"
                            <?php echo (($_GET['blood_group'] ?? '') === 'AB+') ? 'selected' : ''; ?>>
                            AB+
                        </option>

                        <option value="AB-"
                            <?php echo (($_GET['blood_group'] ?? '') === 'AB-') ? 'selected' : ''; ?>>
                            AB-
                        </option>

                    </select>
                </div>


                <!-- CITY -->
                <div class="form-group">

                    <label for="city">City</label>

                    <input
                        type="text"
                        name="city"
                        id="city"
                        placeholder="e.g. Buea"
                        value="<?php echo htmlspecialchars($_GET['city'] ?? ''); ?>"
                    >

                </div>


                <!-- SEARCH BUTTON -->
                <button type="submit" class="search-button">
                    Search
                </button>

            </form>

        </section>


        <!-- SEARCH RESULT INFORMATION -->
        <?php

        $blood_group = trim($_GET['blood_group'] ?? '');
        $city = trim($_GET['city'] ?? '');

        ?>

        <?php if ($blood_group !== '' || $city !== ''): ?>

            <p class="result-information">

                <?php echo count($donors); ?>

                Available, eligible donor(s) found.
                Contact details are shared only once a request is matched.

            </p>

        <?php endif; ?>


        <!-- DONOR RESULTS -->
        <section class="donor-results">

            <?php if (!empty($donors)): ?>

                <?php foreach ($donors as $donor): ?>

                    <article class="donor-card">

                        <!-- AVAILABILITY -->
                        <div class="availability">
                            AVAILABLE
                        </div>


                        <!-- CITY -->
                        <div class="donor-location">
                            <?php echo htmlspecialchars($donor['city']); ?>
                        </div>


                        <!-- LAST DONATION -->
                        <div class="donor-date">

                            Last donation:

                            <?php

                            if (!empty($donor['last_donation_date'])) {

                                echo htmlspecialchars(
                                    $donor['last_donation_date']
                                );

                            } else {

                                echo 'Not available';

                            }

                            ?>

                        </div>


                        <!-- BLOOD GROUP -->
                        <div class="donor-blood">
                            <?php echo htmlspecialchars($donor['blood_group']); ?>
                        </div>


                        <!-- REQUEST BUTTON -->
                        <button
                            type="button"
                            class="request-button"
                        >
                            Request This Type
                        </button>

                    </article>

                <?php endforeach; ?>


            <?php elseif ($blood_group !== '' || $city !== ''): ?>

                <!-- NO RESULTS -->
                <p class="no-results">
                    No available donors found.
                </p>


            <?php endif; ?>

        </section>

    </section>

</main>