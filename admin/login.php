<?php

session_start();

require_once "../config.php";

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$email = "";

// Create CSRF token
if (empty($_SESSION["admin_login_csrf"])) {
    $_SESSION["admin_login_csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (
        empty($csrfToken) ||
        !hash_equals($_SESSION["admin_login_csrf"], $csrfToken)
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        // Validate fields
        if ($email === "" || $password === "") {

            $error = "Please enter email and password.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            /*
             * Fetch admin by email.
             *
             * admins table:
             * id
             * name
             * email
             * password_hash
             * role
             * created_at
             * updated_at
             */

            $sql = "
                SELECT
                    id,
                    name,
                    email,
                    password_hash,
                    role
                FROM admins
                WHERE email = ?
                LIMIT 1
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                $error = "Database error. Please try again.";

            } else {

                $stmt->bind_param("s", $email);

                if ($stmt->execute()) {

                    $result = $stmt->get_result();

                    if ($result && $result->num_rows === 1) {

                        $admin = $result->fetch_assoc();

                        /*
                         * Verify submitted password against
                         * bcrypt/Argon2 password hash.
                         */
                        if (password_verify($password, $admin["password_hash"])) {

                            /*
                             * Regenerate session ID after
                             * successful authentication.
                             */
                            session_regenerate_id(true);

                            // Store admin information in session
                            $_SESSION["admin_id"] = (int) $admin["id"];
                            $_SESSION["admin_name"] = $admin["name"];
                            $_SESSION["admin_email"] = $admin["email"];
                            $_SESSION["admin_role"] = $admin["role"];

                            // Optional: store login time
                            $_SESSION["admin_login_time"] = time();

                            // Redirect to dashboard
                            header("Location: dashboard.php");
                            exit;

                        } else {

                            $error = "Invalid email or password.";
                        }

                    } else {

                        $error = "Invalid email or password.";
                    }

                } else {

                    $error = "Something went wrong. Please try again.";
                }

                $stmt->close();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login - Quick Basket</title>

    <link rel="stylesheet" href="admin.css">

    <style>

        .error-message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #ffe5e5;
            color: #d00000;
            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2874f0;
        }

        .form-group input::placeholder {
            color: #999;
        }

    </style>

</head>

<body class="admin-login-page">

    <div class="admin-login-box">

        <h1>
            Quick<span>Basket</span>
        </h1>

        <h2>Admin Login</h2>

        <?php if (!empty($error)): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <!-- CSRF protection -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars(
                    $_SESSION["admin_login_csrf"],
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>">

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Admin email"
                    value="<?php echo htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    autocomplete="username"
                    required>

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Admin password"
                    autocomplete="current-password"
                    required>

            </div>

            <button type="submit">
                Login
            </button>

        </form>

        <a href="../index.php" class="back-link">
            Back to Website
        </a>

    </div>

</body>

</html>