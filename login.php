<?php

session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Check if fields are empty
    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        try {

            // Find the user by email
            $stmt = $pdo->prepare("
                SELECT
                    user_id,
                    email,
                    password,
                    role,
                    status
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            $user = $stmt->fetch();

            // Check if account exists
            if (!$user) {

                $error = "Invalid email or password.";

            }

            // Check account status
            elseif ($user["status"] !== "active") {

                $error = "Your account is currently inactive.";

            }

            // Check password
            elseif (!password_verify($password, $user["password"])) {

                $error = "Invalid email or password.";

            }

            else {

                // Login successful
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                // Redirect based on role
                if ($user["role"] === "resident") {

                    header("Location: resident/dashboard.php");
                    exit;

                } elseif ($user["role"] === "staff") {

                    header("Location: staff/dashboard.php");
                    exit;

                } elseif ($user["role"] === "admin") {

                    header("Location: admin/dashboard.php");
                    exit;

                }

            }

        } catch (PDOException $e) {

            $error = "Something went wrong. Please try again.";

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

    <title>Login - Barangay Request System</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="min-h-screen bg-gray-50 text-gray-900">

    <main class="flex min-h-screen items-center justify-center px-6 py-12">

        <div class="w-full max-w-md">

            <!-- Header -->
            <div class="mb-8 text-center">

                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                    Barangay Request System
                </h1>

                <p class="mt-2 text-sm text-gray-500">
                    Sign in to access your account
                </p>

            </div>


            <!-- Login Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">

                <div class="mb-6">

                    <h2 class="text-xl font-semibold text-gray-900">
                        Welcome back
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Enter your account details below.
                    </p>

                </div>


                <!-- Error Message -->
                <?php if ($error): ?>

                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                        <p class="text-sm text-red-700">
                            <?= htmlspecialchars($error) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <!-- Form -->
                <form method="POST" class="space-y-5">

                    <!-- Email -->
                    <div>

                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                        >

                    </div>


                    <!-- Password -->
                    <div>

                        <label
                            for="password"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                        >

                    </div>


                    <!-- Login Button -->
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                    >
                        Log In
                    </button>

                </form>


                <!-- Register Link -->
                <div class="mt-6 border-t border-gray-100 pt-6 text-center">

                    <p class="text-sm text-gray-500">

                        Don't have an account?

                        <a
                            href="register.php"
                            class="font-medium text-gray-900 hover:underline"
                        >
                            Create an account
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