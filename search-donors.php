<?php

require_once "db.php";

/*
|--------------------------------------------------------------------------
| GET SEARCH VALUES
|--------------------------------------------------------------------------
*/

$blood_group = trim($_GET["blood_group"] ?? "");
$city = trim($_GET["city"] ?? "");

$donors = [];


/*
|--------------------------------------------------------------------------
| SEARCH DONORS
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            donors.id,
            users.name,
            users.phone,
            donors.blood_group,
            donors.age,
            donors.weight,
            donors.city,
            donors.last_donation_date,
            donors.is_available
        FROM donors
        INNER JOIN users
            ON donors.user_id = users.id
        WHERE donors.is_available = TRUE";


$params = [];
$types = "";


/*
|--------------------------------------------------------------------------
| BLOOD GROUP FILTER
|--------------------------------------------------------------------------
*/

if ($blood_group !== "") {

    $sql .= " AND donors.blood_group = ?";

    $types .= "s";

    $params[] = $blood_group;
}


/*
|--------------------------------------------------------------------------
| CITY FILTER
|--------------------------------------------------------------------------
*/

if ($city !== "") {

    $sql .= " AND donors.city LIKE ?";

    $types .= "s";

    $params[] = "%" . $city . "%";
}


/*
|--------------------------------------------------------------------------
| ORDER RESULTS
|--------------------------------------------------------------------------
*/

$sql .= " ORDER BY donors.city ASC";


$stmt = $conn->prepare($sql);


if (!$stmt) {
    die("Database error: " . $conn->error);
}


/*
|--------------------------------------------------------------------------
| ADD SEARCH PARAMETERS
|--------------------------------------------------------------------------
*/

if (!empty($params)) {

    $stmt->bind_param($types, ...$params);
}


/*
|--------------------------------------------------------------------------
| EXECUTE SEARCH
|--------------------------------------------------------------------------
*/

$stmt->execute();

$result = $stmt->get_result();


while ($row = $result->fetch_assoc()) {

    $donors[] = $row;

}


$stmt->close();

$conn->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Search Donors - BDMS</title>

    <!-- YOUR EXISTING CSS -->
    <link rel="stylesheet" href="recipient.css">

</head>


<body>


<main class="recipient-page">

    <section class="page-content">


        <div class="page-label">
            RECIPIENT
        </div>


        <h1 class="page-title">
            Search Donors
        </h1>


        <!-- NAVIGATION -->

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


        <!-- SEARCH FORM -->

        <form action="search-donors.php"
              method="GET"
              class="search-box">


            <!-- BLOOD GROUP -->

            <div class="search-field">

                <label for="blood-group">
                    Blood Group
                </label>

                <select
                    id="blood-group"
                    name="blood_group">

                    <option value="">
                        Any
                    </option>

                    <option value="A+"
                        <?php if ($blood_group === "A+") echo "selected"; ?>>
                        A+
                    </option>

                    <option value="A-"
                        <?php if ($blood_group === "A-") echo "selected"; ?>>
                        A-
                    </option>

                    <option value="B+"
                        <?php if ($blood_group === "B+") echo "selected"; ?>>
                        B+
                    </option>

                    <option value="B-"
                        <?php if ($blood_group === "B-") echo "selected"; ?>>
                        B-
                    </option>

                    <option value="AB+"
                        <?php if ($blood_group === "AB+") echo "selected"; ?>>
                        AB+
                    </option>

                    <option value="AB-"
                        <?php if ($blood_group === "AB-") echo "selected"; ?>>
                        AB-
                    </option>

                    <option value="O+"
                        <?php if ($blood_group === "O+") echo "selected"; ?>>
                        O+
                    </option>

                    <option value="O-"
                        <?php if ($blood_group === "O-") echo "selected"; ?>>
                        O-
                    </option>

                </select>

            </div>


            <!-- CITY -->

            <div class="search-field">

                <label for="city">
                    City
                </label>

                <input
                    type="text"
                    id="city"
                    name="city"
                    value="<?php echo htmlspecialchars($city); ?>"
                    placeholder="e.g. Buea">

            </div>


            <!-- SEARCH BUTTON -->

            <button
                type="submit"
                class="search-button">

                Search

            </button>


        </form>


        <!-- RESULT COUNT -->

        <p class="result-information">

            <?php echo count($donors); ?>

            Available, eligible donor(s) found.
            Contact details are shared only once a request is matched.

        </p>


        <!-- DONOR RESULTS -->

        <section class="donor-results">


            <?php if (count($donors) > 0): ?>


                <?php foreach ($donors as $donor): ?>


                    <article class="donor-card">


                        <div class="availability">

                            <?php
                            if ($donor["is_available"]) {
                                echo "AVAILABLE";
                            }
                            ?>

                        </div>


                        <div class="donor-location">

                            <?php
                            echo htmlspecialchars(
                                $donor["city"]
                            );
                            ?>

                        </div>


                        <div class="donor-date">

                            Last donation:

                            <?php

                            if (
                                !empty(
                                    $donor["last_donation_date"]
                                )
                            ) {

                                echo htmlspecialchars(
                                    date(
                                        "M d, Y",
                                        strtotime(
                                            $donor["last_donation_date"]
                                        )
                                    )
                                );

                            } else {

                                echo "Not available";

                            }

                            ?>

                        </div>


                        <div class="donor-blood">

                            <?php
                            echo htmlspecialchars(
                                $donor["blood_group"]
                            );
                            ?>

                        </div>


                        <button
                            type="button"
                            class="request-button">

                            Request This Type

                        </button>


                    </article>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="no-results">

                    No available donors found matching your search.

                </div>


            <?php endif; ?>


        </section>


    </section>

</main>


</body>

</html>