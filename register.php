<?php
session_start();

/*
|--------------------------------------------------------------------------
| Display registration errors
|--------------------------------------------------------------------------
*/

$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_data'] ?? [];

unset($_SESSION['register_errors']);
unset($_SESSION['register_data']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account - QuickBasket</title>

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


        /* =========================================
           MAIN PAGE
        ========================================= */

        .register-page {

            min-height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 30px;

            background:
                radial-gradient(
                    circle at 10% 20%,
                    rgba(40,116,240,0.12),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 90% 80%,
                    rgba(255,180,0,0.14),
                    transparent 30%
                ),

                #f1f3f6;
        }


        /* =========================================
           MAIN CONTAINER
        ========================================= */

        .register-container {

            width: 100%;

            max-width: 1050px;

            min-height: 680px;

            display: grid;

            grid-template-columns: 1fr 1fr;

            background: #fff;

            border-radius: 25px;

            overflow: hidden;

            box-shadow:
                0 25px 70px rgba(0,0,0,0.15);
        }


        /* =========================================
           LEFT SHOPPING SECTION
        ========================================= */

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


        .shopping-section::before {

            content: "";

            position: absolute;

            width: 300px;

            height: 300px;

            border-radius: 50%;

            background: rgba(255,255,255,0.08);

            top: -120px;

            left: -120px;
        }


        .shopping-section::after {

            content: "";

            position: absolute;

            width: 400px;

            height: 400px;

            border-radius: 50%;

            background: rgba(255,255,255,0.08);

            right: -180px;

            bottom: -160px;
        }


        .shopping-content {

            position: relative;

            z-index: 2;
        }


        /* =========================================
           LOGO
        ========================================= */

        .logo {

            font-size: 38px;

            font-weight: 800;

            letter-spacing: -1px;

            margin-bottom: 65px;
        }


        .logo i {

            color: #ffb400;

            margin-right: 8px;
        }


        .logo span {

            color: #ffb400;
        }


        /* =========================================
           LEFT HEADING
        ========================================= */

        .shopping-content h1 {

            font-size: 43px;

            line-height: 1.15;

            margin-bottom: 20px;
        }


        .shopping-content h1 span {

            color: #ffb400;
        }


        .shopping-content p {

            font-size: 17px;

            line-height: 1.7;

            max-width: 420px;

            color: rgba(255,255,255,0.88);
        }


        /* =========================================
           SHOPPING CART
        ========================================= */

        .shopping-cart {

            margin-top: 55px;

            font-size: 115px;

            color: white;

            filter:
                drop-shadow(
                    0 15px 15px rgba(0,0,0,0.20)
                );

            animation:
                floatingCart 3s ease-in-out infinite;
        }


        @keyframes floatingCart {

            0%,
            100% {

                transform: translateY(0);

            }

            50% {

                transform: translateY(-12px);

            }
        }


        /* =========================================
           FLOATING CARDS
        ========================================= */

        .floating-card {

            position: absolute;

            z-index: 5;

            background: rgba(255,255,255,0.96);

            color: #333;

            padding: 13px 18px;

            border-radius: 12px;

            box-shadow:
                0 10px 30px rgba(0,0,0,0.18);

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

            animation:
                floatingCard 4s ease-in-out infinite;
        }


        .card-two {

            right: 65px;

            bottom: 110px;

            animation:
                floatingCard 4s ease-in-out infinite 1s;
        }


        @keyframes floatingCard {

            0%,
            100% {

                transform: translateY(0);

            }

            50% {

                transform: translateY(-10px);

            }
        }


        /* =========================================
           RIGHT REGISTER SECTION
        ========================================= */

        .register-section {

            padding: 50px;

            display: flex;

            justify-content: center;

            align-items: center;

            background: #fff;
        }


        .register-box {

            width: 100%;

            max-width: 400px;
        }


        /* =========================================
           HEADER
        ========================================= */

        .register-header {

            margin-bottom: 28px;
        }


        .register-header h2 {

            color: #222;

            font-size: 31px;

            margin-bottom: 8px;
        }


        .register-header p {

            color: #777;

            font-size: 14px;

            line-height: 1.5;
        }


        /* =========================================
           ERROR MESSAGE
        ========================================= */

        .error-message {

            background: #ffe7e7;

            color: #c40000;

            border-left: 4px solid #e00000;

            padding: 12px 14px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 13px;
        }


        .error-message div {

            margin: 3px 0;
        }


        /* =========================================
           FORM
        ========================================= */

        .form-group {

            margin-bottom: 17px;
        }


        .form-group label {

            display: block;

            color: #333;

            font-weight: 600;

            font-size: 14px;

            margin-bottom: 8px;
        }


        .input-box {

            position: relative;
        }


        .input-box .input-icon {

            position: absolute;

            left: 16px;

            top: 50%;

            transform: translateY(-50%);

            color: #999;

            font-size: 15px;

            pointer-events: none;
        }


        .form-group input {

            width: 100%;

            height: 50px;

            padding:
                0 45px;

            border: 1px solid #ddd;

            border-radius: 10px;

            outline: none;

            font-size: 14px;

            color: #333;

            background: #fafafa;

            transition: 0.3s;
        }


        .form-group input:focus {

            border-color: #2874f0;

            background: #fff;

            box-shadow:
                0 0 0 4px
                rgba(40,116,240,0.10);
        }


        /* =========================================
           PASSWORD BUTTON
        ========================================= */

        .password-toggle {

            position: absolute;

            right: 15px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #777;

            cursor: pointer;

            font-size: 15px;
        }


        .password-toggle:hover {

            color: #2874f0;
        }


        /* =========================================
           PASSWORD STRENGTH
        ========================================= */

        .password-strength {

            height: 4px;

            width: 100%;

            background: #eee;

            border-radius: 10px;

            margin-top: 7px;

            overflow: hidden;

            display: none;
        }


        .password-strength-bar {

            height: 100%;

            width: 0%;

            border-radius: 10px;

            transition: 0.3s;
        }


        .password-help {

            font-size: 11px;

            color: #888;

            margin-top: 5px;
        }


        /* =========================================
           REGISTER BUTTON
        ========================================= */

        .register-btn {

            width: 100%;

            height: 53px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #2874f0,
                    #0052cc
                );

            color: white;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.3s;

            box-shadow:
                0 8px 20px
                rgba(40,116,240,0.25);

            margin-top: 5px;
        }


        .register-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(40,116,240,0.35);
        }


        .register-btn i {

            margin-left: 8px;
        }


        /* =========================================
           DIVIDER
        ========================================= */

        .divider {

            display: flex;

            align-items: center;

            gap: 12px;

            margin: 22px 0;

            color: #aaa;

            font-size: 12px;
        }


        .divider::before,
        .divider::after {

            content: "";

            height: 1px;

            background: #e5e5e5;

            flex: 1;
        }


        /* =========================================
           LOGIN LINK
        ========================================= */

        .login-text {

            text-align: center;

            color: #777;

            font-size: 14px;
        }


        .login-text a {

            color: #2874f0;

            text-decoration: none;

            font-weight: 700;
        }


        .login-text a:hover {

            text-decoration: underline;
        }


        /* =========================================
           HOME
        ========================================= */

        .back-home {

            text-align: center;

            margin-top: 20px;
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


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 850px) {

            .register-container {

                grid-template-columns: 1fr;

                max-width: 500px;
            }


            .shopping-section {

                min-height: 340px;

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


            .register-section {

                padding: 40px 30px;
            }
        }


        @media (max-width: 500px) {

            .register-page {

                padding: 0;
            }


            .register-container {

                border-radius: 0;

                min-height: 100vh;
            }


            .shopping-section {

                min-height: 290px;
            }


            .shopping-content h1 {

                font-size: 29px;
            }


            .shopping-content p {

                font-size: 14px;
            }


            .register-section {

                padding: 35px 22px;
            }


            .register-header h2 {

                font-size: 27px;
            }
        }

    </style>

</head>


<body>


<div class="register-page">


    <div class="register-container">


        <!-- =====================================
             LEFT SHOPPING SECTION
        ====================================== -->

        <div class="shopping-section">


            <div class="shopping-content">


                <div class="logo">

                    <i class="fa-solid fa-basket-shopping"></i>

                    Quick<span>Basket</span>

                </div>


                <h1>

                    Start your
                    <br>

                    <span>shopping journey.</span>

                </h1>


                <p>

                    Create your QuickBasket account and
                    enjoy easy shopping, amazing deals,
                    and fast delivery right at your doorstep.

                </p>


                <div class="shopping-cart">

                    <i class="fa-solid fa-cart-shopping"></i>

                </div>


            </div>


            <!-- Floating Card 1 -->

            <div class="floating-card card-one">

                <i class="fa-solid fa-gift"></i>

                Exclusive Offers

            </div>


            <!-- Floating Card 2 -->

            <div class="floating-card card-two">

                <i class="fa-solid fa-truck-fast"></i>

                Fast Delivery

            </div>


        </div>



        <!-- =====================================
             RIGHT REGISTER SECTION
        ====================================== -->

        <div class="register-section">


            <div class="register-box">


                <!-- HEADER -->

                <div class="register-header">

                    <h2>
                        Create Account 🛒
                    </h2>

                    <p>
                        Join QuickBasket and start shopping today.
                    </p>

                </div>



                <!-- ERROR MESSAGE -->

                <?php if (!empty($errors)): ?>

                    <div class="error-message">

                        <?php foreach ($errors as $error): ?>

                            <div>
                                <i class="fa-solid fa-circle-exclamation"></i>

                                <?php echo htmlspecialchars($error); ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>



                <!-- REGISTER FORM -->

                <form
                    action="register-process.php"
                    method="POST"
                    id="registerForm">


                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label for="name">
                            Full Name
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-user input-icon"></i>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Enter your full name"
                                value="<?php
                                    echo htmlspecialchars(
                                        $old['name'] ?? ''
                                    );
                                ?>"
                                autocomplete="name"
                                required>

                        </div>

                    </div>



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
                                value="<?php
                                    echo htmlspecialchars(
                                        $old['email'] ?? ''
                                    );
                                ?>"
                                autocomplete="email"
                                required>

                        </div>

                    </div>



                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="phone">
                            Mobile Number
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-phone input-icon"></i>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="Enter your mobile number"
                                value="<?php
                                    echo htmlspecialchars(
                                        $old['phone'] ?? ''
                                    );
                                ?>"
                                autocomplete="tel"
                                pattern="[0-9]{10}"
                                maxlength="10"
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
                                placeholder="Create a password"
                                minlength="6"
                                autocomplete="new-password"
                                required>

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword(
                                    'password',
                                    'passwordEye'
                                )">

                                <i
                                    class="fa-solid fa-eye"
                                    id="passwordEye">
                                </i>

                            </button>

                        </div>


                        <div
                            class="password-strength"
                            id="strengthContainer">

                            <div
                                class="password-strength-bar"
                                id="strengthBar">
                            </div>

                        </div>


                        <div class="password-help">

                            Minimum 6 characters

                        </div>

                    </div>



                    <!-- CONFIRM PASSWORD -->

                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <div class="input-box">

                            <i class="fa-solid fa-shield-halved input-icon"></i>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Confirm your password"
                                minlength="6"
                                autocomplete="new-password"
                                required>

                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword(
                                    'confirm_password',
                                    'confirmEye'
                                )">

                                <i
                                    class="fa-solid fa-eye"
                                    id="confirmEye">
                                </i>

                            </button>

                        </div>

                    </div>



                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="register-btn">

                        Create My Account

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>


                </form>



                <!-- DIVIDER -->

                <div class="divider">

                    OR

                </div>



                <!-- LOGIN -->

                <div class="login-text">

                    Already have an account?

                    <a href="login.php">

                        Login

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



<!-- =====================================
     JAVASCRIPT
====================================== -->

<script>


/* =====================================
   SHOW / HIDE PASSWORD
===================================== */

function togglePassword(inputId, iconId) {

    const input =
        document.getElementById(inputId);

    const icon =
        document.getElementById(iconId);


    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("fa-eye");

        icon.classList.add("fa-eye-slash");

    }

    else {

        input.type = "password";

        icon.classList.remove("fa-eye-slash");

        icon.classList.add("fa-eye");

    }

}



