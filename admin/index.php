<?php
    // echo password_hash('Alfa@ctflh', PASSWORD_BCRYPT);die;
    // Include database connection
    include_once ("../system/DatabaseConnector.php");
    if (admin_is_logged_in()) {
        redirect(PROOT . 'admin/dashboard');
    }

    $error = "";

    // Process login form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Validate input
        if (empty($email) || empty($password)) {
            $error = "Username and password are required";
        } else {
            try {
                // Check if the user exists
                $statement = $dbConnection->prepare("SELECT user_id, user_password FROM care_users WHERE user_email = ?");
                $statement->execute([$email]);
                $user = $statement->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['user_password'])) {
                    // Login successful
                    if (empty($error) || $error == "") {
                        $admin_id = $user['user_id'];
                        adminLogin($admin_id);
                    }
                } else {
                    $error = "Invalid email or password.";
                }

            } catch (PDOException $e) {
			    $error = "Login failed: " . $e->getMessage();
            }
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h2>Care that Feels like Home - Admin</h2>
            <p class="text-muted">Login to access dashboard</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>