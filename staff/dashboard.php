<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Check Staff Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "staff") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_email = $_SESSION["email"];

/*
|--------------------------------------------------------------------------
| Request Statistics
|--------------------------------------------------------------------------
*/

try {

    // Total requests
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM requests
    ");

    $total_requests = $stmt->fetchColumn();


    // Pending requests
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM requests
        WHERE request_status = 'Pending'
    ");

    $pending_requests = $stmt->fetchColumn();


    // Scheduled requests
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM requests
        WHERE request_status = 'Scheduled'
    ");

    $scheduled_requests = $stmt->fetchColumn();


    // Ready for pickup
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM requests
        WHERE request_status = 'Ready for Pickup'
    ");

    $ready_requests = $stmt->fetchColumn();


    // Released requests
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM requests
        WHERE request_status = 'Released'
    ");

    $released_requests = $stmt->fetchColumn();


    // Rejected requests
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM requests
        WHERE request_status = 'Rejected'
    ");

    $rejected_requests = $stmt->fetchColumn();


} catch (PDOException $e) {

    die("Unable to load dashboard information.");

}


/*
|--------------------------------------------------------------------------
| Recent Requests
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            r.request_id,
            r.request_reference,
            r.request_status,
            r.date_requested,
            r.time_requested,

            CONCAT(
                res.first_name,
                ' ',
                res.last_name
            ) AS resident_name,

            c.certification_name

        FROM requests r

        INNER JOIN residents res
            ON r.resident_id = res.resident_id

        INNER JOIN certifications c
            ON r.certification_id = c.certification_id

        ORDER BY r.created_at DESC

        LIMIT 8
    ");

    $recent_requests = $stmt->fetchAll();

} catch (PDOException $e) {

    die("Unable to load recent requests.");

}


/*
|--------------------------------------------------------------------------
| Status Badge Helper
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
        Staff Dashboard - Barangay Request System
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="min-h-screen bg-gray-50 text-gray-900">


    <!-- Navigation -->

    <nav class="border-b border-gray-200 bg-white">

        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">


            <!-- System Name -->

            <div>

                <h1 class="text-lg font-semibold">
                    Barangay Request System
                </h1>

                <p class="text-xs text-gray-500">
                    Staff Portal
                </p>

            </div>


            <!-- Navigation -->

            <div class="flex items-center gap-6 text-sm">

                <a
                    href="dashboard.php"
                    class="font-medium text-gray-900"
                >
                    Dashboard
                </a>

                <a
                    href="requests.php"
                    class="text-gray-600 transition hover:text-gray-900"
                >
                    Requests
                </a>

                <a
                    href="appointments.php"
                    class="text-gray-600 transition hover:text-gray-900"
                >
                    Appointments
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


    <!-- Main -->

    <main class="mx-auto max-w-7xl px-6 py-10">


        <!-- Header -->

        <div class="mb-8">

            <p class="text-sm text-gray-500">
                Welcome back
            </p>

            <h2 class="mt-1 text-2xl font-semibold tracking-tight">
                Staff Dashboard
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Monitor and manage resident certification requests.
            </p>

        </div>


        <!-- Statistics -->

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">


            <!-- Total -->

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-gray-500">
                    Total Requests
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    <?= $total_requests ?>
                </p>

            </div>


            <!-- Pending -->

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-gray-500">
                    Pending
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    <?= $pending_requests ?>
                </p>

            </div>


            <!-- Scheduled -->

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-gray-500">
                    Scheduled
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    <?= $scheduled_requests ?>
                </p>

            </div>


            <!-- Ready -->

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-gray-500">
                    Ready
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    <?= $ready_requests ?>
                </p>

            </div>


            <!-- Released -->

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-gray-500">
                    Released
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    <?= $released_requests ?>
                </p>

            </div>


            <!-- Rejected -->

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-gray-500">
                    Rejected
                </p>

                <p class="mt-2 text-2xl font-semibold">
                    <?= $rejected_requests ?>
                </p>

            </div>

        </div>


        <!-- Recent Requests -->

        <div class="mt-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


            <!-- Section Header -->

            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5">

                <div>

                    <h3 class="font-semibold text-gray-900">
                        Recent Requests
                    </h3>

                    <p class="mt-1 text-xs text-gray-500">
                        Latest certification requests submitted by residents.
                    </p>

                </div>

                <a
                    href="requests.php"
                    class="text-sm font-medium text-gray-700 hover:text-gray-900"
                >
                    View All
                </a>

            </div>


            <?php if (empty($recent_requests)): ?>

                <!-- Empty -->

                <div class="px-6 py-16 text-center">

                    <p class="text-sm text-gray-500">
                        No certification requests have been submitted yet.
                    </p>

                </div>

            <?php else: ?>


                <!-- Desktop Table -->

                <div class="overflow-x-auto">

                    <table class="w-full text-left text-sm">

                        <thead class="border-b border-gray-200 bg-gray-50">

                            <tr>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Reference
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Resident
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Certification
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Date
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Status
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            <?php foreach ($recent_requests as $request): ?>

                                <tr class="transition hover:bg-gray-50">


                                    <!-- Reference -->

                                    <td class="px-6 py-5">

                                        <span class="font-medium text-gray-900">

                                            <?= htmlspecialchars(
                                                $request["request_reference"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Resident -->

                                    <td class="px-6 py-5">

                                        <?= htmlspecialchars(
                                            $request["resident_name"]
                                        ) ?>

                                    </td>


                                    <!-- Certification -->

                                    <td class="px-6 py-5">

                                        <?= htmlspecialchars(
                                            $request["certification_name"]
                                        ) ?>

                                    </td>


                                    <!-- Date -->

                                    <td class="px-6 py-5">

                                        <p class="text-gray-700">

                                            <?= date(
                                                "M d, Y",
                                                strtotime(
                                                    $request["date_requested"]
                                                )
                                            ) ?>

                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">

                                            <?= date(
                                                "h:i A",
                                                strtotime(
                                                    $request["time_requested"]
                                                )
                                            ) ?>

                                        </p>

                                    </td>


                                    <!-- Status -->

                                    <td class="px-6 py-5">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= getStatusClass($request["request_status"]) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $request["request_status"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Action -->

                                    <td class="px-6 py-5">

                                        <a
                                            href="request_details.php?id=<?= $request["request_id"] ?>"
                                            class="font-medium text-gray-700 hover:text-gray-900"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


        <!-- Quick Actions -->

        <div class="mt-8 grid gap-4 sm:grid-cols-2">


            <a
                href="requests.php"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-gray-300 hover:shadow"
            >

                <h3 class="font-semibold text-gray-900">
                    Manage Requests
                </h3>

                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Review resident certification requests and update their status.
                </p>

            </a>


            <a
                href="appointments.php"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-gray-300 hover:shadow"
            >

                <h3 class="font-semibold text-gray-900">
                    Manage Appointments
                </h3>

                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Review appointment schedules and manage resident pickup dates.
                </p>

            </a>


        </div>


    </main>

</body>

</html>