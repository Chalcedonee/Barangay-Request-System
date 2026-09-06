<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Staff Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "staff") {
    header("Location: ../login.php");
    exit;
}

$message = "";
$message_type = "";

/*
|--------------------------------------------------------------------------
| Selected Request
|--------------------------------------------------------------------------
*/

$selected_request_id = isset($_GET["request_id"])
    ? (int) $_GET["request_id"]
    : 0;


/*
|--------------------------------------------------------------------------
| Handle Appointment Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $appointment_id = (int) ($_POST["appointment_id"] ?? 0);

    try {

        /*
        |--------------------------------------------------------------------------
        | Confirm Appointment
        |--------------------------------------------------------------------------
        */

        if ($action === "confirm") {

            $appointment_date = $_POST["appointment_date"] ?? "";
            $appointment_time = $_POST["appointment_time"] ?? "";

            if (
                empty($appointment_date) ||
                empty($appointment_time)
            ) {
                throw new Exception(
                    "Please provide an appointment date and time."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Past Appointments
            |--------------------------------------------------------------------------
            */

            $appointment_datetime = strtotime(
                $appointment_date . " " . $appointment_time
            );

            if (
                $appointment_datetime === false ||
                $appointment_datetime < time()
            ) {
                throw new Exception(
                    "The appointment date and time cannot be in the past."
                );
            }

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Get Appointment
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    a.appointment_id,
                    a.request_id,
                    a.status AS appointment_status,
                    r.request_status
                FROM appointments a
                INNER JOIN requests r
                    ON a.request_id = r.request_id
                WHERE a.appointment_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$appointment_id]);

            $appointment = $stmt->fetch();

            if (!$appointment) {
                throw new Exception("Appointment not found.");
            }

            /*
            |--------------------------------------------------------------------------
            | Only Approved Requests Can Be Scheduled
            |--------------------------------------------------------------------------
            */

            if (
                $appointment["request_status"] !== "Approved" &&
                $appointment["request_status"] !== "Scheduled"
            ) {
                throw new Exception(
                    "Only approved requests can be scheduled."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Update Appointment
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE appointments
                SET
                    appointment_date = ?,
                    appointment_time = ?,
                    status = 'Scheduled'
                WHERE appointment_id = ?
            ");

            $stmt->execute([
                $appointment_date,
                $appointment_time,
                $appointment_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Request Status
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE requests
                SET request_status = 'Scheduled'
                WHERE request_id = ?
            ");

            $stmt->execute([
                $appointment["request_id"]
            ]);

            $pdo->commit();

            $message = "Appointment confirmed successfully.";
            $message_type = "success";

            $selected_request_id = $appointment["request_id"];
        }


        /*
        |--------------------------------------------------------------------------
        | Cancel Appointment
        |--------------------------------------------------------------------------
        */

        elseif ($action === "cancel") {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT request_id
                FROM appointments
                WHERE appointment_id = ?
                FOR UPDATE
            ");

            $stmt->execute([$appointment_id]);

            $appointment = $stmt->fetch();

            if (!$appointment) {
                throw new Exception("Appointment not found.");
            }

            /*
            |--------------------------------------------------------------------------
            | Cancel Appointment
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE appointments
                SET status = 'Cancelled'
                WHERE appointment_id = ?
            ");

            $stmt->execute([
                $appointment_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Cancel Request
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE requests
                SET request_status = 'Cancelled'
                WHERE request_id = ?
            ");

            $stmt->execute([
                $appointment["request_id"]
            ]);

            $pdo->commit();

            $message = "Appointment cancelled successfully.";
            $message_type = "success";

            $selected_request_id = $appointment["request_id"];
        }

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $e->getMessage();
        $message_type = "error";
    }
}


/*
|--------------------------------------------------------------------------
| Get Selected Appointment
|--------------------------------------------------------------------------
*/

$selected_appointment = null;

if ($selected_request_id > 0) {

    $stmt = $pdo->prepare("
        SELECT
            a.appointment_id,
            a.request_id,
            a.appointment_date,
            a.appointment_time,
            a.status AS appointment_status,

            r.request_reference,
            r.request_status,
            r.purpose,

            CONCAT(
                res.first_name,
                ' ',
                COALESCE(CONCAT(res.middle_name, ' '), ''),
                res.last_name,
                CASE
                    WHEN res.suffix IS NOT NULL
                    AND res.suffix != ''
                    THEN CONCAT(' ', res.suffix)
                    ELSE ''
                END
            ) AS resident_name,

            c.certification_name

        FROM appointments a

        INNER JOIN requests r
            ON a.request_id = r.request_id

        INNER JOIN residents res
            ON r.resident_id = res.resident_id

        INNER JOIN certifications c
            ON r.certification_id = c.certification_id

        WHERE a.request_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $selected_request_id
    ]);

    $selected_appointment = $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| Get All Appointments
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        a.appointment_id,
        a.request_id,
        a.appointment_date,
        a.appointment_time,
        a.status AS appointment_status,

        r.request_reference,
        r.request_status,

        CONCAT(
            res.first_name,
            ' ',
            COALESCE(CONCAT(res.middle_name, ' '), ''),
            res.last_name,
            CASE
                WHEN res.suffix IS NOT NULL
                AND res.suffix != ''
                THEN CONCAT(' ', res.suffix)
                ELSE ''
            END
        ) AS resident_name,

        c.certification_name

    FROM appointments a

    INNER JOIN requests r
        ON a.request_id = r.request_id

    INNER JOIN residents res
        ON r.resident_id = res.resident_id

    INNER JOIN certifications c
        ON r.certification_id = c.certification_id

    ORDER BY
        CASE
            WHEN a.status = 'Pending' THEN 1
            WHEN a.status = 'Scheduled' THEN 2
            WHEN a.status = 'Missed' THEN 3
            WHEN a.status = 'Cancelled' THEN 4
            WHEN a.status = 'Completed' THEN 5
            ELSE 6
        END,

        a.appointment_date ASC,
        a.appointment_time ASC
");

$appointments = $stmt->fetchAll();

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
        Appointments - Barangay Request System
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="min-h-screen bg-gray-50 text-gray-900">


<!--
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
-->

<header class="border-b bg-white">

    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">

        <a
            href="dashboard.php"
            class="text-lg font-semibold"
        >
            Barangay Request System
        </a>


        <nav class="flex items-center gap-6 text-sm">

            <a
                href="dashboard.php"
                class="text-gray-600 hover:text-gray-900"
            >
                Dashboard
            </a>

            <a
                href="requests.php"
                class="text-gray-600 hover:text-gray-900"
            >
                Requests
            </a>

            <a
                href="appointments.php"
                class="font-medium text-gray-900"
            >
                Appointments
            </a>

            <a
                href="../logout.php"
                class="text-red-600 hover:text-red-700"
            >
                Logout
            </a>

        </nav>

    </div>

</header>


<!--
|--------------------------------------------------------------------------
| Main
|--------------------------------------------------------------------------
-->

<main class="mx-auto max-w-6xl px-6 py-10">


    <div class="mb-8">

        <p class="text-sm text-gray-500">
            Staff Panel
        </p>

        <h1 class="mt-1 text-2xl font-semibold">
            Appointments
        </h1>

        <p class="mt-2 text-sm text-gray-500">
            Confirm and manage resident pickup schedules.
        </p>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Message
    |--------------------------------------------------------------------------
    -->

    <?php if ($message): ?>

        <div
            class="mb-6 rounded-lg border px-4 py-3 text-sm
            <?= $message_type === "success"
                ? "border-green-200 bg-green-50 text-green-700"
                : "border-red-200 bg-red-50 text-red-700"
            ?>"
        >

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!--
    |--------------------------------------------------------------------------
    | Appointment Editor
    |--------------------------------------------------------------------------
    -->

    <?php if ($selected_appointment): ?>

        <section class="mb-8 rounded-xl border bg-white p-6">

            <div class="mb-6">

                <p class="text-sm text-gray-500">
                    Request
                </p>

                <h2 class="text-lg font-semibold">

                    <?= htmlspecialchars(
                        $selected_appointment["request_reference"]
                    ) ?>

                </h2>

            </div>


            <div class="grid gap-6 md:grid-cols-2">


                <div>

                    <p class="text-xs uppercase tracking-wide text-gray-400">
                        Resident
                    </p>

                    <p class="mt-1 font-medium">

                        <?= htmlspecialchars(
                            $selected_appointment["resident_name"]
                        ) ?>

                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-gray-400">
                        Certification
                    </p>

                    <p class="mt-1 font-medium">

                        <?= htmlspecialchars(
                            $selected_appointment["certification_name"]
                        ) ?>

                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-gray-400">
                        Request Status
                    </p>

                    <p class="mt-1 font-medium">

                        <?= htmlspecialchars(
                            $selected_appointment["request_status"]
                        ) ?>

                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-gray-400">
                        Current Appointment Status
                    </p>

                    <p class="mt-1 font-medium">

                        <?= htmlspecialchars(
                            $selected_appointment["appointment_status"]
                        ) ?>

                    </p>

                </div>

            </div>


            <div class="my-6 border-t"></div>


            <?php if (
                $selected_appointment["request_status"] === "Approved" ||
                $selected_appointment["request_status"] === "Scheduled"
            ): ?>

                <form
                    method="POST"
                    class="space-y-5"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="confirm"
                    >

                    <input
                        type="hidden"
                        name="appointment_id"
                        value="<?= $selected_appointment["appointment_id"] ?>"
                    >


                    <div>

                        <label
                            for="appointment_date"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Appointment Date
                        </label>

                        <input
                            type="date"
                            id="appointment_date"
                            name="appointment_date"
                            required
                            min="<?= date("Y-m-d") ?>"
                            value="<?= htmlspecialchars(
                                $selected_appointment["appointment_date"]
                            ) ?>"
                            class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-gray-900"
                        >

                    </div>


                    <div>

                        <label
                            for="appointment_time"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Appointment Time
                        </label>

                        <input
                            type="time"
                            id="appointment_time"
                            name="appointment_time"
                            required
                            value="<?= htmlspecialchars(
                                $selected_appointment["appointment_time"]
                            ) ?>"
                            class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-gray-900"
                        >

                    </div>


                    <div class="flex items-center gap-3">

                        <button
                            type="submit"
                            class="rounded-lg bg-gray-900 px-5 py-3 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            <?= $selected_appointment["request_status"] === "Scheduled"
                                ? "Update Appointment"
                                : "Confirm Appointment"
                            ?>
                        </button>


                        <a
                            href="appointments.php"
                            class="rounded-lg border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </a>

                    </div>

                </form>


            <?php else: ?>

                <p class="text-sm text-gray-500">
                    This appointment cannot currently be modified.
                </p>

            <?php endif; ?>

        </section>

    <?php endif; ?>


    <!--
    |--------------------------------------------------------------------------
    | Appointment List
    |--------------------------------------------------------------------------
    -->

    <section class="overflow-hidden rounded-xl border bg-white">

        <?php if (empty($appointments)): ?>

            <div class="px-6 py-16 text-center">

                <p class="text-sm text-gray-500">
                    No appointments found.
                </p>

            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="border-b bg-gray-50">

                        <tr>

                            <th class="px-6 py-4 text-left font-medium text-gray-600">
                                Reference
                            </th>

                            <th class="px-6 py-4 text-left font-medium text-gray-600">
                                Resident
                            </th>

                            <th class="px-6 py-4 text-left font-medium text-gray-600">
                                Certification
                            </th>

                            <th class="px-6 py-4 text-left font-medium text-gray-600">
                                Date
                            </th>

                            <th class="px-6 py-4 text-left font-medium text-gray-600">
                                Time
                            </th>

                            <th class="px-6 py-4 text-left font-medium text-gray-600">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right font-medium text-gray-600">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y">

                        <?php foreach ($appointments as $appointment): ?>

                            <tr class="hover:bg-gray-50">


                                <td class="px-6 py-4 font-medium">

                                    <?= htmlspecialchars(
                                        $appointment["request_reference"]
                                    ) ?>

                                </td>


                                <td class="px-6 py-4">

                                    <?= htmlspecialchars(
                                        $appointment["resident_name"]
                                    ) ?>

                                </td>


                                <td class="px-6 py-4">

                                    <?= htmlspecialchars(
                                        $appointment["certification_name"]
                                    ) ?>

                                </td>


                                <td class="px-6 py-4">

                                    <?= date(
                                        "M d, Y",
                                        strtotime(
                                            $appointment["appointment_date"]
                                        )
                                    ) ?>

                                </td>


                                <td class="px-6 py-4">

                                    <?= date(
                                        "h:i A",
                                        strtotime(
                                            $appointment["appointment_time"]
                                        )
                                    ) ?>

                                </td>


                                <td class="px-6 py-4">

                                    <?php

                                    $status =
                                        $appointment["appointment_status"];

                                    $status_class = match ($status) {

                                        "Pending" =>
                                            "bg-yellow-50 text-yellow-700",

                                        "Scheduled" =>
                                            "bg-blue-50 text-blue-700",

                                        "Completed" =>
                                            "bg-green-50 text-green-700",

                                        "Cancelled" =>
                                            "bg-red-50 text-red-700",

                                        "Missed" =>
                                            "bg-gray-100 text-gray-700",

                                        default =>
                                            "bg-gray-100 text-gray-700"
                                    };

                                    ?>

                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= $status_class ?>"
                                    >
                                        <?= htmlspecialchars($status) ?>
                                    </span>

                                </td>


                                <td class="px-6 py-4 text-right">

                                    <?php if (
                                        $appointment["request_status"] === "Approved"
                                    ): ?>

                                        <a
                                            href="appointments.php?request_id=<?= $appointment["request_id"] ?>"
                                            class="font-medium text-blue-600 hover:text-blue-700"
                                        >
                                            Set Appointment
                                        </a>


                                    <?php elseif (
                                        $appointment["request_status"] === "Scheduled"
                                    ): ?>

                                        <a
                                            href="appointments.php?request_id=<?= $appointment["request_id"] ?>"
                                            class="font-medium text-blue-600 hover:text-blue-700"
                                        >
                                            Edit
                                        </a>


                                    <?php else: ?>

                                        <a
                                            href="request.php?id=<?= $appointment["request_id"] ?>"
                                            class="font-medium text-gray-600 hover:text-gray-900"
                                        >
                                            View
                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>


</main>

</body>

</html>