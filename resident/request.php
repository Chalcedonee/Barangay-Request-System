<?php

session_start();

require_once "../config/database.php";

// ========================================
// AUTHENTICATION
// ========================================

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION["role"] !== "resident") {
    header("Location: ../login.php");
    exit;
}


// ========================================
// GET RESIDENT
// ========================================

$user_id = $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT
        resident_id,
        first_name,
        middle_name,
        last_name,
        suffix
    FROM residents
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$resident = $stmt->fetch();

if (!$resident) {
    die("Resident information could not be found.");
}


// ========================================
// GET CERTIFICATIONS
// ========================================

$stmt = $pdo->query("
    SELECT
        certification_id,
        certification_name,
        description,
        fee,
        processing_days
    FROM certifications
    WHERE status = 'active'
    ORDER BY certification_name ASC
");

$certifications = $stmt->fetchAll();


// ========================================
// FORM PROCESSING
// ========================================

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $certification_id = $_POST["certification_id"] ?? "";
    $purpose = trim($_POST["purpose"] ?? "");
    $appointment_date = $_POST["appointment_date"] ?? "";
    $appointment_time = $_POST["appointment_time"] ?? "";


    // ========================================
    // VALIDATION
    // ========================================

    if (empty($certification_id)) {

        $error = "Please select a certification.";

    } elseif (empty($purpose)) {

        $error = "Please enter the purpose of your request.";

    } elseif (empty($appointment_date)) {

        $error = "Please select a preferred date.";

    } elseif (empty($appointment_time)) {

        $error = "Please select a preferred time.";

    } else {

        // Check that the selected certification actually exists
        $stmt = $pdo->prepare("
            SELECT certification_id
            FROM certifications
            WHERE certification_id = ?
            AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([$certification_id]);

        if (!$stmt->fetch()) {

            $error = "The selected certification is not available.";

        } else {

            // ========================================
            // DATE VALIDATION
            // ========================================

            $selected_date = strtotime($appointment_date);

            $today = strtotime(date("Y-m-d"));

            if ($selected_date < $today) {

                $error = "Please select today or a future date.";

            } else {

                try {

                    // ========================================
                    // START TRANSACTION
                    // ========================================

                    $pdo->beginTransaction();


                    // ========================================
                    // GENERATE REQUEST REFERENCE
                    // ========================================

                    $year = date("Y");

                    $stmt = $pdo->query("
                        SELECT COUNT(*) + 1
                        FROM requests
                        WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
                    ");

                    $request_number = $stmt->fetchColumn();

                    $request_reference = "BR-"
                        . $year
                        . "-"
                        . str_pad(
                            $request_number,
                            5,
                            "0",
                            STR_PAD_LEFT
                        );


                    // ========================================
                    // CURRENT DATE AND TIME
                    // ========================================

                    $date_requested = date("Y-m-d");
                    $time_requested = date("H:i:s");


                    // ========================================
                    // INSERT REQUEST
                    // ========================================

                    $stmt = $pdo->prepare("
                        INSERT INTO requests
                        (
                            request_reference,
                            resident_id,
                            certification_id,
                            purpose,
                            request_status,
                            date_requested,
                            time_requested
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            'Pending',
                            ?,
                            ?
                        )
                    ");

                    $stmt->execute([
                        $request_reference,
                        $resident["resident_id"],
                        $certification_id,
                        $purpose,
                        $date_requested,
                        $time_requested
                    ]);


                    // Get the newly created request ID
                    $request_id = $pdo->lastInsertId();


                    // ========================================
                    // CREATE APPOINTMENT
                    // ========================================

                    $stmt = $pdo->prepare("
                        INSERT INTO appointments
                        (
                            request_id,
                            appointment_date,
                            appointment_time,
                            status
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            'Scheduled'
                        )
                    ");

                    $stmt->execute([
                        $request_id,
                        $appointment_date,
                        $appointment_time
                    ]);


                    // ========================================
                    // COMMIT
                    // ========================================

                    $pdo->commit();


                    // Redirect to dashboard
                    header("Location: dashboard.php?request=success");
                    exit;


                } catch (PDOException $e) {

                    // Rollback if something goes wrong
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error = "Unable to submit your request. Please try again.";

                }

            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Request Certification - Barangay Request System
    </title>

    <!-- Tailwind CSS -->

    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="min-h-screen bg-gray-50 text-gray-900">


<!-- ========================================
     NAVIGATION
======================================== -->

<header class="border-b border-gray-200 bg-white">

    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <div class="flex h-16 items-center justify-between">

            <a
                href="dashboard.php"
                class="text-lg font-semibold tracking-tight"
            >
                Barangay Request System
            </a>


            <nav class="flex items-center gap-8">

                <a
                    href="dashboard.php"
                    class="text-sm font-medium text-gray-500 transition hover:text-gray-900"
                >
                    Dashboard
                </a>

                <a
                    href="requests.php"
                    class="text-sm font-medium text-gray-500 transition hover:text-gray-900"
                >
                    My Requests
                </a>

                <a
                    href="profile.php"
                    class="text-sm font-medium text-gray-500 transition hover:text-gray-900"
                >
                    Profile
                </a>

                <a
                    href="../logout.php"
                    class="text-sm font-medium text-red-600 transition hover:text-red-700"
                >
                    Logout
                </a>

            </nav>

        </div>

    </div>

</header>



<!-- ========================================
     MAIN
======================================== -->

<main>

    <div class="mx-auto max-w-3xl px-6 py-10 lg:px-8">


        <!-- PAGE HEADER -->

        <div class="mb-8">

            <a
                href="dashboard.php"
                class="text-sm text-gray-500 hover:text-gray-900"
            >
                ← Back to Dashboard
            </a>

            <h1 class="mt-5 text-3xl font-semibold tracking-tight">
                Request a Certification
            </h1>

            <p class="mt-2 text-gray-500">
                Fill out the form below to submit your barangay certification request.
            </p>

        </div>



        <!-- ERROR MESSAGE -->

        <?php if ($error): ?>

            <div
                class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>



        <!-- FORM -->

        <form
            method="POST"
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8"
        >


            <!-- ========================================
                 CERTIFICATION
            ======================================== -->

            <div>

                <label
                    for="certification_id"
                    class="block text-sm font-medium text-gray-900"
                >
                    Certification
                </label>

                <select
                    id="certification_id"
                    name="certification_id"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                >

                    <option value="">
                        Select a certification
                    </option>

                    <?php foreach ($certifications as $certification): ?>

                        <option
                            value="<?= $certification["certification_id"] ?>"
                            <?= (
                                ($_POST["certification_id"] ?? "")
                                == $certification["certification_id"]
                            ) ? "selected" : "" ?>
                        >

                            <?= htmlspecialchars(
                                $certification["certification_name"]
                            ) ?>

                            -
                            ₱<?= number_format(
                                $certification["fee"],
                                2
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <!-- ========================================
                 PURPOSE
            ======================================== -->

            <div class="mt-6">

                <label
                    for="purpose"
                    class="block text-sm font-medium text-gray-900"
                >
                    Purpose
                </label>

                <textarea
                    id="purpose"
                    name="purpose"
                    rows="3"
                    required
                    placeholder="Example: Employment requirement"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                ><?= htmlspecialchars(
                    $_POST["purpose"] ?? ""
                ) ?></textarea>

            </div>



            <!-- ========================================
                 APPOINTMENT DATE
            ======================================== -->

            <div class="mt-6">

                <label
                    for="appointment_date"
                    class="block text-sm font-medium text-gray-900"
                >
                    Preferred Date
                </label>

                <input
                    type="date"
                    id="appointment_date"
                    name="appointment_date"
                    min="<?= date("Y-m-d") ?>"
                    value="<?= htmlspecialchars(
                        $_POST["appointment_date"] ?? ""
                    ) ?>"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                >

                <p class="mt-2 text-xs text-gray-500">
                    Select today or a future date.
                </p>

            </div>



            <!-- ========================================
                 APPOINTMENT TIME
            ======================================== -->

            <div class="mt-6">

                <label
                    for="appointment_time"
                    class="block text-sm font-medium text-gray-900"
                >
                    Preferred Time
                </label>

                <input
                    type="time"
                    id="appointment_time"
                    name="appointment_time"
                    value="<?= htmlspecialchars(
                        $_POST["appointment_time"] ?? ""
                    ) ?>"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                >

                <p class="mt-2 text-xs text-gray-500">
                    Your preferred time will be reviewed by barangay staff.
                </p>

            </div>



            <!-- ========================================
                 INFORMATION
            ======================================== -->

            <div class="mt-8 rounded-lg bg-gray-50 p-4">

                <p class="text-sm font-medium text-gray-900">
                    Important
                </p>

                <p class="mt-1 text-sm leading-6 text-gray-500">
                    Your request will initially be marked as
                    <strong class="font-medium text-gray-700">
                        Pending
                    </strong>.
                    Barangay staff will review your request and confirm your appointment.
                </p>

            </div>



            <!-- ========================================
                 BUTTONS
            ======================================== -->

            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="dashboard.php"
                    class="inline-flex justify-center rounded-lg border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex justify-center rounded-lg bg-gray-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-gray-700"
                >
                    Submit Request
                </button>

            </div>

        </form>

    </div>

</main>


</body>

</html>