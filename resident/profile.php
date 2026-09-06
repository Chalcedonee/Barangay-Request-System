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
| Get Resident Profile
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            u.email,
            r.resident_id,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.suffix,
            r.birth_date,
            r.sex,
            r.address,
            r.contact_number
        FROM users u

        INNER JOIN residents r
            ON u.user_id = r.user_id

        WHERE u.user_id = ?

        LIMIT 1
    ");

    $stmt->execute([$user_id]);

    $resident = $stmt->fetch();

    if (!$resident) {
        die("Resident profile not found.");
    }

} catch (PDOException $e) {

    die("Unable to load your profile.");

}

/*
|--------------------------------------------------------------------------
| Full Name
|--------------------------------------------------------------------------
*/

$full_name = $resident["first_name"];

if (!empty($resident["middle_name"])) {
    $full_name .= " " . $resident["middle_name"];
}

$full_name .= " " . $resident["last_name"];

if (!empty($resident["suffix"])) {
    $full_name .= " " . $resident["suffix"];
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
        My Profile - Barangay Request System
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>


<body class="min-h-screen bg-gray-50 text-gray-900">


    <!-- Navigation -->

    <nav class="border-b border-gray-200 bg-white">

        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">

            <!-- Logo / System Name -->

            <div>

                <h1 class="text-lg font-semibold">
                    Barangay Request System
                </h1>

                <p class="text-xs text-gray-500">
                    Resident Portal
                </p>

            </div>


            <!-- Navigation Links -->

            <div class="flex items-center gap-6 text-sm">

                <a
                    href="dashboard.php"
                    class="text-gray-600 transition hover:text-gray-900"
                >
                    Dashboard
                </a>

                <a
                    href="requests.php"
                    class="text-gray-600 transition hover:text-gray-900"
                >
                    My Requests
                </a>

                <a
                    href="profile.php"
                    class="font-medium text-gray-900"
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


        <!-- Page Header -->

        <div class="mb-8">

            <h2 class="text-2xl font-semibold tracking-tight">
                My Profile
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                View your personal information and account details.
            </p>

        </div>


        <!-- Profile Card -->

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


            <!-- Profile Header -->

            <div class="border-b border-gray-200 px-6 py-6">

                <div class="flex items-center gap-4">


                    <!-- Profile Initial -->

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-900 text-lg font-semibold text-white"
                    >

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $resident["first_name"],
                                    0,
                                    1
                                )
                            )
                        ) ?>

                    </div>


                    <!-- Name -->

                    <div>

                        <h3 class="text-lg font-semibold text-gray-900">
                            <?= htmlspecialchars($full_name) ?>
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Resident
                        </p>

                    </div>

                </div>

            </div>


            <!-- Personal Information -->

            <div class="px-6 py-6">

                <h3 class="mb-6 text-sm font-semibold text-gray-900">
                    Personal Information
                </h3>


                <div class="grid gap-6 sm:grid-cols-2">


                    <!-- First Name -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            First Name
                        </p>

                        <p class="mt-2 text-sm text-gray-900">
                            <?= htmlspecialchars(
                                $resident["first_name"]
                            ) ?>
                        </p>

                    </div>


                    <!-- Middle Name -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Middle Name
                        </p>

                        <p class="mt-2 text-sm text-gray-900">

                            <?php if (!empty($resident["middle_name"])): ?>

                                <?= htmlspecialchars(
                                    $resident["middle_name"]
                                ) ?>

                            <?php else: ?>

                                <span class="text-gray-400">
                                    Not provided
                                </span>

                            <?php endif; ?>

                        </p>

                    </div>


                    <!-- Last Name -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Last Name
                        </p>

                        <p class="mt-2 text-sm text-gray-900">
                            <?= htmlspecialchars(
                                $resident["last_name"]
                            ) ?>
                        </p>

                    </div>


                    <!-- Suffix -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Suffix
                        </p>

                        <p class="mt-2 text-sm text-gray-900">

                            <?php if (!empty($resident["suffix"])): ?>

                                <?= htmlspecialchars(
                                    $resident["suffix"]
                                ) ?>

                            <?php else: ?>

                                <span class="text-gray-400">
                                    None
                                </span>

                            <?php endif; ?>

                        </p>

                    </div>


                    <!-- Birth Date -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Birth Date
                        </p>

                        <p class="mt-2 text-sm text-gray-900">
                            <?= date(
                                "F d, Y",
                                strtotime(
                                    $resident["birth_date"]
                                )
                            ) ?>
                        </p>

                    </div>


                    <!-- Sex -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Sex
                        </p>

                        <p class="mt-2 text-sm text-gray-900">
                            <?= htmlspecialchars(
                                $resident["sex"]
                            ) ?>
                        </p>

                    </div>


                    <!-- Contact Number -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Contact Number
                        </p>

                        <p class="mt-2 text-sm text-gray-900">

                            <?php if (!empty($resident["contact_number"])): ?>

                                <?= htmlspecialchars(
                                    $resident["contact_number"]
                                ) ?>

                            <?php else: ?>

                                <span class="text-gray-400">
                                    Not provided
                                </span>

                            <?php endif; ?>

                        </p>

                    </div>


                    <!-- Email -->

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Email Address
                        </p>

                        <p class="mt-2 break-all text-sm text-gray-900">
                            <?= htmlspecialchars(
                                $resident["email"]
                            ) ?>
                        </p>

                    </div>


                    <!-- Address -->

                    <div class="sm:col-span-2">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Address
                        </p>

                        <p class="mt-2 text-sm leading-6 text-gray-900">
                            <?= nl2br(
                                htmlspecialchars(
                                    $resident["address"]
                                )
                            ) ?>
                        </p>

                    </div>

                </div>

            </div>


            <!-- Account Information -->

            <div class="border-t border-gray-200 bg-gray-50 px-6 py-6">

                <h3 class="mb-4 text-sm font-semibold text-gray-900">
                    Account Information
                </h3>


                <div class="grid gap-6 sm:grid-cols-2">


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Account Type
                        </p>

                        <p class="mt-2 text-sm text-gray-900">
                            Resident
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Account Status
                        </p>

                        <p class="mt-2">

                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                Active
                            </span>

                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- Back to Dashboard -->

        <div class="mt-6">

            <a
                href="dashboard.php"
                class="inline-flex rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                ← Back to Dashboard
            </a>

        </div>


    </main>

</body>

</html>