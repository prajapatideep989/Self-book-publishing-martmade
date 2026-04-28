<?php
session_start();

$error = "";
$success = "";

/* ================= VERIFY OTP ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_otp'])) {

    $otp = trim($_POST['otp']);

    if (empty($otp)) {
        $error = "Please enter OTP.";
    } elseif (!isset($_SESSION['otp'], $_SESSION['otp_expires'])) {
        $error = "OTP not generated.";
    } elseif (time() > $_SESSION['otp_expires']) {
        $error = "OTP expired. Please resend.";
    } elseif ($otp != $_SESSION['otp']) {
        $error = "Invalid OTP.";
    } else {
        $_SESSION['otp_verified'] = true;

        $_SESSION['reg_data'] = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'house_no' => $_POST['house_no'],
            'apartment_society' => $_POST['apartment_society'],
            'street' => $_POST['street'],
            'area' => $_POST['area'],
            'landmark' => $_POST['landmark'],
            'pincode' => $_POST['pincode'],
            'city' => $_POST['city'],
            'state' => $_POST['state']
        ];

        $success = "Email verified successfully ✔";
    }
}

/* ================= REGISTER USER ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {

    if (!($_SESSION['otp_verified'] ?? false)) {
        $error = "Please verify email first.";
    } else {

        $data = $_SESSION['reg_data'];

        $name = trim($data['name']);
        $email = trim($data['email']);
        $phone = trim($data['phone']);
        $house_no = trim($data['house_no']);
        $apartment_society = trim($data['apartment_society']);
        $street = trim($data['street']);
        $area = trim($data['area']);
        $landmark = trim($data['landmark']);
        $pincode = trim($data['pincode']);
        $city = trim($data['city']);
        $state = trim($data['state']);
        $password = $_POST['password'];

        if (empty($password)) {
            $error = "Password required.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif (!preg_match('/^\d{6}$/', $pincode)) {
            $error = "Pincode must be exactly 6 digits.";
        } else {

            $conn = new mysqli("localhost", "root", "", "books_db");
            if ($conn->connect_error) {
                die("Database error");
            }

            $check = $conn->prepare("SELECT id FROM users WHERE email=?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO users (name,email,phone,house_no,apartment_society,street,area,landmark,pincode,city,state,password) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
                );
                $stmt->bind_param("ssssssssssss", $name, $email, $phone, $house_no, $apartment_society, $street, $area, $landmark, $pincode, $city, $state, $hash);

                if ($stmt->execute()) {
                    session_unset();
                    session_destroy();
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $error = "Registration failed.";
                }
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register | Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef2ff, #f8fafc);
            min-height: 100vh;
        }

        .page-wrapper {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 120px 20px 60px;
        }

        .card {
            background: #fff;
            width: 100%;
            max-width: 480px;
            padding: 2.8rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 5px;
            color: #1e293b
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 2rem
        }

        .field {
            position: relative;
            margin-bottom: 1.2rem
        }

        .field input,
        .field textarea {
            width: 100%;
            padding: .85rem .85rem .85rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f9fafb;
            transition: .3s;
        }

        .field input:focus,
        .field textarea:focus {
            outline: none;
            border-color: #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .1);
        }

        textarea {
            resize: none;
            height: 90px
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border: none;
            border-radius: 16px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(79, 70, 229, .35)
        }

        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            box-shadow: none
        }

        .otp-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: .75rem;
            cursor: pointer;
        }

        .alert {
            padding: .8rem;
            border-radius: 12px;
            margin-bottom: 1.2rem;
            text-align: center;
            font-size: .9rem;
        }

        .alert.error {
            background: #fee2e2;
            color: #b91c1c
        }

        .alert.success {
            background: #dcfce7;
            color: #166534
        }

        .footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: .9rem;
            color: #6b7280
        }

        .footer a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500
        }

        .footer a:hover {
            text-decoration: underline
        }

        #otp-box {
            display: none
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="page-wrapper">
        <div class="card">

            <h1>Create Account</h1>
            <p class="subtitle">Join Alpha Book</p>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="field">
                    <input type="text" name="name" placeholder="Name"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['name'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <input type="email" name="email" id="email" placeholder="Email"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['email'] ?? '') ?>" required>
                    <button type="button" class="otp-btn" id="sendOtp">Send OTP</button>
                </div>

                <div class="field" id="otp-box">
                    <input type="text" name="otp" placeholder="Enter OTP">
                    <button type="submit" name="verify_otp" class="otp-btn">Verify</button>
                </div>

                <div class="field">
                    <input type="text" name="phone" placeholder="Phone"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['phone'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <input type="text" name="house_no" placeholder="House/Flat No."
                        value="<?= htmlspecialchars($_SESSION['reg_data']['house_no'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <input type="text" name="apartment_society" placeholder="Apartment / Society Name"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['apartment_society'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <input type="text" name="street" placeholder="Street"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['street'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <input type="text" name="area" placeholder="Area"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['area'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <input type="text" name="landmark" placeholder="Landmark"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['landmark'] ?? '') ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="field" style="position: relative;">
                        <input type="text" name="pincode" id="reg-pincode" placeholder="Pincode"
                            value="<?= htmlspecialchars($_SESSION['reg_data']['pincode'] ?? '') ?>" pattern="\d{6}" maxlength="6" title="Please enter a 6 digit pincode" required>
                        <small id="reg-pincode-error" style="color: #e63946; display: none; font-size: 0.75rem; position: absolute; bottom: -18px; left: 0;">Must be exactly 6 digits</small>
                    </div>
                    <div class="field">
                        <input type="text" name="city" placeholder="City"
                            value="<?= htmlspecialchars($_SESSION['reg_data']['city'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="field">
                    <input type="text" name="state" placeholder="State"
                        value="<?= htmlspecialchars($_SESSION['reg_data']['state'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <input type="password" name="password" placeholder="Password (min 6 chars)">
                </div>

                <button class="btn" name="register" <?= empty($_SESSION['otp_verified']) ? 'disabled' : '' ?>>
                    Create Account
                </button>

            </form>

            <div class="footer">
                Already registered? <a href="login.php">Login</a>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('sendOtp').onclick = function () {
            let email = document.getElementById('email').value;
            if (!email) { alert("Enter email first"); return; }
            fetch('send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
                .then(res => res.text())
                .then(msg => {
                    alert(msg);
                    document.getElementById('otp-box').style.display = 'block';
                });
        };

        // Real-time Pincode Validation
        document.getElementById('reg-pincode').addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            let errorMsg = document.getElementById('reg-pincode-error');
            if (this.value.length > 0 && this.value.length < 6) {
                errorMsg.style.display = 'block';
                this.style.borderColor = '#e63946';
            } else {
                errorMsg.style.display = 'none';
                this.style.borderColor = '';
            }
        });
    </script>

</body>

</html>