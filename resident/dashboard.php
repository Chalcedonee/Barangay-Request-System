<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Make sure the account belongs to a resident
if ($_SESSION["role"] !== "resident") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Get resident information
$stmt = $pdo->prepare("
    SELECT
        resident_id,
        first_name,
        middle_name,
        last_name,
        suffix,
        address,
        contact_number
    FROM residents
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$resident = $stmt->fetch();

if (!$resident) {
    die("Resident information could not be found.");
}

// Resident full name
$full_name = $resident["first_name"];

if (!empty($resident["middle_name"])) {
    $full_name .= " " . $resident["middle_name"];
}

$full_name .= " " . $resident["last_name"];

if (!empty($resident["suffix"])) {
    $full_name .= " " . $resident["suffix"];
}


// ========================================
// REQUEST STATISTICS
// ========================================

// Total requests
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM requests
    WHERE resident_id = ?
");

$stmt->execute([$resident["resident_id"]]);

$total_requests = $stmt->fetchColumn();


// Pending requests
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM requests
    WHERE resident_id = ?
    AND request_status = 'Pending'
");

$stmt->execute([$resident["resident_id"]]);

$pending_requests = $stmt->fetchColumn();


// Scheduled requests
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM requests
    WHERE resident_id = ?
    AND request_status = 'Scheduled'
");

$stmt->execute([$resident["resident_id"]]);

$scheduled_requests = $stmt->fetchColumn();


// Released requests
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM requests
    WHERE resident_id = ?
    AND request_status = 'Released'
");

$stmt->execute([$resident["resident_id"]]);

$released_requests = $stmt->fetchColumn();


// ========================================
// RECENT REQUESTS
// ========================================

$stmt = $pdo->prepare("
    SELECT
        r.request_reference,
        c.certification_name,
        r.purpose,
        r.request_status,
        r.date_requested
    FROM requests r

    INNER JOIN certifications c
        ON r.certification_id = c.certification_id

    WHERE r.resident_id = ?

    ORDER BY r.created_at DESC

    LIMIT 5
");

$stmt->execute([$resident["resident_id"]]);

$recent_requests = $stmt->fetchAll();

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
        Resident Dashboard - Barangay Request System
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

            <!-- Logo / System Name -->

            <a
                href="dashboard.php"
                class="text-lg font-semibold tracking-tight"
            >
                Barangay Request System
            </a>


            <!-- Navigation -->

            <nav class="hidden items-center gap-8 md:flex">

                <a
                    href="dashboard.php"
                    class="text-sm font-medium text-gray-900"
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
     MAIN CONTENT
======================================== -->

<main>

    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">


        <!-- Welcome -->

        <div class="mb-10">

            <p class="text-sm font-medium text-gray-500">
                Resident Dashboard
            </p>

            <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                Welcome, <?= htmlspecialchars($resident["first_name"]) ?>
            </h1>

            <p class="mt-2 text-gray-500">
                Manage your barangay certification requests.
            </p>

        </div>



        <!-- ========================================
             REQUEST BUTTON
        ======================================== -->

        <div class="mb-8">

            <a
                href="request.php"
                class="inline-flex items-center rounded-lg bg-gray-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-gray-700"
            >

                Request Certification

                <span class="ml-2">
                    →
                </span>

            </a>

        </div>



        <!-- ========================================
             STATISTICS
        ======================================== -->

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">


            <!-- Total -->

            <div class="rounded-xl border border-gray-200 bg-white p-6">

                <p class="text-sm text-gray-500">
                    Total Requests
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    <?= $total_requests ?>
                </p>

            </div>


            <!-- Pending -->

            <div class="rounded-xl border border-gray-200 bg-white p-6">

                <p class="text-sm text-gray-500">
                    Pending
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    <?= $pending_requests ?>
                </p>

            </div>


            <!-- Scheduled -->

            <div class="rounded-xl border border-gray-200 bg-white p-6">

                <p class="text-sm text-gray-500">
                    Scheduled
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    <?= $scheduled_requests ?>
                </p>

            </div>


            <!-- Released -->

            <div class="rounded-xl border border-gray-200 bg-white p-6">

                <p class="text-sm text-gray-500">
                    Released
                </p>

                <p class="mt-2 text-3xl font-semibold">
                    <?= $released_requests ?>
                </p>

            </div>

        </div>



        <!-- ========================================
             RECENT REQUESTS
        ======================================== -->

        <div class="mt-10">

            <div class="mb-4 flex items-center justify-between">

                <div>

                    <h2 class="text-xl font-semibold">
                        Recent Requests
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Your latest certification requests.
                    </p>

                </div>


                <a
                    href="requests.php"
                    class="text-sm font-medium text-gray-700 hover:text-gray-900"
                >
                    View all →
                </a>

            </div>



            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">


                <?php if (empty($recent_requests)): ?>

                    <!-- No Requests -->

                    <div class="px-6 py-12 text-center">

                        <p class="text-gray-500">
                            You haven't submitted any certification requests yet.
                        </p>

                        <a
                            href="request.php"
                            class="mt-4 inline-block text-sm font-medium text-gray-900 underline"
                        >
                            Submit your first request
                        </a>

                    </div>


                <?php else: ?>


                    <!-- Request List -->

                    <div class="divide-y divide-gray-200">

                        <?php foreach ($recent_requests as $request): ?>

                            <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">


                                <div>

                                    <p class="font-medium">
                                        <?= htmlspecialchars($request["certification_name"]) ?>
                                    </p>

                                    <p class="mt-1 text-sm text-gray-500">

                                        <?= htmlspecialchars($request["request_reference"]) ?>

                                        ·

                                        <?= date(
                                            "F j, Y",
                                            strtotime($request["date_requested"])
                                        ) ?>

                                    </p>

                                    <p class="mt-1 text-sm text-gray-500">

                                        Purpose:
                                        <?= htmlspecialchars($request["purpose"]) ?>

                                    </p>

                                </div>



                                <!-- Status -->

                                <?php

                                $status = $request["request_status"];

                                $status_class = match ($status) {

                                    "Pending" =>
                                        "bg-yellow-50 text-yellow-700",

                                    "Approved" =>
                                        "bg-blue-50 text-blue-700",

                                    "Scheduled" =>
                                        "bg-purple-50 text-purple-700",

                                    "Ready for Pickup" =>
                                        "bg-green-50 text-green-700",

                                    "Released" =>
                                        "bg-green-50 text-green-700",

                                    "Rejected" =>
                                        "bg-red-50 text-red-700",

                                    "Cancelled" =>
                                        "bg-gray-100 text-gray-600",

                                    default =>
                                        "bg-gray-100 text-gray-600"

                                };

                                ?>

                                <span
                                    class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-medium <?= $status_class ?>"
                                >
                                    <?= htmlspecialchars($status) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


            </div>

        </div>


    </div>

</main>


</body>

</html>