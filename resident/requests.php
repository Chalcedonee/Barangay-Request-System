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

$user_id = $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Get Resident ID
|--------------------------------------------------------------------------
*/

try {
    $stmt = $pdo->prepare("
        SELECT resident_id, first_name, last_name
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
| Get All Requests
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
            r.remarks,

            c.certification_name,
            c.fee,

            a.appointment_date,
            a.appointment_time,
            a.status AS appointment_status

        FROM requests r

        INNER JOIN certifications c
            ON r.certification_id = c.certification_id

        LEFT JOIN appointments a
            ON r.request_id = a.request_id

        WHERE r.resident_id = ?

        ORDER BY r.created_at DESC
    ");

    $stmt->execute([$resident_id]);
    $requests = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Unable to load your requests.");
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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Requests - Barangay Request System</title>

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

    <main class="mx-auto max-w-7xl px-6 py-10">

        <!-- Header -->

        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-semibold tracking-tight">
                    My Requests
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    View and track your certification requests.
                </p>

            </div>

            <a
                href="request.php"
                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
            >
                + Request Certification
            </a>

        </div>


        <!-- Request List -->

        <?php if (empty($requests)): ?>

            <!-- Empty State -->

            <div class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">

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
                    No requests yet
                </h3>

                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    You haven't submitted any certification requests yet.
                </p>

                <a
                    href="request.php"
                    class="mt-6 inline-flex rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                >
                    Request a Certification
                </a>

            </div>

        <?php else: ?>

            <!-- Desktop Table -->

            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">

                <div class="overflow-x-auto">

                    <table class="w-full text-left text-sm">

                        <thead class="border-b border-gray-200 bg-gray-50">

                            <tr>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Reference
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Certification
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Date Requested
                                </th>

                                <th class="px-6 py-4 font-medium text-gray-600">
                                    Appointment
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
                                            <?= htmlspecialchars($request["request_reference"]) ?>
                                        </p>

                                    </td>


                                    <!-- Certification -->

                                    <td class="px-6 py-5">

                                        <p class="font-medium text-gray-900">
                                            <?= htmlspecialchars($request["certification_name"]) ?>
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Fee: ₱<?= number_format($request["fee"], 2) ?>
                                        </p>

                                    </td>


                                    <!-- Date Requested -->

                                    <td class="px-6 py-5">

                                        <p class="text-gray-700">
                                            <?= date("M d, Y", strtotime($request["date_requested"])) ?>
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            <?= date("h:i A", strtotime($request["time_requested"])) ?>
                                        </p>

                                    </td>


                                    <!-- Appointment -->

                                    <td class="px-6 py-5">

                                        <?php if ($request["appointment_date"]): ?>

                                            <p class="text-gray-700">
                                                <?= date("M d, Y", strtotime($request["appointment_date"])) ?>
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500">
                                                <?= date("h:i A", strtotime($request["appointment_time"])) ?>
                                            </p>

                                        <?php else: ?>

                                            <span class="text-gray-400">
                                                Not scheduled
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Status -->

                                    <td class="px-6 py-5">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= getStatusClass($request["request_status"]) ?>"
                                        >
                                            <?= htmlspecialchars($request["request_status"]) ?>
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

            </div>


            <!-- Mobile Cards -->

            <div class="space-y-4 md:hidden">

                <?php foreach ($requests as $request): ?>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs text-gray-500">
                                    Reference
                                </p>

                                <p class="mt-1 font-semibold text-gray-900">
                                    <?= htmlspecialchars($request["request_reference"]) ?>
                                </p>

                            </div>

                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= getStatusClass($request["request_status"]) ?>"
                            >
                                <?= htmlspecialchars($request["request_status"]) ?>
                            </span>

                        </div>


                        <div class="mt-5 space-y-3 text-sm">

                            <div>

                                <p class="text-xs text-gray-500">
                                    Certification
                                </p>

                                <p class="mt-1 font-medium text-gray-900">
                                    <?= htmlspecialchars($request["certification_name"]) ?>
                                </p>

                            </div>


                            <div class="grid grid-cols-2 gap-4">

                                <div>

                                    <p class="text-xs text-gray-500">
                                        Date Requested
                                    </p>

                                    <p class="mt-1 text-gray-700">
                                        <?= date("M d, Y", strtotime($request["date_requested"])) ?>
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-gray-500">
                                        Appointment
                                    </p>

                                    <?php if ($request["appointment_date"]): ?>

                                        <p class="mt-1 text-gray-700">
                                            <?= date("M d, Y", strtotime($request["appointment_date"])) ?>
                                        </p>

                                    <?php else: ?>

                                        <p class="mt-1 text-gray-400">
                                            Not scheduled
                                        </p>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>


                        <div class="mt-5 border-t border-gray-100 pt-4">

                            <a
                                href="request_details.php?id=<?= $request["request_id"] ?>"
                                class="text-sm font-medium text-gray-700 hover:text-gray-900"
                            >
                                View Request →
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

</body>

</html>