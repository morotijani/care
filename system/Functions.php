<?php 

    // Import PHPMailer classes into the global namespace
    // These must be at the top of your script, not inside a function
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

	function dnd($data) {
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	    die;
	}

	// read from or write to database
	function run($conn, $query, $var = [], $res = null) {
		$statement = $conn->prepare($query);
		if ($statement) {
			$check = $statement->execute($var);
			if ($check) {
				$response = $check;
				$data = $statement->fetchAll(PDO::FETCH_OBJ); // fetch objects
				if (is_array($data) && count($data) > 0) {
					$response = $data;

					if ($res == 'count') {
						$response = $statement->rowCount();
					} else if ($res == 'lastinsertid') {
						$response = $conn->lastInserId();
					}

				} else {
					return false;
				}			
			}
			return $response;
		}

		return false;
	}

	// Make Date Readable
	function pretty_date($date) {
		return date("M d, Y h:i A", strtotime($date));
	}

	// Make Date Readable
	function pretty_date_notime($date){
		return date("M d, Y", strtotime($date));
	}

	// Display money in a readable way
	function money($number) {
		$output = '0.00';
		if ($number != NULL || $number != '') 
			$output = number_format($number, 2);
	
		return '₵' . $output;
	}

	// Check For Incorrect Input Of Data
	function sanitize($dirty) {
	    $clean = htmlentities($dirty, ENT_QUOTES, "UTF-8");
	    return trim($clean);
	}

	function cleanPost($post) {
	    $clean = [];
	    foreach ($post as $key => $value) {
	      	if (is_array($value)) {
	        	$ary = [];
	        	foreach($value as $val) {
	          		$ary[] = sanitize($val);
	        	}
	        	$clean[$key] = $ary;
	      	} else {
	        	$clean[$key] = sanitize($value);
	      	}
	    }
	    return $clean;
	}

	//
	function php_url_slug($string) {
	 	$slug = preg_replace('/[^a-z0-9-]+/', '-', trim(strtolower($string)));
	 	return $slug;
	}

	// REDIRECT PAGE
	function redirect($url) {
		if(!headers_sent()) {
			header("Location: {$url}");
		} else {
			echo '<script>window.location.href="' . $url . '"</script>';
		}
		exit;
	}

	function issetElse($array, $key, $default = "") {
	    if(!isset($array[$key]) || empty($array[$key])) {
	      return $default;
	    }
	    return $array[$key];
	}


	// Email VALIDATION
	function isEmail($email) {
		return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) ? FALSE : TRUE;
	}

	function maskEmail($email) {
		$parts = explode("@", $email);
		$name = $parts[0];
		$domain = $parts[1] ?? '';

		// Show first 3 characters of the name, then mask the rest
		$nameMasked = substr($name, 0, 3) . str_repeat('*', max(0, strlen($name) - 3));

		return $nameMasked . '@' . $domain;
	}

	// GET USER IP ADDRESS
	function getIPAddress() {  
	    //whether ip is from the share internet  
	    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {  
	        $ip = $_SERVER['HTTP_CLIENT_IP'];  
	    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {  // whether ip is from the proxy
	       $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];  
	     } else {  // whether ip is from the remote address 
	        $ip = $_SERVER['REMOTE_ADDR'];  
	    }  
	    return $ip;  
	}

	// PRINT OUT RANDAM NUMBERS
	function digit_random($digits) {
	  	return rand(pow(10, $digits - 1) - 1, pow(10, $digits) - 1);
	}

	function js_alert($msg) {
		return '<script>alert("' . $msg . '");</script>';
	}

	// SEND SMS
	function send_sms($msg, $phone) {
		$sender = "Namibra";
		$msg = urlencode($msg);
	
		$curl = curl_init();
		curl_setopt_array($curl, 
			array(
				CURLOPT_URL => "https://sms.arkesel.com/sms/api?action=send-sms&api_key=".ARKESEL_SMS_API_KEY."&to={$phone}&from={$sender}&sms={$msg}",
				# When sending SMS to Nigerian contacts, the use_case field is required |
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET', 
			)
		);
		$json_data = curl_exec($curl);
		curl_close($curl);
		$response_data = json_decode($json_data);
		if ($response_data->code == 'ok') {
			return 1;
		}
		
		return 0;
	}

	// Send EMAIL
	function send_email($name, $to, $subject, $body) {
		$mail = new PHPMailer(true);
		try {
	        $fn = $name;
	        $to = $to;
	        $from = MAIL_EMAIL;
	        $from_name = 'Levina, Namibra.io 🤞';
	        $subject = $subject;
	        $body = $body;

	        //Create an instance; passing `true` enables exceptions
	        $mail = new PHPMailer(true);

	        $mail->IsSMTP();
	        $mail->SMTPAuth = true;

	        $mail->SMTPSecure = 'ssl'; 
	        $mail->Host = 'smtp.garypie.com';
	        $mail->Port = 465;  
	        $mail->Username = $from;
	        $mail->Password = MAIL_KEY; 

	        $mail->IsHTML(true);
	        $mail->WordWrap = 50;
	        $mail->From = $from;
	        $mail->FromName = $from_name;
	        $mail->Sender = $from;
	        $mail->AddReplyTo($from, $from_name);
	        $mail->Subject = $subject;
	        $mail->Body = $body;
	        $mail->AddAddress($to);
	        $mail->send();
	        return true;
	    } catch (Exception $e) {
	    	//return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
	    	return false;
	        //$message = "Please check your internet connection well...";
	    }
	}

	// send mail to server
	function send_mail_to_server($subject, $body) {
		$to_server = MAIL_EMAIL;

		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		$headers .= "From:" . $to_server;
					
		mail($to_server, $subject, $body, $headers);
	}

	function sanitizeGhanaPhone($input) {
		// Remove any non-numeric characters
		$digits = preg_replace('/\D/', '', $input);

		// If starts with '+233', remove '+'
		if (strpos($input, '+233') === 0) {
			$digits = substr($digits, 1); // remove the initial +
		}

		// If starts with '0' and has 10 digits
		if (preg_match('/^0\d{9}$/', $digits)) {
			$digits = '233' . substr($digits, 1); // remove leading 0 and add 233
		}
		// If starts with '233' and has 12 digits (233 + 9 digits)
		elseif (preg_match('/^233\d{9}$/', $digits)) {
			// Already formatted, do nothing
		} else {
			return false; // Invalid format
		}

		return $digits;
	}

	// Generate UUID VERSION 4
	function guidv4($data = null) {
	    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
	    $data = $data ?? random_bytes(16);
	    assert(strlen($data) == 16);

	    // Set version to 0100
	    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	    // Set bits 6-7 to 10
	    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

	    // Output the 36 character UUID.
	    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	/// find user agent
	function getBrowserAndOs() {

	    $user_agent = $_SERVER['HTTP_USER_AGENT'];
	    $browser = "N/A";

	    $browsers = array(
	        '/msie/i' => 'Internet explorer',
	        '/firefox/i' => 'Firefox',
	        '/safari/i' => 'Safari',
	        '/chrome/i' => 'Chrome',
	        '/edge/i' => 'Edge',
	        '/opera/i' => 'Opera',
	        '/mobile/i' => 'Mobile browser'
	    );

	    foreach ($browsers as $regex => $value) {
	        if (preg_match($regex, $user_agent)) { $browser = $value; }
	    }

	    $visitor_agent_division = explode("(", $user_agent)[1];
	    list($os, $division_two) = explode(")", $visitor_agent_division);

	    $refferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

	    $visitor_broswer_os = array(
	        'browser' => $browser,
	        'operatingSystem' => $os,
	        'refferer' => $refferer
	    );

	   	$output = json_encode($visitor_broswer_os);

	    return $output;
	}

	function convertNumber($number) {
	    list($integer, $fraction) = explode(".", (string) $number);

	    $output = "";

	    if ($integer[0] == "-") {
	        $output = "negative ";
	        $integer    = ltrim($integer, "-");
	    } else if ($integer[0] == "+") {
	        $output = "positive ";
	        $integer    = ltrim($integer, "+");
	    }

	    if ($integer[0] == "0") {
	        $output .= "zero";
	    } else {
	        $integer = str_pad($integer, 36, "0", STR_PAD_LEFT);
	        $group   = rtrim(chunk_split($integer, 3, " "), " ");
	        $groups  = explode(" ", $group);

	        $groups2 = array();
	        foreach ($groups as $g) {
	            $groups2[] = convertThreeDigit($g[0], $g[1], $g[2]);
	        }

	        for ($z = 0; $z < count($groups2); $z++) {
	            if ($groups2[$z] != "") {
	                $output .= $groups2[$z] . convertGroup(11 - $z) . (
	                        $z < 11
	                        && !array_search('', array_slice($groups2, $z + 1, -1))
	                        && $groups2[11] != ''
	                        && $groups[11][0] == '0'
	                            ? " and "
	                            : ", "
	                    );
	            }
	        }

	        $output = rtrim($output, ", ");
	    }

	    if ($fraction > 0) {
	        $output .= " point";
	        for ($i = 0; $i < strlen($fraction); $i++) {
	            $output .= " " . convertDigit($fraction[$i]);
	        }
	    }

	    return $output;
	}

	function convertGroup($index) {
	    switch ($index) {
	        case 11:
	            return " decillion";
	        case 10:
	            return " nonillion";
	        case 9:
	            return " octillion";
	        case 8:
	            return " septillion";
	        case 7:
	            return " sextillion";
	        case 6:
	            return " quintrillion";
	        case 5:
	            return " quadrillion";
	        case 4:
	            return " trillion";
	        case 3:
	            return " billion";
	        case 2:
	            return " million";
	        case 1:
	            return " thousand";
	        case 0:
	            return "";
	    }
	}

	function convertThreeDigit($digit1, $digit2, $digit3) {
	    $buffer = "";

	    if ($digit1 == "0" && $digit2 == "0" && $digit3 == "0") {
	        return "";
	    }

	    if ($digit1 != "0") {
	        $buffer .= convertDigit($digit1) . " hundred";
	        if ($digit2 != "0" || $digit3 != "0")
	        {
	            $buffer .= " and ";
	        }
	    }

	    if ($digit2 != "0") {
	        $buffer .= convertTwoDigit($digit2, $digit3);
	    }
	    else if ($digit3 != "0") {
	        $buffer .= convertDigit($digit3);
	    }

	    return $buffer;
	}

	function convertTwoDigit($digit1, $digit2) {
	    if ($digit2 == "0") {
	        switch ($digit1) {
	            case "1":
	                return "ten";
	            case "2":
	                return "twenty";
	            case "3":
	                return "thirty";
	            case "4":
	                return "forty";
	            case "5":
	                return "fifty";
	            case "6":
	                return "sixty";
	            case "7":
	                return "seventy";
	            case "8":
	                return "eighty";
	            case "9":
	                return "ninety";
	        }
	    } else if ($digit1 == "1") {
	        switch ($digit2) {
	            case "1":
	                return "eleven";
	            case "2":
	                return "twelve";
	            case "3":
	                return "thirteen";
	            case "4":
	                return "fourteen";
	            case "5":
	                return "fifteen";
	            case "6":
	                return "sixteen";
	            case "7":
	                return "seventeen";
	            case "8":
	                return "eighteen";
	            case "9":
	                return "nineteen";
	        }
	    } else {
	        $temp = convertDigit($digit2);
	        switch ($digit1) {
	            case "2":
	                return "twenty-$temp";
	            case "3":
	                return "thirty-$temp";
	            case "4":
	                return "forty-$temp";
	            case "5":
	                return "fifty-$temp";
	            case "6":
	                return "sixty-$temp";
	            case "7":
	                return "seventy-$temp";
	            case "8":
	                return "eighty-$temp";
	            case "9":
	                return "ninety-$temp";
	        }
	    }
	}

	function convertDigit($digit) {
	    switch ($digit) {
	        case "0":
	            return "zero";
	        case "1":
	            return "one";
	        case "2":
	            return "two";
	        case "3":
	            return "three";
	        case "4":
	            return "four";
	        case "5":
	            return "five";
	        case "6":
	            return "six";
	        case "7":
	            return "seven";
	        case "8":
	            return "eight";
	        case "9":
	            return "nine";
	    }
	}

	// go back
	function goBack() {
	    $previous = "javascript:history.go(-1)";
	    if (isset($_SERVER['HTTP_REFERER'])) {
	        $previous = $_SERVER['HTTP_REFERER'];
	    }
	    return $previous;
	}

	// get file size
	function getFilesize($file) {
	    if (!file_exists($file)) return "File does not exist";

	    $filesize = filesize($file);

	    if ($filesize > 1024) {
	        $filesize = ($filesize / 1024);

	        if ($filesize > 1024) {
	            $filesize = ($filesize / 1024);

	            if ($filesize > 1024) {
	                $filesize = ($filesize / 1024);
	                $filesize = round($filesize, 1);
	                return $filesize." GB";
	            } else {
	                $filesize = round($filesize, 1);
	                return $filesize." MB";
	            }
	        } else {
	            $filesize = round($filesize, 1);
	            return $filesize." KB";
	        }
	    } else {
	        $filesize = round($filesize, 1);
	        return $filesize." Bytes";
	    }
	}

	function yearDropdown($startYear, $endYear, $class) {           
	    //echo each year as an option     
	    for ($i = $startYear; $i <= $endYear; $i++) { 
	    	echo "<option value=" . $i . ">" . $i . "</option>n";     
	    }
	}



	////////////////////////////////////////////////////////////////////////////
	function timeAgo($timestamp) {
		$time_ago = strtotime($timestamp);
		$current_time = time();
		$time_difference = $current_time - $time_ago;
		$seconds = $time_difference;
	
		if ($seconds < 60) {
			return $seconds . " seconds ago";
		} elseif ($seconds < 3600) {
			return floor($seconds / 60) . " minutes ago";
		} elseif ($seconds < 86400) {
			return floor($seconds / 3600) . " hours ago";
		} elseif ($seconds < 604800) {
			return floor($seconds / 86400) . " days ago";
		} elseif ($seconds < 2419200) {
			return floor($seconds / 604800) . " weeks ago";
		} elseif ($seconds < 29030400) {
			return floor($seconds / 2419200) . " months ago";
		} else {
			return floor($seconds / 29030400) . " years ago";
		}
	}

	// Shorten a string from the middle
	function shortenStringMiddle($string, $maxLength = 10) {
		// Check if the string length is greater than the maximum length
		if (strlen($string) > $maxLength) {
			// Calculate the lengths of the start and end parts
			$startLength = ($maxLength - 3) / 2;
			$endLength = $maxLength - $startLength - 3;

			// Shorten the string and insert an ellipsis in the middle
			$shortenedString = substr($string, 0, $startLength) . '...' . substr($string, -$endLength);
		} else {
			// If the string is within the maximum length, keep it as is
			$shortenedString = $string;
		}

		return $shortenedString;
	}

