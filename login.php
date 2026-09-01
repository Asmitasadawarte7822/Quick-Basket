<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - QuickBasket</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            background: #f1f3f6;
        }

        /* =========================
           MAIN PAGE
        ========================= */

        .login-page {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            background:
                radial-gradient(circle at 10% 20%,
                rgba(40,116,240,0.12),
                transparent 30%),
                radial-gradient(circle at 90% 80%,
                rgba(255,180,0,0.14),
                transparent 30%),
                #f1f3f6;
        }

        /* =========================
           LOGIN CONTAINER
        ========================= */

        .login-container {
            width: 100%;
            max-width: 1050px;
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0,0,0,0.15);
        }

        /* =========================
           LEFT SHOPPING SECTION
        ========================= */

        .shopping-section {
            position: relative;
            padding: 55px;
            color: white;
            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    rgba(40,116,240,0.94),
                    rgba(0,82,204,0.92)
                ),
                url('shopping-bg.jpg');

            background-size: cover;
            background-position: center;
        }

        .shopping-section::after {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            right: -150px;
            bottom: -130px;
        }

        .shopping-content {
            position: relative;
            z-index: 2;
        }

        /* LOGO */

        .logo {
            font-size: 38px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 70px;
        }

        .logo span {
            color: #ffb400;
        }

        .logo i {
            color: #ffb400;
            margin-right: 8px;
        }

        /* HEADING */

        .shopping-content h1 {
            font-size: 45px;
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .shopping-content h1 span {
            color: #ffb400;
        }

        .shopping-content p {
            font-size: 17px;
            line-height: 1.7;
            color: rgba(255,255,255,0.88);
            max-width: 430px;
        }

        /* SHOPPING ILLUSTRATION */

        .shopping-cart {
            margin-top: 55px;
            font-size: 120px;
            color: white;
            filter: drop-shadow(0 15px 15px rgba(0,0,0,0.2));
            animation: floatCart 3s ease-in-out infinite;
        }

        @keyframes floatCart {

            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-12px);
            }

        }

        /* FLOATING BADGES */

        .floating-card {
            position: absolute;
            z-index: 3;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18);
            font-size: 14px;
            font-weight: 600;
        }

        .floating-card i {
            color: #2874f0;
            margin-right: 7px;
        }

        .card-one {
            right: 45px;
            top: 180px;
            animation: floating 4s ease-in-out infinite;
        }

        .card-two {
            right: 70px;
            bottom: 100px;
            animation: floating 4s ease-in-out infinite 1s;
        }

        @keyframes floating {

            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }

        }

        /* =========================
           RIGHT LOGIN SECTION
        ========================= */

        .login-section {
            padding: 55px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            margin-bottom: 35px;
        }

        .login-header h2 {
            color: #222;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #777;
            font-size: 15px;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 9px;
        }

        .input-box {
            position: relative;
        }

        .input-box i.input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }

        .form-group input {
            width: 100%;
            height: 52px;
            padding: 0 48px;
            border: 1px solid #ddd;
            border-radius: 10px;
            outline: none;
            font-size: 15px;
            color: #333;
            transition: 0.3s;
            background: #fafafa;
        }

        .form-group input:focus {
            border-color: #2874f0;
            background: white;
            box-shadow: 0 0 0 4px rgba(40,116,240,0.10);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #777;
            cursor: pointer;
            font-size: 16px;
        }

        .password-toggle:hover {
            color: #2874f0;
        }

        /* =========================
           EXTRA OPTIONS
        ========================= */

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #666;
        }

        .remember input {
            accent-color: #2874f0;
        }

        .forgot {
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot:hover {
            text-decoration: underline;
        }

        /* =========================
           LOGIN BUTTON
        ========================= */

        .login-btn {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(
                135deg,
                #2874f0,
                #0052cc
            );
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(40,116,240,0.25);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(40,116,240,0.35);
        }

        .login-btn i {
            margin-left: 8px;
        }

        /* =========================
           DIVIDER
        ========================= */

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 25px 0;
            color: #aaa;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: "";
            height: 1px;
            background: #e5e5e5;
            flex: 1;
        }

        /* =========================
           REGISTER
        ========================= */

        .register-text {
            text-align: center;
            color: #777;
            font-size: 14px;
        }

        .register-text a {
            color: #2874f0;
            text-decoration: none;
            font-weight: 700;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        /* =========================
           BACK HOME
        ========================= */

        .back-home {
            text-align: center;
            margin-top: 25px;
        }

        .back-home a {
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .back-home a:hover {
            color: #2874f0;
        }

        .back-home i {
            margin-right: 6px;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 850px) {

            .login-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .shopping-section {
                min-height: 350px;
                padding: 40px;
            }

            .logo {
                margin-bottom: 35px;
            }

            .shopping-content h1 {
                font-size: 34px;
            }

            .shopping-cart {
                font-size: 75px;
                margin-top: 25px;
            }

            .floating-card {
                display: none;
            }

            .login-section {
                padding: 40px 30px;
            }
        }

        @media (max-width: 500px) {

            .login-page {
                padding: 0;
            }

            .login-container {
                border-radius: 0;
                min-height: 100vh;
            }

            .shopping-section {
                min-height: 300px;
            }

            .shopping-content h1 {
                font-size: 30px;
            }

            .shopping-content p {
                font-size: 14px;
            }

            .login-section {
                padding: 35px 22px;
            }

            .login-header h2 {
                font-size: 28px;
            }

        }

    </style>

</head>


<body>


<div class="login-page">

    <div class="login-container">


        <!-- ==================================
             LEFT SHOPPING / IMAGE SECTION
        =================================== -->

        <div class="shopping-section">

            <div class="shopping-content">

                <div class="logo">
                    <i class="fa-solid fa-basket-shopping"></i>
                    Quick<span>Basket</span>
                </div>


                <h1>
                    Shop smarter.<br>
                    <span>Live better.</span>
                </h1>


                <p>
                    Discover amazing products, great prices,
                    and a simple shopping experience with
                    QuickBasket.
                </p>


                <div class="shopping-cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>

            </div>


            <!-- Floating Cards -->

            <div class="floating-card card-one">

                <i class="fa-solid fa-truck-fast"></i>

                Fast Delivery

            </div>


            <div class="floating-card card-two">

                <i class="fa-solid fa-tags"></i>

                Best Deals

            </div>

        </div>


        <!-- ==================================
             RIGHT LOGIN SECTION
        =================================== -->

        <div class="login-section">

            <div class="login-box">


                <div class="login-header">

                    <h2>Welcome Back! 👋</h2>

                    <p>
                        Login to continue shopping with QuickBasket.
                    </p>

                </div>


                <!-- LOGIN FORM -->

                <form action="login-process.php" method="POST">


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-envelope input-icon"></i>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Enter your email"
                                autocomplete="email"
                                required>

                        </div>

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-lock input-icon"></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword()">

                                <i class="fa-solid fa-eye"
                                   id="eyeIcon"></i>

                            </button>

                        </div>

                    </div>


                    <!-- OPTIONS -->

                    <div class="form-options">

                        <label class="remember">

                            <input
                                type="checkbox"
                                name="remember">

                            Remember me

                        </label>


                        <a href="#" class="forgot">
                            Forgot Password?
                        </a>

                    </div>


                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="login-btn">

                        Login to Account

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>


                </form>


                <!-- DIVIDER -->

                <div class="divider">
                    OR
                </div>


                <!-- REGISTER -->

                <div class="register-text">

                    Don't have an account?

                    <a href="register.php">
                        Create Account
                    </a>

                </div>


                <!-- HOME -->

                <div class="back-home">

                    <a href="index.php">

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to Home

                    </a>

                </div>


            </div>

        </div>


    </div>

</div>


<!-- ==================================
     PASSWORD SHOW / HIDE
=================================== -->

<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const eyeIcon =
        document.getElementById("eyeIcon");


    if (password.type === "password") {

        password.type = "text";

        eyeIcon.classList.remove("fa-eye");

        eyeIcon.classList.add("fa-eye-slash");

    } else {

        password.type = "password";

        eyeIcon.classList.remove("fa-eye-slash");

        eyeIcon.classList.add("fa-eye");

    }

}

</script>


</body>
</html>
