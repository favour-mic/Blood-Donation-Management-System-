<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Search Donors - BDMS</title>

    <!-- CSS FILE -->
    <link rel="stylesheet" href="recipient.css">
</head>

<body>

    <!-- SEARCH DONORS PAGE -->
    <main class="recipient-page">

        <section class="page-content">

            <div class="page-label">
                RECIPIENT
            </div>

            <h1 class="page-title">
                Search Donors
            </h1>

            <!-- PAGE NAVIGATION -->
            <div class="page-navigation">

                <a href="search-donors.php"
                   class="page-nav-button active">
                    Find Donors
                </a>

                <a href="submit-request.php"
                   class="page-nav-button">
                    Submit Request
                </a>

                <a href="my-request.php"
                   class="page-nav-button">
                    My Requests
                </a>

            </div>


            <!-- SEARCH BOX -->
            <section class="search-box">

                <div class="search-field">

                    <label for="blood-group">
                        Blood Group
                    </label>

                    <select id="blood-group" name="blood_group">

                        <option value="">
                            Any
                        </option>

                        <option value="A+">
                            A+
                        </option>

                        <option value="A-">
                            A-
                        </option>

                        <option value="B+">
                            B+
                        </option>

                        <option value="B-">
                            B-
                        </option>

                        <option value="AB+">
                            AB+
                        </option>

                        <option value="AB-">
                            AB-
                        </option>

                        <option value="O+">
                            O+
                        </option>

                        <option value="O-">
                            O-
                        </option>

                    </select>

                </div>


                <div class="search-field">

                    <label for="city">
                        City
                    </label>

                    <input
                        type="text"
                        id="city"
                        name="city"
                        placeholder="e.g. Buea"
                    >

                </div>


                <button type="button"
                        class="search-button">
                    Search
                </button>

            </section>


            <!-- SEARCH RESULT INFORMATION -->
            <p class="result-information">
                3 Available , eligible donor(s) found. Contact details are shared only once a request is matched.
            </p>


            <!-- DONOR RESULTS -->
            <section class="donor-results">


                <!-- DONOR 1 -->
                <article class="donor-card">

                    <div class="availability">
                        AVAILABLE
                    </div>

                    <div class="donor-location">
                        Buea
                    </div>

                    <div class="donor-date">
                        Last donation: Jul 20, 2026
                    </div>

                    <div class="donor-blood">
                        O+
                    </div>

                    <button class="request-button"
                            type="button">
                        Request This Type
                    </button>

                </article>


                <!-- DONOR 2 -->
                <article class="donor-card">

                    <div class="availability">
                        AVAILABLE
                    </div>

                    <div class="donor-location">
                        Douala
                    </div>

                    <div class="donor-date">
                        Last donation: Jun 30, 2026
                    </div>

                    <div class="donor-blood">
                        O+
                    </div>

                    <button class="request-button"
                            type="button">
                        Request This Type
                    </button>

                </article>


                <!-- DONOR 3 -->
                <article class="donor-card">

                    <div class="availability">
                        AVAILABLE
                    </div>

                    <div class="donor-location">
                        Buea
                    </div>

                    <div class="donor-date">
                        Last donation: Aug 1, 2026
                    </div>

                    <div class="donor-blood">
                        A-
                    </div>

                    <button class="request-button"
                            type="button">
                        Request This Type
                    </button>

                </article>

            </section>

        </section>

    </main>

</body>
</html>