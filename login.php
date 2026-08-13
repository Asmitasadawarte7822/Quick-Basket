
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - Quick Basket</title>

    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        .auth-page {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 15px;
            background: #f1f3f6;
        }

        .auth-box {
            width: 100%;
            max-width: 450px;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .auth-logo h1 {
            color: #2874f0;
            font-size: 36px;
        }

        .auth-logo span {
            color: #ffb400;
        }

        .auth-box h2 {
            text-align: center;
            color: #222;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #333;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 6px;
            outline: none;
            font-size: 15px;
        }

        .form-group input:focus {
            border-color: #2874f0;
        }

        .auth-btn {
            width: 100%;
            padding: 14px;
            border: none;
            background: #2874f0;
            color: #fff;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .auth-btn:hover {
            background: #0052cc;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.error {
            background: #ffe5e5;
            color: #d00000;
        }

        .message.success {
            background: #e5ffe9;
            color: #008f12;
        }

        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .auth-link a {
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
        }

        .back-home {
            text-align: center;
            margin-top: 15px;
        }

        .back-home a {
            color: #555;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="auth-page">

    <div class="auth-box">

        <div class="auth-logo">
            <h1>Quick<span>Basket</span></h1>
        </div>

        <h2>Welcome Back</h2>

       


        <form action="login-process.php" method="POST">

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
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
                    placeholder="Enter your password"
                    required>

            </div>


            <button
                type="submit"
                class="auth-btn">

                Login

            </button>

        </form>


        <div class="auth-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>


        <div class="back-home">

            <a href="index.php">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Home
            </a>

        </div>

    </div>

</div>

</body>
</html>
```
