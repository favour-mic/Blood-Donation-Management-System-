<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Submit a Blood Request - BDMS</title>

    <!-- SEPARATE CSS FILE -->
    <link rel="stylesheet" href="submit-request.css">

</head>


<body>

    <main class="submit-page">

        <section class="submit-content">

            <!-- PAGE TITLE -->

            <h1 class="submit-title">
                Submit a Blood Request
            </h1>


            <!-- RECIPIENT PAGE NAVIGATION -->

            <nav class="request-navigation">

                <a href="search-donors.php"
                   class="request-nav-button">
                    Find Donors
                </a>

                <a href="submit-request.php"
                   class="request-nav-button active">
                    Submit Request
                </a>

                <a href="my-request.php"
                   class="request-nav-button">
                    My Requests
                </a>

            </nav>


            <!-- REQUEST FORM -->

            <section class="request-form-card">

                <form action="#" method="post">


                    <!-- BLOOD GROUP -->

                    <div class="form-group">

                        <label for="blood-group">
                            Blood Group Needed
                        </label>


                        <div class="blood-input-row">

                            <select
                                id="blood-group"
                                name="blood_group"
                                required>

                                <option value="">
                                    O+
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


                            <input
                                type="number"
                                id="units"
                                name="units"
                                min="1"
                                placeholder="2"
                                required>

                        </div>

                    </div>


                    <!-- URGENCY -->

                    <div class="form-group">

                        <label for="urgency">
                            Urgency
                        </label>

                        <select
                            id="urgency"
                            name="urgency"
                            required>

                            <option value="">
                                High
                            </option>

                            <option value="High">
                                High
                            </option>

                            <option value="Medium">
                                Medium
                            </option>

                            <option value="Low">
                                Low
                            </option>

                        </select>

                    </div>


                    <!-- HOSPITAL -->

                    <div class="form-group">

                        <label for="hospital">
                            Hospital
                        </label>

                        <select
                            id="hospital"
                            name="hospital"
                            required>

                            <option value="">
                                Buea General Hospital - Buea
                            </option>

                            <option value="Buea General Hospital">
                                Buea General Hospital - Buea
                            </option>

                            <option value="Douala Central Clinic">
                                Douala Central Clinic - Douala
                            </option>

                        </select>

                    </div>


                    <!-- HOSPITAL NAME -->

                    <div class="form-group">

                        <label for="hospital-name">
                            Hospital Name (as it should appear)
                        </label>

                        <input
                            type="text"
                            id="hospital-name"
                            name="hospital_name"
                            placeholder="Buea General Hospital"
                            required>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-request-button">

                        Submit Request

                    </button>


                </form>

            </section>

        </section>

    </main>

</body>

</html>