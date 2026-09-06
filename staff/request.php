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

$staff_id = $_SESSION["user_id"];

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

/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Process Request
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    try {

        /*
        |--------------------------------------------------------------------------
        | Get Current Request
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                request_id,
                request_status
            FROM requests
            WHERE request_id = ?
            LIMIT 1
        ");

        $stmt->execute([$request_id]);

        $current_request = $stmt->fetch();

        if (!$current_request) {

            header("Location: requests.php");
            exit;

        }


        /*
        |--------------------------------------------------------------------------
        | Approve Request
        |--------------------------------------------------------------------------
        */

        if ($action === "approve") {

    $pdo->beginTransaction();

    try {

        // Confirm the appointment selected by the resident
        $appointmentStmt = $pdo->prepare("
            UPDATE appointments
            SET status = 'Scheduled'
            WHERE request_id = ?
        ");

        $appointmentStmt->execute([
            $request_id
        ]);

        // Approve the request and immediately schedule it
        $requestStmt = $pdo->prepare("
            UPDATE requests
            SET
                request_status = 'Scheduled',
                processed_by = ?,
                processed_at = NOW()
            WHERE request_id = ?
        ");

        $requestStmt->execute([
            $staff_id,
            $request_id
        ]);

        $pdo->commit();

        $success = "Request approved and appointment scheduled successfully.";

    } catch (Exception $e) {

        $pdo->rollBack();

        $error = "Unable to approve request: " . $e->getMessage();
    }
}


        /*
        |--------------------------------------------------------------------------
        | Mark as Released
        |--------------------------------------------------------------------------
        */

        elseif ($action === "release") {

            $stmt = $pdo->prepare("
                UPDATE requests
                SET
                    request_status = 'Released',
                    released_at = NOW()
                WHERE request_id = ?
            ");

            $stmt->execute([
                $request_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Appointment
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE appointments
                SET
                    status = 'Completed'
                WHERE request_id = ?
            ");

            $stmt->execute([
                $request_id
            ]);

            $success = "Certification has been marked as released.";
        }


        /*
        |--------------------------------------------------------------------------
        | Cancel Request
        |--------------------------------------------------------------------------
        */

        elseif ($action === "cancel") {

            $remarks = trim($_POST["remarks"] ?? "");

            if (empty($remarks)) {

                $error = "Please provide a reason for cancelling the request.";

            } else {

                $stmt = $pdo->prepare("
                    UPDATE requests
                    SET
                        request_status = 'Cancelled',
                        processed_by = ?,
                        processed_at = NOW(),
                        remarks = ?
                    WHERE request_id = ?
                ");

                $stmt->execute([
                    $staff_id,
                    $remarks,
                    $request_id
                ]);

                $success = "Request has been cancelled.";
            }
        }

    } catch (PDOException $e) {

        $error = "Unable to update the request. Please try again.";

    }
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

            res.resident_id,
            res.first_name,
            res.middle_name,
            res.last_name,
            res.suffix,
            res.birth_date,
            res.sex,
            res.address,
            res.contact_number,

            u.email,

            a.appointment_date,
            a.appointment_time,
            a.status AS appointment_status

        FROM requests r

        INNER JOIN certifications c
            ON r.certification_id = c.certification_id

        INNER JOIN residents res
            ON r.resident_id = res.resident_id

        INNER JOIN users u
            ON res.user_id = u.user_id

        LEFT JOIN appointments a
            ON r.request_id = a.request_id

        WHERE r.request_id = ?

        LIMIT 1
    ");

    $stmt->execute([$request_id]);

    $request = $stmt->fetch();

    if (!$request) {

        header("Location: requests.php");
        exit;

    }

} catch (PDOException $e) {

    die("Unable to load request details.");

}


/*
|--------------------------------------------------------------------------
| Resident Full Name
|--------------------------------------------------------------------------
*/

$resident_name = $request["first_name"];

if (!empty($request["middle_name"])) {
    $resident_name .= " " . $request["middle_name"];
}

$resident_name .= " " . $request["last_name"];

if (!empty($request["suffix"])) {
    $resident_name .= " " . $request["suffix"];
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
        Request Details - Staff Portal
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
                    Staff Portal
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

    <main class="mx-auto max-w-5xl px-6 py-10">


        <!-- Back -->

        <div class="mb-6">

            <a
                href="requests.php"
                class="text-sm text-gray-500 transition hover:text-gray-900"
            >
                ← Back to Requests
            </a>

        </div>


        <!-- Success Message -->

        <?php if ($success): ?>

            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-5 py-4">

                <p class="text-sm font-medium text-green-700">
                    <?= htmlspecialchars($success) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- Error Message -->

        <?php if ($error): ?>

            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-5 py-4">

                <p class="text-sm font-medium text-red-700">
                    <?= htmlspecialchars($error) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- Header -->

        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            <div>

                <p class="text-sm text-gray-500">
                    Request Reference
                </p>

                <h2 class="mt-1 text-2xl font-semibold tracking-tight">
                    <?= htmlspecialchars(
                        $request["request_reference"]
                    ) ?>
                </h2>

            </div>


            <span
                class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-medium <?= getStatusClass($request["request_status"]) ?>"
            >

                <?= htmlspecialchars(
                    $request["request_status"]
                ) ?>

            </span>

        </div>


        <!-- Resident Information -->

        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">

                <h3 class="font-semibold text-gray-900">
                    Resident Information
                </h3>

            </div>


            <div class="grid gap-6 px-6 py-6 sm:grid-cols-2">


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Full Name
                    </p>

                    <p class="mt-2 text-sm font-medium text-gray-900">
                        <?= htmlspecialchars($resident_name) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Email Address
                    </p>

                    <p class="mt-2 break-all text-sm text-gray-900">
                        <?= htmlspecialchars($request["email"]) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Birth Date
                    </p>

                    <p class="mt-2 text-sm text-gray-900">
                        <?= date(
                            "F d, Y",
                            strtotime($request["birth_date"])
                        ) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Sex
                    </p>

                    <p class="mt-2 text-sm text-gray-900">
                        <?= htmlspecialchars($request["sex"]) ?>
                    </p>

                </div>


                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Contact Number
                    </p>

                    <p class="mt-2 text-sm text-gray-900">
                        <?= htmlspecialchars(
                            $request["contact_number"] ?: "Not provided"
                        ) ?>
                    </p>

                </div>


                <div class="sm:col-span-2">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Address
                    </p>

                    <p class="mt-2 text-sm leading-6 text-gray-900">
                        <?= nl2br(
                            htmlspecialchars($request["address"])
                        ) ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- Certification Information -->

        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">

                <h3 class="font-semibold text-gray-900">
                    Certification Request
                </h3>

            </div>


            <div class="px-6 py-6">


                <div class="mb-6">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Certification
                    </p>

                    <p class="mt-2 text-lg font-semibold text-gray-900">
                        <?= htmlspecialchars(
                            $request["certification_name"]
                        ) ?>
                    </p>

                </div>


                <div class="grid gap-6 sm:grid-cols-3">


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Fee
                        </p>

                        <p class="mt-2 text-sm font-medium text-gray-900">
                            ₱<?= number_format(
                                $request["fee"],
                                2
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
                            Date Requested
                        </p>

                        <p class="mt-2 text-sm text-gray-900">

                            <?= date(
                                "F d, Y",
                                strtotime(
                                    $request["date_requested"]
                                )
                            ) ?>

                        </p>

                    </div>

                </div>


                <!-- Purpose -->

                <div class="mt-6 border-t border-gray-100 pt-6">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Purpose
                    </p>

                    <p class="mt-2 text-sm leading-6 text-gray-900">
                        <?= htmlspecialchars(
                            $request["purpose"]
                        ) ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- Appointment -->

        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">

                <h3 class="font-semibold text-gray-900">
                    Appointment
                </h3>

            </div>


            <div class="px-6 py-6">

                <?php if ($request["appointment_date"]): ?>

                    <div class="grid gap-6 sm:grid-cols-3">


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
                                Status
                            </p>

                            <p class="mt-2 text-sm text-gray-900">
                                <?= htmlspecialchars(
                                    $request["appointment_status"]
                                ) ?>
                            </p>

                        </div>

                    </div>

                <?php else: ?>

                    <p class="text-sm text-gray-500">
                        No appointment has been scheduled.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- Remarks -->

        <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">

                <h3 class="font-semibold text-gray-900">
                    Staff Remarks
                </h3>

            </div>


            <div class="px-6 py-6">

                <?php if (!empty($request["remarks"])): ?>

                    <div class="rounded-lg bg-gray-50 p-4">

                        <p class="text-sm leading-6 text-gray-700">
                            <?= nl2br(
                                htmlspecialchars(
                                    $request["remarks"]
                                )
                            ) ?>
                        </p>

                    </div>

                <?php else: ?>

                    <p class="text-sm text-gray-400">
                        No remarks have been added.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- Staff Actions -->

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">

                <h3 class="font-semibold text-gray-900">
                    Request Actions
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Update the status of this certification request.
                </p>

            </div>


            <div class="px-6 py-6">


                <!-- Pending -->

                <?php if ($request["request_status"] === "Pending"): ?>

                    <div class="flex flex-col gap-4 sm:flex-row">


                        <!-- Approve -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="approve"
                            >

                            <button
                                type="submit"
                                class="w-full rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 sm:w-auto"
                            >
                                Approve Request
                            </button>

                        </form>


                        <!-- Reject -->

                        <form
                            method="POST"
                            class="w-full sm:max-w-md"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="reject"
                            >

                            <div class="flex gap-2">

                                <input
                                    type="text"
                                    name="remarks"
                                    placeholder="Reason for rejection"
                                    required
                                    class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-500"
                                >

                                <button
                                    type="submit"
                                    class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-red-700"
                                >
                                    Reject
                                </button>

                            </div>

                        </form>

                    </div>


                <!-- Approved -->

                <?php elseif ($request["request_status"] === "Approved"): ?>

                    <div class="flex flex-col gap-4 sm:flex-row">

                        <a
                            href="appointments.php?request_id=<?= $request["request_id"] ?>"
                            class="inline-flex justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                        >
                            Set Appointment
                        </a>

                    </div>


                <!-- Scheduled -->

                <?php elseif ($request["request_status"] === "Scheduled"): ?>

                    <div class="flex flex-col gap-4 sm:flex-row">

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="ready"
                            >

                            <button
                                type="submit"
                                class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                            >
                                Mark Ready for Pickup
                            </button>

                        </form>

                    </div>


                <!-- Ready -->

                <?php elseif ($request["request_status"] === "Ready for Pickup"): ?>

                    <div class="flex flex-col gap-4 sm:flex-row">

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="release"
                            >

                            <button
                                type="submit"
                                class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                            >
                                Mark as Released
                            </button>

                        </form>

                    </div>


                <!-- Other Status -->

                <?php else: ?>

                    <p class="text-sm text-gray-500">
                        No further actions are available for this request.
                    </p>

                <?php endif; ?>


                <!-- Cancel -->

                <?php if (
                    $request["request_status"] !== "Released" &&
                    $request["request_status"] !== "Rejected" &&
                    $request["request_status"] !== "Cancelled"
                ): ?>

                    <div class="mt-6 border-t border-gray-100 pt-6">

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="cancel"
                            >

                            <div class="flex flex-col gap-3 sm:flex-row">

                                <input
                                    type="text"
                                    name="remarks"
                                    placeholder="Reason for cancellation"
                                    required
                                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-500"
                                >

                                <button
                                    type="submit"
                                    class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                >
                                    Cancel Request
                                </button>

                            </div>

                        </form>

                    </div>

                <?php endif; ?>

            </div>

        </div>


    </main>

</body>

</html>