<?php
    include_once ("../system/DatabaseConnector.php");
    if (admin_is_logged_in()) {
        redirect(PROOT . 'admin/dashboard');
    }

    $error = "";
    $email_val = "";

    // Process login form
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $email_val = $email;
        
        if (empty($email) || empty($password)) {
            $error = "Please enter both your email address and password.";
        } else {
            try {
                $statement = $dbConnection->prepare("SELECT user_id, user_password, user_type, user_status FROM care_users WHERE user_email = ? LIMIT 1");
                $statement->execute([$email]);
                $user = $statement->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['user_password'])) {
                    if ($user['user_type'] !== 'admin') {
                        $error = "Access denied. This account does not have administrator privileges.";
                    } elseif ($user['user_status'] !== 'active') {
                        $error = "Your administrator account is currently inactive.";
                    } else {
                        adminLogin($user['user_id']);
                    }
                } else {
                    $error = "Invalid email or password. Please try again.";
                }
            } catch (PDOException $e) {
                $error = "Authentication service error. Please try again.";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In — Care That Feels Like Home</title>
    <link rel="icon" type="image/jpg" href="<?= PROOT; ?>assets/media/logo/logo.jpg" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 55%, #020617 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            padding: 2.25rem 2rem 1.85rem;
            text-align: center;
            color: #fff;
        }
        .login-logo-img {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 0.85rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        .login-body {
            padding: 2rem 2.25rem 2.25rem;
        }
        .form-control {
            padding: 0.72rem 1rem;
            border-radius: 10px;
            border-color: #cbd5e1;
            font-size: 0.94rem;
        }
        .form-control:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 0.22rem rgba(13, 148, 136, 0.18);
        }
        .input-group-text {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #64748b;
            border-radius: 10px;
        }
        .btn-login {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            padding: 0.8rem 1.25rem;
            border-radius: 10px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-login:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.35);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" alt="Logo" class="login-logo-img">
            <h4 class="fw-bold mb-1">Admin Console</h4>
            <p class="mb-0 small opacity-75">Care That Feels Like Home Corporation</p>
        </div>

        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 rounded-3 small mb-4" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <div><?= e($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="" autocomplete="on">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-secondary">Administrator Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="far fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($email_val); ?>" placeholder="admin@example.com" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold small text-secondary">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword" style="border-color: #cbd5e1;" aria-label="Show password">
                            <i class="far fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-right-to-bracket me-2"></i>Sign In to Console
                    </button>
                </div>

                <div class="text-center pt-2 border-top">
                    <a href="<?= PROOT; ?>" class="text-decoration-none small text-muted">
                        <i class="fas fa-arrow-left me-1"></i>Return to Public Website
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?= $flash_user; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwdInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>
</html>
