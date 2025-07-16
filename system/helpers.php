<?php

////////////////////////////////////////////////////// FOR USER

// Sessions For login
function userLogin($user_id) {
	$_SESSION['LVNUser'] = $user_id;
    $_SESSION['flash_success'] = 'You are now logged in!';
    redirect(PROOT . 'app/');
}

function user_is_logged_in() {
	if (isset($_SESSION['LVNUser']) && $_SESSION['LVNUser'] > 0) {
		return true;
	}
	return false;
}

// Redirect admin if !logged in
function user_login_redirect($url = 'auth/signin') {
	$_SESSION['flash_error'] = 'You must be logged in to access that page.';
	redirect(PROOT . $url);
}

// get user details by id
function get_id_details($dbConnection, $id) {
	$statement = $dbConnection->query("SELECT * FROM levina_users WHERE user_id = '" . $id . "'")->fetch(PDO::FETCH_ASSOC);
	return $statement;
}

 // get list of products
 function count_products() {
	global $dbConnection;
	$statement = $dbConnection->query("SELECT * FROM levina_products WHERE product_trash = 0")->rowCount();
	return $statement;
 }
 
// get list of products
function get_products() {
	global $dbConnection;
	$statement = $dbConnection->query("SELECT * FROM levina_products WHERE product_trash = 0 ORDER BY createdAt DESC")->fetchAll(PDO::FETCH_ASSOC);
	return $statement;
 }




/////////////////////////////////////////////////// FOR ADMIN

// Sessions For login
function adminLogin($user_id) {
	$_SESSION['XPADMIN'] = $user_id;
    $_SESSION['flash_success'] = 'You are now logged in!';
    redirect(PROOT . 'xd192/');
}

function admin_is_logged_in() {
	if (isset($_SESSION['XPADMIN']) && $_SESSION['XPADMIN'] > 0) {
		return true;
	}
	return false;
}

// Redirect admin if !logged in
function admin_login_redirect($url = 'xd192/logout') {
	$_SESSION['flash_error'] = 'You must be logged in to access that page.';
	redirect(PROOT . $url);
}

// GET ALL PRODUCTS WHERE TRASH = 0
function get_all_product($product_trash = '') {
	global $dbConnection;
	$output = '';

	$query = "
		SELECT * FROM levina_products 
		INNER JOIN levina_admin
		ON levina_admin.admin_id = levina_products.product_added_by
		WHERE levina_products.product_trash = :product_trash 
		ORDER BY levina_products.id DESC
	";
	$statement = $dbConnection->prepare($query);
	$statement->execute([':product_trash' => $product_trash]);
	$count_products = $statement->rowCount();
	$result = $statement->fetchAll();

	if ($count_products > 0) {
		$i = 1;
		foreach ($result as $key => $row) {
			$output .= '
				<tr>
					<td>' . $i . '</td>
					<td>' . ucwords($row["product_name"]) . '</td>
					<td>' . money($row["product_price"]) . '</td>
					<td>' . ucwords($row["admin_fullname"]) . '</td>
					<td>' . pretty_date($row["createdAt"]) . '</td>
				';
				if ($product_trash == 0) {
					$output .= '
						<td>
							<a href="' . PROOT . 'admin/products?featured='.(($row['product_featured'] == 0)? "1" : "0") . '&id='.$row["product_id"].'" class="btn btn-sm btn-light">
								<i class="bi bi-' . (($row['product_featured'] == 0) ? "plus" : "dash").'-circle-fill"></i> ' . (($row['product_featured'] == 0)?"" : "Featured product").'
							</a>
						</td>
						<td>
					';
				} else {
					$output .= '
						<td>
						</td>
						<td>
					';
				}
				if ($product_trash == 1) {
					$output .= '
						<a href="'.PROOT.'admin/products?permanent_delete='.$row["product_id"].'&upload_product_image_name='.$row["product_image"].'" class="btn btn-sm btn-outline-primary"><i class="bi bi-trash3"></i></a>&nbsp;
                        <a href="'.PROOT.'admin/products?restore='.$row["product_id"].'" class="btn btn-sm btn-outline-danger"><i class="bi bi-recycle"></i></a>&nbsp;
					';
				} else {
					$output .= '
							<a href="'.PROOT.'admin/products?edit='.$row["product_id"].'" class="btn btn-sm btn-info"><i class="bi bi-pencil"></i></a>
							<a href="'.PROOT.'admin/products?delete='.$row["product_id"].'" class="btn btn-sm btn-secondary"><i class="bi bi-trash3"></i></a>
						';
				}
				$output .= '
						</td>
					</tr>
				';
			$i++;
		}
	} else {
		$output = '
			<tr>
				<td colspan="9">No products found in the database.</h3></td>
			</tr>
		';
	}
	return $output;
}

// get number of clients
function get_number_of_clients() {
	global $dbConnection;
	$statement = $dbConnection->query("SELECT * FROM levina_leads WHERE lead_status = 0")->rowCount();
	return $statement;
}

// get number of users
function get_number_of_users() {
	global $dbConnection;
	$statement = $dbConnection->query("SELECT * FROM levina_users WHERE user_trash = 0")->rowCount();
	return $statement;
}

// get number of products
function get_number_of_products() {
	global $dbConnection;
	$statement = $dbConnection->query("SELECT * FROM levina_products WHERE product_trash = 0")->rowCount();
	return $statement;
}


// get user by id
function get_user($user_id) {
    global $dbConnection;
    $query = "
        SELECT * FROM levina_users 
        WHERE user_id = ?
    ";
    $statement = $dbConnection->prepare($query);
    $statement->execute([$user_id]);
    $user = $statement->fetch(PDO::FETCH_OBJ);
    return $user;
}

// get user by email
function get_user_by_email($user_email) {
    global $dbConnection;
    $query = "
        SELECT * FROM levina_users 
        WHERE user_email = :user_email
    ";
    $statement = $dbConnection->prepare($query);
    $statement->execute([':user_email' => $user_email]);
    $user = $statement->fetch(PDO::FETCH_OBJ);
    return $user;
}