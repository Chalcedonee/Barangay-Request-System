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

    <title>Resident Registration</title>

</head>

<body>

    <h1>Resident Registration</h1>

    <?php if ($error): ?>

        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>

    <?php endif; ?>


    <?php if ($message): ?>

        <p style="color: green;">
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <h2>Personal Information</h2>

        <label>
            First Name *
        </label>

        <br>

        <input
            type="text"
            name="first_name"
            required
        >

        <br><br>


        <label>
            Middle Name
        </label>

        <br>

        <input
            type="text"
            name="middle_name"
        >

        <br><br>


        <label>
            Last Name *
        </label>

        <br>

        <input
            type="text"
            name="last_name"
            required
        >

        <br><br>


        <label>
            Suffix
        </label>

        <br>

        <input
            type="text"
            name="suffix"
            placeholder="Jr., Sr., III"
        >

        <br><br>


        <label>
            Birth Date *
        </label>

        <br>

        <input
            type="date"
            name="birth_date"
            required
        >

        <br><br>


        <label>
            Sex *
        </label>

        <br>

        <select name="sex" required>

            <option value="">Select</option>

            <option value="Male">
                Male
            </option>

            <option value="Female">
                Female
            </option>

        </select>

        <br><br>


        <label>
            Address *
        </label>

        <br>

        <textarea
            name="address"
            rows="3"
            required
        ></textarea>

        <br><br>


        <label>
            Contact Number
        </label>

        <br>

        <input
            type="text"
            name="contact_number"
        >

        <br><br>


        <h2>Account Information</h2>

        <label>
            Email *
        </label>

        <br>

        <input
            type="email"
            name="email"
            required
        >

        <br><br>


        <label>
            Password *
        </label>

        <br>

        <input
            type="password"
            name="password"
            required
        >

        <br><br>


        <label>
            Confirm Password *
        </label>

        <br>

        <input
            type="password"
            name="confirm_password"
            required
        >

        <br><br>


        <button type="submit">
            Create Account
        </button>

    </form>

</body>

</html>