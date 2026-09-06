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


/*
|--------------------------------------------------------------------------
| Get Selected Status Filter
|--------------------------------------------------------------------------
*/

$status_filter = $_GET["status"] ?? "All";


$allowed_statuses = [
    "All",
    "Pending",
    "Approved",
    "Scheduled",
    "Ready for Pickup",
    "Released",
    "Rejected",
    "Cancelled"
];


if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = "All";
}


/*
|--------------------------------------------------------------------------
| Get Requests
|--------------------------------------------------------------------------
*/

try {

    if ($status_filter === "All") {

        $stmt = $pdo->query("
            SELECT

                r.request_id,
                r.request_reference,
                r.purpose,
                r.request_status,
                r.date_requested,
                r.time_requested,

                c.certification_name,

                CONCAT(
                    res.first_name,
                    ' ',
                    res.last_name
                ) AS resident_name

            FROM requests r

            INNER JOIN residents res
                ON r.resident_id = res.resident_id

            INNER JOIN certifications c
                ON r.certification_id = c.certification_id

            ORDER BY r.created_at DESC
        ");

    } else {

        $stmt = $pdo->prepare("
            SELECT

                r.request_id,
                r.request_reference,
                r.purpose,
                r.request_status,
                r.date_requested,
                r.time_requested,

                c.certification_name,

                CONCAT(
                    res.first_name,
                    ' ',
                    res.last_name
                ) AS resident_name

            FROM requests r

            INNER JOIN residents res
                ON r.resident_id = res.resident_id

            INNER JOIN certifications c
                ON r.certification_id = c.certification_id

            WHERE r.request_status = ?

            ORDER BY r.created_at DESC
        ");

        $stmt->execute([$status_filter]);
    }


    $requests = $stmt->fetchAll();

} catch (PDOException $e) {

    die("Unable to load requests.");

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
        Requests - Staff Portal
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
                    class="text-gray-600 transition hover:text-gray-900"
                >
                    Dashboard
                </a>

                <a
                    href="requests.php"
                    class="font-medium text-gray-900"
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

            <h2 class="text-2xl font-semibold tracking-tight">
                Certification Requests
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Review and manage certification requests submitted by residents.
            </p>

        </div>


        <!-- Filters -->

        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="px-6 py-5">

                <div class="flex flex-wrap gap-2">


                    <!-- All -->

                    <a
                        href="requests.php"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "All"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        All
                    </a>


                    <!-- Pending -->

                    <a
                        href="requests.php?status=Pending"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "Pending"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        Pending
                    </a>


                    <!-- Approved -->

                    <a
                        href="requests.php?status=Approved"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "Approved"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        Approved
                    </a>


                    <!-- Scheduled -->

                    <a
                        href="requests.php?status=Scheduled"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "Scheduled"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        Scheduled
                    </a>


                    <!-- Ready -->

                    <a
                        href="requests.php?status=Ready%20for%20Pickup"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "Ready for Pickup"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        Ready for Pickup
                    </a>


                    <!-- Released -->

                    <a
                        href="requests.php?status=Released"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "Released"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        Released
                    </a>


                    <!-- Rejected -->

                    <a
                        href="requests.php?status=Rejected"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "Rejected"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        Rejected
                    </a>


                    <!-- Cancelled -->

                    <a
                        href="requests.php?status=Cancelled"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition
                        <?= $status_filter === "Cancelled"
                            ? "bg-gray-900 text-white"
                            : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                        ?>"
                    >
                        Cancelled
                    </a>

                </div>

            </div>

        </div>


        <!-- Request Table -->

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


            <?php if (empty($requests)): ?>

                <!-- Empty State -->

                <div class="px-6 py-16 text-center">

                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-6 w-6 text-gray-500"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 0H6.375c-1.036 0-1.875.84-1.875 1.875v15c0 1.036.84 1.875 1.875 1.875h11.25c1.036 0 1.875-.84 1.875-1.875V11.25a2.25 2.25 0 0 0-2.25-2.25H13.5"
                            />

                        </svg>

                    </div>


                    <h3 class="text-lg font-medium text-gray-900">
                        No requests found
                    </h3>


                    <p class="mt-2 text-sm text-gray-500">

                        <?php if ($status_filter === "All"): ?>

                            There are no certification requests yet.

                        <?php else: ?>

                            There are no requests with the
                            <strong>
                                <?= htmlspecialchars($status_filter) ?>
                            </strong>
                            status.

                        <?php endif; ?>

                    </p>

                </div>


            <?php else: ?>


                <!-- Desktop Table -->

                <div class="hidden overflow-x-auto md:block">

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
                                    Date Requested
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


                            <?php foreach ($requests as $request): ?>

                                <tr class="transition hover:bg-gray-50">


                                    <!-- Reference -->

                                    <td class="px-6 py-5">

                                        <p class="font-medium text-gray-900">

                                            <?= htmlspecialchars(
                                                $request["request_reference"]
                                            ) ?>

                                        </p>

                                    </td>


                                    <!-- Resident -->

                                    <td class="px-6 py-5">

                                        <p class="font-medium text-gray-900">

                                            <?= htmlspecialchars(
                                                $request["resident_name"]
                                            ) ?>

                                        </p>

                                    </td>


                                    <!-- Certification -->

                                    <td class="px-6 py-5">

                                        <p class="text-gray-700">

                                            <?= htmlspecialchars(
                                                $request["certification_name"]
                                            ) ?>

                                        </p>

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
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= getStatusClass(
                                                $request["request_status"]
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $request["request_status"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Action -->

                                    <td class="px-6 py-5">

                                        <a
                                            href="request.php?id=<?= $request["request_id"] ?>"
                                            class="font-medium text-gray-700 transition hover:text-gray-900"
                                        >
                                            View
                                        </a>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


                <!-- Mobile Cards -->

                <div class="space-y-4 p-4 md:hidden">


                    <?php foreach ($requests as $request): ?>

                        <div class="rounded-xl border border-gray-200 p-5">


                            <div class="flex items-start justify-between gap-4">


                                <div>

                                    <p class="text-xs text-gray-500">
                                        Reference
                                    </p>

                                    <p class="mt-1 font-semibold text-gray-900">

                                        <?= htmlspecialchars(
                                            $request["request_reference"]
                                        ) ?>

                                    </p>

                                </div>


                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= getStatusClass(
                                        $request["request_status"]
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $request["request_status"]
                                    ) ?>

                                </span>

                            </div>


                            <div class="mt-5 space-y-4">


                                <div>

                                    <p class="text-xs text-gray-500">
                                        Resident
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-gray-900">

                                        <?= htmlspecialchars(
                                            $request["resident_name"]
                                        ) ?>

                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-gray-500">
                                        Certification
                                    </p>

                                    <p class="mt-1 text-sm text-gray-900">

                                        <?= htmlspecialchars(
                                            $request["certification_name"]
                                        ) ?>

                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-gray-500">
                                        Date Requested
                                    </p>

                                    <p class="mt-1 text-sm text-gray-900">

                                        <?= date(
                                            "F d, Y",
                                            strtotime(
                                                $request["date_requested"]
                                            )
                                        ) ?>

                                    </p>

                                </div>


                            </div>


                            <div class="mt-5 border-t border-gray-100 pt-4">

                                <a
                                    href="request.php?id=<?= $request["request_id"] ?>"
                                    class="text-sm font-medium text-gray-700 hover:text-gray-900"
                                >
                                    View Request →
                                </a>

                            </div>


                        </div>

                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>


        <!-- Back to Dashboard -->

        <div class="mt-6">

            <a
                href="dashboard.php"
                class="text-sm text-gray-500 transition hover:text-gray-900"
            >
                ← Back to Dashboard
            </a>

        </div>


    </main>

</body>

</html>