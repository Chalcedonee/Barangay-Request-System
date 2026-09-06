<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Check Resident Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "resident") {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Validate Request ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {
    header("Location: requests.php");
    exit;
}

$request_id = (int) $_GET["id"];
$user_id = $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Get Resident ID
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT resident_id
        FROM residents
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$user_id]);

    $resident = $stmt->fetch();

    if (!$resident) {
        die("Resident profile not found.");
    }

    $resident_id = $resident["resident_id"];

} catch (PDOException $e) {

    die("Something went wrong. Please try again.");

}

/*
|--------------------------------------------------------------------------
| Get Request Details
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            r.request_id,
            r.request_reference,
            r.purpose,
            r.request_status,
            r.date_requested,
            r.time_requested,
            r.processed_at,
            r.released_at,
            r.remarks,

            c.certification_name,
            c.description,
            c.processing_days,
            c.fee,

            a.appointment_date,
            a.appointment_time,
            a.status AS appointment_status

        FROM requests r

        INNER JOIN certifications c
            ON r.certification_id = c.certification_id

        LEFT JOIN appointments a
            ON r.request_id = a.request_id

        WHERE r.request_id = ?
        AND r.resident_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $request_id,
        $resident_id
    ]);

    $request = $stmt->fetch();

    /*
    |--------------------------------------------------------------------------
    | Make Sure Request Belongs To Resident
    |--------------------------------------------------------------------------
    */

    if (!$request) {
        header("Location: requests.php");
        exit;
    }

} catch (PDOException $e) {

    die("Unable to load request details.");

}

/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

