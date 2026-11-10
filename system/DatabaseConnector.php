<?php 

    require dirname(__DIR__) . '/bootstrap.php';
    require_once dirname(__DIR__) . "/config.php";
    require_once ("Functions.php");
    require_once ("helpers.php");
    require_once ("Security.php");
	
    $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
    $hostname = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'care_db';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    try {
        $string = $driver . ":host=" . $hostname . ";port=" . $port . ";charset=utf8mb4;dbname=" . $database;
        $dbConnection = new \PDO(
            $string, $username, $password,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    } catch (\PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        http_response_code(500);
        exit("Service temporarily unavailable.");
    }

    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.cookie_httponly', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => PROOT,
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if (!empty($_SESSION['CTFLHADMIN'])) {
        $user_id = $_SESSION['CTFLHADMIN'];
        $data = array($user_id, 'admin');
        $sql = "
            SELECT * FROM care_users 
            WHERE user_id = ? 
            AND user_type = ?
            LIMIT 1
        ";
        $statement = $dbConnection->prepare($sql);
        $statement->execute($data);
        $admin_row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($admin_row) {
            $admin_data = $admin_row;

            $fn = explode(' ', trim($admin_data['user_fullname']));
            $admin_data['first'] = ucwords($fn[0] ?? 'Admin');
            $admin_data['last'] = '';
            if (count($fn) > 1) {
                $admin_data['last'] = ucwords($fn[1]);
            }
        } else {
            unset($_SESSION['CTFLHADMIN']);
            redirect(PROOT . 'admin/');
        }
    }

    // Display Messages on Errors And Success for users
 	$flash_user = '';
 	if (isset($_SESSION['flash_success'])) {
 	 	$flash_user = '
			<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 start-50 translate-middle-x rounded-3" style="z-index: 9999;">
				<div class="p-3">
					<div class="toast show align-items-center text-bg-success border-0 shadow-lg" id="temporary" role="alert">
                        <div class="d-flex">
                            <div class="toast-body fw-medium">
                                <i class="fas fa-check-circle me-2"></i>' . $_SESSION['flash_success'] . '
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
					</div>
				</div>
			</div>
		';
 	 	unset($_SESSION['flash_success']);
 	}

 	if (isset($_SESSION['flash_error'])) {
 	 	$flash_user = '
            <div aria-live="polite" aria-atomic="true" class="position-fixed top-0 start-50 translate-middle-x rounded-3" style="z-index: 9999;">
                <div class="p-3">
                    <div class="toast show align-items-center text-bg-danger border-0 shadow-lg" id="temporary" role="alert">
                        <div class="d-flex">
                            <div class="toast-body fw-medium">
                                <i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['flash_error'] . '
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            </div>
        ';
 	 	unset($_SESSION['flash_error']);
 	}
