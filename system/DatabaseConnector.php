<?php 

    require dirname(__DIR__)  . '/bootstrap.php';
	
    $driver = $_ENV['DB_DRIVER'];
    $hostname = $_ENV['DB_HOST'];
    $port = $_ENV['DB_PORT'];
    $database = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];

    try {
        $string = $driver . ":host=" . $hostname . ";charset=utf8mb4;dbname=" . $database;
        $dbConnection = new \PDO(
            $string, $username, $password
        );
    } catch (\PDOException $e) {
        exit($e->getMessage());
    }
    session_start();

    if (isset($_SESSION['CTFLHADMIN'])) {
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
        if ($statement->rowCount() > 0) {
            $admin_data = $statement->fetchAll();
            $admin_data = $admin_data[0];

            $fn = explode(' ', $admin_data['user_fullname']);
            $admin_data['first'] = ucwords($fn[0]);
            $admin_data['last'] = '';
            if (count($fn) > 1) {
                $admin_data['last'] = ucwords($fn[1]);
            }

        } else {
            unset($_SESSION['CTFLHADMIN']);
            redirect(PROOT . 'admin/');
        }
    }

    require_once ("Functions.php");
    require_once ("helpers.php");
    require_once dirname(__DIR__) . "/config.php";

    // Display on Messages on Errors And Success for users
 	$flash_user = '';
 	if (isset($_SESSION['flash_success'])) {
 	 	$flash_user = '
			<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 start-50 translate-middle-x rounded-3" style="z-index: 9999;">
				<div class="p-3">
					<div class="toast show text-bg-primary border-0" id="temporary">
                        <div class="toast-body">
                            ' . $_SESSION['flash_success'] . '
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
                    <div class="toast show text-bg-danger border-0" id="temporary">
                        <div class="toast-body">
                            ' . $_SESSION['flash_error'] . '
                        </div>
                    </div>
                </div>
            </div>
        ';
 	 	unset($_SESSION['flash_error']);
 	}
