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

</head>

<body>

    <h1>Barangay Request System</h1>

    <h2>Login</h2>

    <?php if ($error): ?>

        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <label for="email">
            Email
        </label>

        <br>

        <input
            type="email"
            id="email"
            name="email"
            required
        >

        <br><br>


        <label for="password">
            Password
        </label>

        <br>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <br><br>


        <button type="submit">
            Log In
        </button>

    </form>


    <p>
        Don't have an account?

        <a href="register.php">
            Create an account
        </a>
    </p>

</body>

</html>