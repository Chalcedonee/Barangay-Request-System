<?php

require_once "config/database.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $suffix = trim($_POST["suffix"]);
    $birth_date = $_POST["birth_date"];
    $sex = $_POST["sex"];
    $address = trim($_POST["address"]);
    $contact_number = trim($_POST["contact_number"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check required fields
    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($birth_date) ||
        empty($sex) ||
        empty($address) ||
        empty($email) ||
        empty($password)
    ) {
        $error = "Please fill in all required fields.";
    }

    // Check password
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    }

    else {

        try {

            // Check if email already exists
            $check = $pdo->prepare(
                "SELECT user_id FROM users WHERE email = ?"
            );

            $check->execute([$email]);

            if ($check->fetch()) {

                $error = "An account with this email already exists.";

            } else {

                // Start transaction
                $pdo->beginTransaction();

                // Hash password
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Create user account
                $stmt = $pdo->prepare("
                    INSERT INTO users
                    (email, password, role, status)
                    VALUES (?, ?, 'resident', 'active')
                ");

                $stmt->execute([
                    $email,
                    $hashed_password
                ]);

                $user_id = $pdo->lastInsertId();

                // Create resident record
                $stmt = $pdo->prepare("
                    INSERT INTO residents
                    (
                        user_id,
                        first_name,
                        middle_name,
                        last_name,
                        suffix,
                        birth_date,
                        sex,
                        address,
                        contact_number
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $user_id,
                    $first_name,
                    $middle_name ?: null,
                    $last_name,
                    $suffix ?: null,
                    $birth_date,
                    $sex,
                    $address,
                    $contact_number ?: null
                ]);

                // Save everything
                $pdo->commit();

                $message = "Registration successful! You can now log in.";

            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Registration failed. Please try again.";

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

    <title>Create Account - Barangay Request System</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="min-h-screen bg-gray-50 text-gray-900">

    <main class="px-6 py-12">

        <div class="mx-auto w-full max-w-3xl">

            <!-- Header -->
            <div class="mb-8 text-center">

                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                    Barangay Request System
                </h1>

                <p class="mt-2 text-sm text-gray-500">
                    Create an account to access barangay services
                </p>

            </div>


            <!-- Registration Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm md:p-10">

                <div class="mb-8">

                    <h2 class="text-xl font-semibold text-gray-900">
                        Create your account
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Please provide your information below.
                    </p>

                </div>


                <!-- Error -->
                <?php if ($error): ?>

                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                        <p class="text-sm text-red-700">
                            <?= htmlspecialchars($error) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <!-- Success -->
                <?php if ($message): ?>

                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3">

                        <p class="text-sm text-green-700">
                            <?= htmlspecialchars($message) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <form method="POST" class="space-y-8">

                    <!-- Personal Information -->
                    <section>

                        <div class="mb-5 border-b border-gray-100 pb-3">

                            <h3 class="text-base font-semibold text-gray-900">
                                Personal Information
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Enter your basic personal information.
                            </p>

                        </div>


                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            <!-- First Name -->
                            <div>

                                <label
                                    for="first_name"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    First Name <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    value="<?= htmlspecialchars($_POST["first_name"] ?? "") ?>"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>


                            <!-- Middle Name -->
                            <div>

                                <label
                                    for="middle_name"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Middle Name
                                </label>

                                <input
                                    type="text"
                                    id="middle_name"
                                    name="middle_name"
                                    value="<?= htmlspecialchars($_POST["middle_name"] ?? "") ?>"
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>


                            <!-- Last Name -->
                            <div>

                                <label
                                    for="last_name"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Last Name <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    value="<?= htmlspecialchars($_POST["last_name"] ?? "") ?>"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>


                            <!-- Suffix -->
                            <div>

                                <label
                                    for="suffix"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Suffix
                                </label>

                                <input
                                    type="text"
                                    id="suffix"
                                    name="suffix"
                                    value="<?= htmlspecialchars($_POST["suffix"] ?? "") ?>"
                                    placeholder="Jr., Sr., III"
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>


                            <!-- Birth Date -->
                            <div>

                                <label
                                    for="birth_date"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Birth Date <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="date"
                                    id="birth_date"
                                    name="birth_date"
                                    value="<?= htmlspecialchars($_POST["birth_date"] ?? "") ?>"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>


                            <!-- Sex -->
                            <div>

                                <label
                                    for="sex"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Sex <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="sex"
                                    name="sex"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                                    <option value="">
                                        Select
                                    </option>

                                    <option
                                        value="Male"
                                        <?= (($_POST["sex"] ?? "") === "Male") ? "selected" : "" ?>
                                    >
                                        Male
                                    </option>

                                    <option
                                        value="Female"
                                        <?= (($_POST["sex"] ?? "") === "Female") ? "selected" : "" ?>
                                    >
                                        Female
                                    </option>

                                </select>

                            </div>


                            <!-- Address -->
                            <div class="md:col-span-2">

                                <label
                                    for="address"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Address <span class="text-red-500">*</span>
                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    rows="3"
                                    required
                                    class="block w-full resize-none rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                ><?= htmlspecialchars($_POST["address"] ?? "") ?></textarea>

                            </div>


                            <!-- Contact Number -->
                            <div class="md:col-span-2">

                                <label
                                    for="contact_number"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Contact Number
                                </label>

                                <input
                                    type="text"
                                    id="contact_number"
                                    name="contact_number"
                                    value="<?= htmlspecialchars($_POST["contact_number"] ?? "") ?>"
                                    placeholder="09XXXXXXXXX"
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>

                        </div>

                    </section>


                    <!-- Account Information -->
                    <section>

                        <div class="mb-5 border-b border-gray-100 pb-3">

                            <h3 class="text-base font-semibold text-gray-900">
                                Account Information
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Create the login details for your account.
                            </p>

                        </div>


                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            <!-- Email -->
                            <div class="md:col-span-2">

                                <label
                                    for="email"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Email Address <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                                    placeholder="you@example.com"
                                    autocomplete="email"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>


                            <!-- Password -->
                            <div>

                                <label
                                    for="password"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Password <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                                <p class="mt-2 text-xs text-gray-400">
                                    Must be at least 8 characters.
                                </p>

                            </div>


                            <!-- Confirm Password -->
                            <div>

                                <label
                                    for="confirm_password"
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                >

                            </div>

                        </div>

                    </section>


                    <!-- Submit -->
                    <div class="border-t border-gray-100 pt-6">

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                        >
                            Create Account
                        </button>

                    </div>

                </form>


                <!-- Login Link -->
                <div class="mt-6 text-center">

                    <p class="text-sm text-gray-500">

                        Already have an account?

                        <a
                            href="login.php"
                            class="font-medium text-gray-900 hover:underline"
                        >
                            Log in
                        </a>

                    </p>

                </div>

            </div>


            <!-- Footer -->
            <p class="mt-6 text-center text-xs text-gray-400">
                Barangay Request System
            </p>

        </div>

    </main>

</body>

</html>