/* =====================================
   PASSWORD STRENGTH
===================================== */

const password =
    document.getElementById("password");

const strengthContainer =
    document.getElementById("strengthContainer");

const strengthBar =
    document.getElementById("strengthBar");


password.addEventListener("input", function () {

    const value = password.value;

    strengthContainer.style.display = "block";


    let strength = 0;


    if (value.length >= 6) {

        strength += 25;

    }


    if (value.length >= 8) {

        strength += 25;

    }


    if (/[A-Z]/.test(value)) {

        strength += 15;

    }


    if (/[0-9]/.test(value)) {

        strength += 15;

    }


    if (/[^A-Za-z0-9]/.test(value)) {

        strength += 20;

    }


    strengthBar.style.width =
        Math.min(strength, 100) + "%";


    if (strength < 40) {

        strengthBar.style.background =
            "#e53935";

    }

    else if (strength < 70) {

        strengthBar.style.background =
            "#ffb400";

    }

    else {

        strengthBar.style.background =
            "#20b15a";

    }

});



/* =====================================
   CONFIRM PASSWORD CHECK
===================================== */

document
    .getElementById("registerForm")
    .addEventListener("submit", function (event) {

        const password =
            document.getElementById("password").value;

        const confirmPassword =
            document.getElementById("confirm_password").value;


        if (password !== confirmPassword) {

            event.preventDefault();

            alert("Passwords do not match.");

        }

    });


</script>


</body>

</html>
