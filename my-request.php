<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Requests</title>

    <link rel="stylesheet" href="my-request.css">
</head>

<body>

    <main class="my-request-page">

        <!-- PAGE TITLE -->
        <section class="request-intro">

            <h1>My Requests</h1>

            <!-- RECIPIENT PAGE NAVIGATION -->
            <div class="page-navigation">

                <a href="search-donors.php" class="page-nav-button">
                    Find Donors
                </a>

                <a href="submit-request.php" class="page-nav-button">
                    Submit Request
                </a>

                <a href="my-request.php" class="page-nav-button active">
                    My Requests
                </a>

            </div>

        </section>


        <!-- REQUEST LIST -->
        <section class="requests-container">


            <!-- REQUEST 1 -->
            <article class="request-card">

                <div class="request-top">

                    <div class="request-information">

                        <h2>Q+ . 2 unit(s)</h2>

                        <p>
                            Buea General Hospital - Submitted aug 14, 2026
                        </p>

                    </div>

                    <span class="urgency high">
                        HIGH URGENCY
                    </span>

                </div>


                <!-- REQUEST PROGRESS -->
                <div class="request-progress">

                    <div class="progress-line">

                        <span class="progress-segment completed"></span>

                        <span class="progress-segment completed"></span>

                        <span class="progress-segment pending"></span>

                    </div>


                    <div class="progress-labels">

                        <span>Pending</span>

                        <span>Matched</span>

                        <span class="inactive-label">Fulfilled</span>

                    </div>

                </div>

            </article>



            <!-- REQUEST 2 -->
            <article class="request-card">

                <div class="request-top">

                    <div class="request-information">

                        <h2>A- . 1 unit(s)</h2>

                        <p>
                            Douala Central Clinic - Submitted aug 10, 2026
                        </p>

                    </div>

                    <span class="urgency medium">
                        MEDIUM URGENCY
                    </span>

                </div>


                <!-- REQUEST PROGRESS -->
                <div class="request-progress">

                    <div class="progress-line">

                        <span class="progress-segment completed"></span>

                        <span class="progress-segment completed"></span>

                        <span class="progress-segment completed"></span>

                    </div>


                    <div class="progress-labels">

                        <span>Pending</span>

                        <span>Matched</span>

                        <span>Fulfilled</span>

                    </div>

                </div>

            </article>


        </section>

    </main>


</body>
</html>