function getStatusClass($status)
{
    switch ($status) {

        case "Pending":
            return "bg-yellow-100 text-yellow-700";

        case "Approved":
            return "bg-blue-100 text-blue-700";

        case "Scheduled":
            return "bg-indigo-100 text-indigo-700";

        case "Ready for Pickup":
            return "bg-green-100 text-green-700";

        case "Released":
            return "bg-gray-100 text-gray-700";

        case "Rejected":
            return "bg-red-100 text-red-700";

        case "Cancelled":
            return "bg-gray-100 text-gray-600";

        default:
            return "bg-gray-100 text-gray-600";
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
        Request Details - Barangay Request System
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="min-h-screen bg-gray-50 text-gray-900">


    <!-- Navigation -->

    <nav class="border-b border-gray-200 bg-white">

        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">

            <div>

                <h1 class="text-lg font-semibold">
                    Barangay Request System
                </h1>

                <p class="text-xs text-gray-500">
                    Resident Portal
                </p>

            </div>


            <div class="flex items-center gap-6 text-sm">

                <a
                    href="dashboard.php"
                    class="text-gray-600 transition hover:text-gray-900"
                >
                    Dashboard
                </a>

                <a
                    href="requests.php"
                    class="font-medium text-gray-900"
                >
                    My Requests
                </a>

                <a
                    href="profile.php"
                    class="text-gray-600 transition hover:text-gray-900"
                >
                    Profile
                </a>

                <a
                    href="../logout.php"
                    class="text-red-600 transition hover:text-red-700"
                >
                    Logout
                </a>

            </div>

        </div>

    </nav>


    <!-- Main Content -->

    <main class="mx-auto max-w-4xl px-6 py-10">


        <!-- Back -->

        <div class="mb-6">

            <a
                href="requests.php"
                class="text-sm text-gray-500 transition hover:text-gray-900"
            >
                ← Back to My Requests
            </a>

        </div>


        <!-- Header -->

        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            <div>

                <p class="text-sm text-gray-500">
                    Request Reference
                </p>

                <h2 class="mt-1 text-2xl font-semibold tracking-tight">
                    <?= htmlspecialchars($request["request_reference"]) ?>
                </h2>

            </div>


            <span
                class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-medium <?= getStatusClass($request["request_status"]) ?>"
            >
                <?= htmlspecialchars($request["request_status"]) ?>
            </span>

        </div>


        <!-- Request Information -->

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


            <!-- Certification -->

            <div class="border-b border-gray-200 px-6 py-6">

                <p class="text-sm text-gray-500">
                    Certification
                </p>

                <h3 class="mt-1 text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars($request["certification_name"]) ?>
                </h3>

                <?php if (!empty($request["description"])): ?>

                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        <?= htmlspecialchars($request["description"]) ?>
                    </p>

                <?php endif; ?>

            </div>


            <!-- Request Information -->

            <div class="grid gap-6 border-b border-gray-200 px-6 py-6 sm:grid-cols-2">


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Date Requested
                    </p>

                    <p class="mt-2 text-sm text-gray-900">
                        <?= date(
                            "F d, Y",
                            strtotime($request["date_requested"])
                        ) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Time Requested
                    </p>

                    <p class="mt-2 text-sm text-gray-900">
                        <?= date(
                            "h:i A",
                            strtotime($request["time_requested"])
                        ) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Processing Time
                    </p>

                    <p class="mt-2 text-sm text-gray-900">

                        <?= htmlspecialchars(
                            $request["processing_days"]
                        ) ?>

                        <?= $request["processing_days"] == 1
                            ? "day"
                            : "days"
                        ?>

                    </p>

                </div>


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Certification Fee
                    </p>

                    <p class="mt-2 text-sm font-medium text-gray-900">
                        ₱<?= number_format(
                            $request["fee"],
                            2
                        ) ?>
                    </p>

                </div>

            </div>


            <!-- Purpose -->

            <div class="border-b border-gray-200 px-6 py-6">

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Purpose
                </p>

                <p class="mt-2 text-sm leading-6 text-gray-900">
                    <?= htmlspecialchars($request["purpose"]) ?>
                </p>

            </div>


            <!-- Appointment -->

            <div class="border-b border-gray-200 px-6 py-6">

                <h3 class="text-sm font-semibold text-gray-900">
                    Appointment
                </h3>


                <?php if ($request["appointment_date"]): ?>

                    <div class="mt-4 grid gap-6 sm:grid-cols-3">


                        <div>

                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Date
                            </p>

                            <p class="mt-2 text-sm text-gray-900">
                                <?= date(
                                    "F d, Y",
                                    strtotime(
                                        $request["appointment_date"]
                                    )
                                ) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Time
                            </p>

                            <p class="mt-2 text-sm text-gray-900">
                                <?= date(
                                    "h:i A",
                                    strtotime(
                                        $request["appointment_time"]
                                    )
                                ) ?>
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Appointment Status
                            </p>

                            <p class="mt-2 text-sm text-gray-900">
                                <?= htmlspecialchars(
                                    $request["appointment_status"]
                                ) ?>
                            </p>

                        </div>

                    </div>

                <?php else: ?>

                    <p class="mt-3 text-sm text-gray-500">
                        No appointment has been scheduled yet.
                    </p>

                <?php endif; ?>

            </div>


            <!-- Processing Information -->

            <?php if ($request["processed_at"]): ?>

                <div class="border-b border-gray-200 px-6 py-6">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Processed On
                    </p>

                    <p class="mt-2 text-sm text-gray-900">

                        <?= date(
                            "F d, Y h:i A",
                            strtotime($request["processed_at"])
                        ) ?>

                    </p>

                </div>

            <?php endif; ?>


            <!-- Released Information -->

            <?php if ($request["released_at"]): ?>

                <div class="border-b border-gray-200 px-6 py-6">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Released On
                    </p>

                    <p class="mt-2 text-sm text-gray-900">

                        <?= date(
                            "F d, Y h:i A",
                            strtotime($request["released_at"])
                        ) ?>

                    </p>

                </div>

            <?php endif; ?>


            <!-- Remarks -->

            <div class="px-6 py-6">

                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                    Remarks
                </p>


                <?php if (!empty($request["remarks"])): ?>

                    <div class="mt-3 rounded-lg bg-gray-50 p-4">

                        <p class="text-sm leading-6 text-gray-700">
                            <?= nl2br(
                                htmlspecialchars(
                                    $request["remarks"]
                                )
                            ) ?>
                        </p>

                    </div>

                <?php else: ?>

                    <p class="mt-2 text-sm text-gray-400">
                        No remarks yet.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- Bottom Action -->

        <div class="mt-6 flex justify-between">

            <a
                href="requests.php"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                Back
            </a>


            <?php if (
                $request["request_status"] === "Pending"
            ): ?>

                <span class="self-center text-xs text-gray-400">
                    Waiting for staff review
                </span>

            <?php elseif (
                $request["request_status"] === "Ready for Pickup"
            ): ?>

                <span class="self-center text-xs font-medium text-green-600">
                    Your certification is ready for pickup.
                </span>

            <?php elseif (
                $request["request_status"] === "Released"
            ): ?>

                <span class="self-center text-xs text-gray-500">
                    Certification released.
                </span>

            <?php endif; ?>

        </div>


    </main>

</body>

</html>